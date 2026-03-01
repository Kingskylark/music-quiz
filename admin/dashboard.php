<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Require admin login
require_admin_login();

$page_title = "Admin Dashboard";
$css_path = '../assets/css/custom.css';

// Get current settings
$game_active = is_game_active();
$allow_registration = is_registration_allowed();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'toggle_game') {
        $new_status = $game_active ? '0' : '1';
        update_game_setting('game_active', $new_status);
        $game_active = !$game_active;

        if (!$game_active) {
            // Game just stopped — auto-mark top 3 winners
            $conn->query("UPDATE users SET is_winner = 0, prize_rank = NULL");

            $top3_query = "SELECT id FROM users WHERE status = 'completed' ORDER BY score DESC, total_time ASC LIMIT 3";
            $top3_result = $conn->query($top3_query);

            $rank = 1;
            if ($top3_result) {
                while ($winner = $top3_result->fetch_assoc()) {
                    $conn->query("UPDATE users SET is_winner = 1, prize_rank = $rank WHERE id = {$winner['id']}");
                    $rank++;
                }
            }
            $winners_count = $rank - 1;
            $message = "Game stopped! $winners_count winner(s) marked automatically.";
        } else {
            $message = 'Game started successfully!';
        }

    } elseif ($action === 'toggle_registration') {
        $new_status = $allow_registration ? '0' : '1';
        update_game_setting('allow_registration', $new_status);
        $allow_registration = !$allow_registration;
        $message = $allow_registration ? 'Registration enabled!' : 'Registration disabled!';

    } elseif ($action === 'reset_all_users') {
        $conn->query("DELETE FROM answers");
        $conn->query("DELETE FROM payments");
        $conn->query("DELETE FROM users");
        $conn->query("ALTER TABLE users AUTO_INCREMENT = 1");
        $conn->query("ALTER TABLE answers AUTO_INCREMENT = 1");
        $message = 'All users reset successfully!';

    } elseif ($action === 'mark_winners') {
        $conn->query("UPDATE users SET is_winner = 0, prize_rank = NULL");

        $top3_query = "SELECT id FROM users WHERE status = 'completed' ORDER BY score DESC, total_time ASC LIMIT 3";
        $top3_result = $conn->query($top3_query);

        $rank = 1;
        if ($top3_result) {
            while ($winner = $top3_result->fetch_assoc()) {
                $conn->query("UPDATE users SET is_winner = 1, prize_rank = $rank WHERE id = {$winner['id']}");
                $rank++;
            }
        }
        $message = 'Top 3 winners marked successfully!';

    } elseif ($action === 'mark_paid' && isset($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        $conn->query("UPDATE payments SET payment_status = 'completed' WHERE user_id = $uid");
        $message = 'Payment marked as completed!';

    } elseif ($action === 'mark_unpaid' && isset($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        $conn->query("UPDATE payments SET payment_status = 'pending' WHERE user_id = $uid");
        $message = 'Payment status reset to pending.';
    }
}

// Get stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$waiting_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'not_started'")->fetch_assoc()['count'];
$playing_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'in_progress'")->fetch_assoc()['count'];
$completed_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'completed'")->fetch_assoc()['count'];

// Get current winners — simple query first (no JOIN)
$current_winners = [];
$winners_q = $conn->query("SELECT id, name, nickname, score, total_time, prize_rank FROM users WHERE is_winner = 1 ORDER BY prize_rank ASC");
if ($winners_q) {
    while ($w = $winners_q->fetch_assoc()) {
        // Fetch payment details separately for each winner
        $uid = (int)$w['id'];
        $pay_q = $conn->query("SELECT bank_name, account_number, account_name, payment_status FROM payments WHERE user_id = $uid LIMIT 1");
        if ($pay_q && $pay_q->num_rows > 0) {
            $pay = $pay_q->fetch_assoc();
            $w['bank_name'] = $pay['bank_name'];
            $w['account_number'] = $pay['account_number'];
            $w['account_name'] = $pay['account_name'];
            $w['payment_status'] = $pay['payment_status'];
        } else {
            $w['bank_name'] = null;
            $w['account_number'] = null;
            $w['account_name'] = null;
            $w['payment_status'] = null;
        }
        $current_winners[] = $w;
    }
}

// Get top 3 completed users (for reference, even if not marked as winners yet)
$top3 = [];
$top3_q = $conn->query("SELECT id, name, nickname, score, total_time FROM users WHERE status = 'completed' ORDER BY score DESC, total_time ASC LIMIT 3");
if ($top3_q) {
    while ($t = $top3_q->fetch_assoc()) {
        $top3[] = $t;
    }
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-dashboard">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-music-note-beamed text-success me-2"></i>
                Quiz Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="winners.php"><i class="bi bi-trophy-fill me-1"></i>Winners</a></li>
                    <li class="nav-item"><a class="nav-link" href="questions.php"><i class="bi bi-question-circle me-1"></i>Questions</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people me-1"></i>Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="analytics.php"><i class="bi bi-graph-up me-1"></i>Analytics</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="text-white"><i class="bi bi-speedometer2 text-success me-2"></i>Admin Dashboard</h3>
                <p class="text-light">Manage the game, view stats, and process winners</p>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($message): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-dark border-white">
                    <div class="card-body text-center">
                        <i class="bi bi-people-fill display-4 text-white mb-3"></i>
                        <h2 class="fw-bold text-white mb-0"><?php echo $total_users; ?></h2>
                        <p class="text-light mb-0">Total Registered</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-dark border-warning">
                    <div class="card-body text-center">
                        <i class="bi bi-hourglass-split display-4 text-warning mb-3"></i>
                        <h2 class="fw-bold text-white mb-0"><?php echo $waiting_users; ?></h2>
                        <p class="text-light mb-0">Waiting to Start</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-dark border-info">
                    <div class="card-body text-center">
                        <i class="bi bi-play-circle-fill display-4 text-info mb-3"></i>
                        <h2 class="fw-bold text-white mb-0"><?php echo $playing_users; ?></h2>
                        <p class="text-light mb-0">Currently Playing</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-dark border-success">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle-fill display-4 text-success mb-3"></i>
                        <h2 class="fw-bold text-white mb-0"><?php echo $completed_users; ?></h2>
                        <p class="text-light mb-0">Completed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- WINNERS SECTION -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-dark border-warning">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-trophy-fill me-2"></i>Winners & Payment</h5>
                        <div>
                            <a href="winners.php" class="btn btn-outline-dark btn-sm me-2"><i class="bi bi-eye me-1"></i>View Details</a>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="action" value="mark_winners">
                                <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('Mark top 3 completed players as winners?');">
                                    <i class="bi bi-trophy me-1"></i>Mark Top 3
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($current_winners) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Player</th>
                                            <th class="text-center">Score</th>
                                            <th class="text-center">Prize</th>
                                            <th>Bank Details</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($current_winners as $w):
                                            $medals = [1 => '1st Place', 2 => '2nd Place', 3 => '3rd Place'];
                                            $prize = get_prize_amount($w['prize_rank']);
                                            $status = $w['payment_status'];
                                            $status_badges = [
                                                'pending' => '<span class="badge bg-warning text-dark">Submitted</span>',
                                                'processing' => '<span class="badge bg-info">Processing</span>',
                                                'completed' => '<span class="badge bg-success">Paid</span>',
                                                'failed' => '<span class="badge bg-danger">Failed</span>',
                                            ];
                                        ?>
                                        <tr>
                                            <td><span class="fw-bold text-warning"><?php echo $medals[$w['prize_rank']] ?? $w['prize_rank']; ?></span></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($w['name']); ?></strong>
                                                <?php if (!empty($w['nickname'])): ?>
                                                    <br><small class="text-muted">(<?php echo htmlspecialchars($w['nickname']); ?>)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo $w['score']; ?>/<?php echo TOTAL_QUESTIONS; ?></td>
                                            <td class="text-center text-success fw-bold"><?php echo number_format($prize); ?></td>
                                            <td>
                                                <?php if (!empty($w['bank_name'])): ?>
                                                    <strong><?php echo htmlspecialchars($w['bank_name']); ?></strong><br>
                                                    <span class="text-light"><?php echo htmlspecialchars($w['account_number']); ?></span><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($w['account_name']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="bi bi-clock me-1"></i>Awaiting submission</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($status): ?>
                                                    <?php echo $status_badges[$status] ?? '<span class="badge bg-secondary">'.$status.'</span>'; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Not submitted</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($w['bank_name']) && $status !== 'completed'): ?>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="action" value="mark_paid">
                                                    <input type="hidden" name="user_id" value="<?php echo $w['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark as paid?');">
                                                        <i class="bi bi-check-circle me-1"></i>Paid
                                                    </button>
                                                </form>
                                                <?php elseif ($status === 'completed'): ?>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="action" value="mark_unpaid">
                                                    <input type="hidden" name="user_id" value="<?php echo $w['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-warning btn-sm" onclick="return confirm('Undo paid status?');">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Undo
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <p class="text-light mb-2"><i class="bi bi-info-circle me-1"></i>No winners marked yet.</p>

                                <?php if (count($top3) > 0): ?>
                                    <p class="text-muted small mb-3">Top completed players who will become winners:</p>
                                    <div class="table-responsive">
                                        <table class="table table-dark table-sm">
                                            <thead><tr><th>#</th><th>Player</th><th class="text-center">Score</th><th class="text-center">Time</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($top3 as $i => $t): ?>
                                                <tr>
                                                    <td><?php echo $i + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($t['name']); ?></td>
                                                    <td class="text-center"><?php echo $t['score']; ?>/<?php echo TOTAL_QUESTIONS; ?></td>
                                                    <td class="text-center"><?php echo format_time($t['total_time']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-warning small">Click "Mark Top 3" above or stop the game to auto-mark winners.</p>
                                <?php else: ?>
                                    <p class="text-muted small">No completed players yet.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Control Panels -->
        <div class="row g-4">
            <!-- Game Status Control -->
            <div class="col-md-6">
                <div class="card bg-dark border-<?php echo $game_active ? 'success' : 'danger'; ?>">
                    <div class="card-header bg-<?php echo $game_active ? 'success' : 'danger'; ?> text-white">
                        <h5 class="mb-0"><i class="bi bi-power me-2"></i>Game Status</h5>
                    </div>
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-<?php echo $game_active ? 'play-circle-fill text-success' : 'stop-circle-fill text-danger'; ?> mb-3" style="font-size: 5rem;"></i>
                            <h3 class="text-white mb-3">Game is <?php echo $game_active ? 'ACTIVE' : 'STOPPED'; ?></h3>
                            <?php if ($game_active): ?>
                                <p class="text-success">Players can now start the quiz!</p>
                                <p class="text-warning small"><i class="bi bi-info-circle me-1"></i>Stopping the game will automatically mark the top 3 players as winners</p>
                            <?php else: ?>
                                <p class="text-light">Players are waiting for you to start the game</p>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to <?php echo $game_active ? 'STOP' : 'START'; ?> the game?');">
                            <input type="hidden" name="action" value="toggle_game">
                            <button type="submit" class="btn btn-<?php echo $game_active ? 'danger' : 'success'; ?> btn-lg px-5">
                                <i class="bi bi-<?php echo $game_active ? 'stop' : 'play'; ?>-fill me-2"></i>
                                <?php echo $game_active ? 'Stop Game' : 'Start Game'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Registration Control -->
            <div class="col-md-6">
                <div class="card bg-dark border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Registration Control</h5>
                    </div>
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-<?php echo $allow_registration ? 'unlock-fill text-success' : 'lock-fill text-danger'; ?> mb-3" style="font-size: 5rem;"></i>
                            <h3 class="text-white mb-3">Registration is <?php echo $allow_registration ? 'OPEN' : 'CLOSED'; ?></h3>
                            <?php if ($allow_registration): ?>
                                <p class="text-success">New users can register for the quiz</p>
                            <?php else: ?>
                                <p class="text-light">New registrations are blocked</p>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="toggle_registration">
                            <button type="submit" class="btn btn-<?php echo $allow_registration ? 'warning' : 'success'; ?> btn-lg px-5">
                                <i class="bi bi-<?php echo $allow_registration ? 'lock' : 'unlock'; ?>-fill me-2"></i>
                                <?php echo $allow_registration ? 'Close Registration' : 'Open Registration'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reset Control -->
            <div class="col-md-12">
                <div class="card bg-dark border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="text-white mb-2">Reset All Users</h5>
                                <p class="text-light mb-0">This will permanently delete all registered users, quiz data, and payment records.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <form method="POST" action="" onsubmit="return confirm('WARNING: This will delete ALL users and quiz data! Type DELETE to confirm.') && prompt('Type DELETE to confirm') === 'DELETE';">
                                    <input type="hidden" name="action" value="reset_all_users">
                                    <button type="submit" class="btn btn-danger btn-lg"><i class="bi bi-trash-fill me-2"></i>Reset All Users</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
