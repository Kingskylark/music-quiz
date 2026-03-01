<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Require admin login
require_admin_login();

$page_title = "Game Control";
$css_path = '../assets/css/custom.css';

// Get current settings
$game_active = is_game_active();
$allow_registration = is_registration_allowed();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'toggle_game') {
            $new_status = $game_active ? '0' : '1';
            update_game_setting('game_active', $new_status);
            $game_active = !$game_active;

            if (!$game_active) {
                // Game just stopped — auto-mark top 3 winners
                // First, clear any previous winners
                $conn->query("UPDATE users SET is_winner = FALSE, prize_rank = NULL");

                $top3_query = "SELECT id FROM users
                               WHERE status = 'completed'
                               ORDER BY score DESC, total_time ASC
                               LIMIT 3";
                $top3_result = $conn->query($top3_query);

                $rank = 1;
                while ($winner = $top3_result->fetch_assoc()) {
                    $stmt = $conn->prepare("UPDATE users SET is_winner = TRUE, prize_rank = ? WHERE id = ?");
                    $stmt->bind_param("ii", $rank, $winner['id']);
                    $stmt->execute();
                    $stmt->close();
                    $rank++;
                }

                $winners_count = $rank - 1;
                $message = "Game stopped successfully! $winners_count winner(s) marked automatically.";
            } else {
                $message = 'Game started successfully!';
            }
        } elseif ($action === 'toggle_registration') {
            $new_status = $allow_registration ? '0' : '1';
            update_game_setting('allow_registration', $new_status);
            $allow_registration = !$allow_registration;
            $message = $allow_registration ? 'Registration enabled!' : 'Registration disabled!';
        } elseif ($action === 'reset_all_users') {
            // Delete all users
            $conn->query("DELETE FROM users");
            $conn->query("ALTER TABLE users AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE answers AUTO_INCREMENT = 1");
            $message = 'All users reset successfully!';
        } elseif ($action === 'mark_winners') {
            // Clear previous winners
            $conn->query("UPDATE users SET is_winner = FALSE, prize_rank = NULL");

            // Get top 3 users
            $top3_query = "SELECT id FROM users
                           WHERE status = 'completed'
                           ORDER BY score DESC, total_time ASC
                           LIMIT 3";
            $top3_result = $conn->query($top3_query);

            $rank = 1;
            while ($winner = $top3_result->fetch_assoc()) {
                $stmt = $conn->prepare("UPDATE users SET is_winner = TRUE, prize_rank = ? WHERE id = ?");
                $stmt->bind_param("ii", $rank, $winner['id']);
                $stmt->execute();
                $stmt->close();
                $rank++;
            }

            $message = 'Top 3 winners marked successfully!';
        }
    }
}

// Get stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$waiting_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'not_started'")->fetch_assoc()['count'];
$playing_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'in_progress'")->fetch_assoc()['count'];
$completed_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'completed'")->fetch_assoc()['count'];

// Get current winners
$winners_query = "SELECT u.id, u.name, u.nickname, u.score, u.total_time, u.prize_rank,
                         p.payment_status, p.bank_name
                  FROM users u
                  LEFT JOIN payments p ON u.id = p.user_id
                  WHERE u.is_winner = TRUE
                  ORDER BY u.prize_rank ASC";
$winners_result = $conn->query($winners_query);
$current_winners = [];
if ($winners_result) {
    while ($w = $winners_result->fetch_assoc()) {
        $current_winners[] = $w;
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
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="game-control.php">
                            <i class="bi bi-joystick me-1"></i>Game Control
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="questions.php">
                            <i class="bi bi-question-circle me-1"></i>Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people me-1"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics.php">
                            <i class="bi bi-graph-up me-1"></i>Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="text-white">
                    <i class="bi bi-joystick text-success me-2"></i>
                    Game Control Center
                </h3>
                <p class="text-light">Start/Stop the game and manage settings</p>
            </div>
        </div>
        
        <!-- Success Message -->
        <?php if (isset($message)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $message; ?>
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
        
        <!-- Control Panels -->
        <div class="row g-4">
            <!-- Game Status Control -->
            <div class="col-md-6">
                <div class="card bg-dark border-<?php echo $game_active ? 'success' : 'danger'; ?>">
                    <div class="card-header bg-<?php echo $game_active ? 'success' : 'danger'; ?> text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-power me-2"></i>Game Status
                        </h5>
                    </div>
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-<?php echo $game_active ? 'play-circle-fill text-success' : 'stop-circle-fill text-danger'; ?> mb-3" style="font-size: 5rem;"></i>
                            <h3 class="text-white mb-3">
                                Game is <?php echo $game_active ? 'ACTIVE' : 'STOPPED'; ?>
                            </h3>
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
                        <h5 class="mb-0">
                            <i class="bi bi-person-plus me-2"></i>Registration Control
                        </h5>
                    </div>
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-<?php echo $allow_registration ? 'unlock-fill text-success' : 'lock-fill text-danger'; ?> mb-3" style="font-size: 5rem;"></i>
                            <h3 class="text-white mb-3">
                                Registration is <?php echo $allow_registration ? 'OPEN' : 'CLOSED'; ?>
                            </h3>
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
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="text-white mb-2">Reset All Users</h5>
                                <p class="text-light mb-0">
                                    This will permanently delete all registered users and their quiz data. 
                                    Use this to start fresh for a new quiz session.
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <form method="POST" action="" onsubmit="return confirm('⚠️ WARNING: This will delete ALL users and quiz data! Type DELETE in the prompt to confirm.') && prompt('Type DELETE to confirm') === 'DELETE';">
                                    <input type="hidden" name="action" value="reset_all_users">
                                    <button type="submit" class="btn btn-danger btn-lg">
                                        <i class="bi bi-trash-fill me-2"></i>Reset All Users
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Winners Section -->
            <div class="col-md-12 mt-4">
                <div class="card bg-dark border-warning">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-trophy-fill me-2"></i>Winners
                        </h5>
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="action" value="mark_winners">
                            <button type="submit" class="btn btn-dark btn-sm">
                                <i class="bi bi-arrow-clockwise me-1"></i>Re-mark Winners
                            </button>
                        </form>
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
                                            <th class="text-center">Time</th>
                                            <th class="text-center">Prize</th>
                                            <th class="text-center">Payment Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $medals = [1 => '1st', 2 => '2nd', 3 => '3rd'];
                                        foreach ($current_winners as $w):
                                            $prize = get_prize_amount($w['prize_rank']);
                                            $status = $w['payment_status'] ?? 'awaiting';
                                            $status_badge = [
                                                'awaiting' => '<span class="badge bg-secondary">Awaiting Details</span>',
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
                                                <?php if ($w['nickname']): ?>
                                                    <br><small class="text-muted">"<?php echo htmlspecialchars($w['nickname']); ?>"</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo $w['score']; ?>/<?php echo TOTAL_QUESTIONS; ?></td>
                                            <td class="text-center"><?php echo format_time($w['total_time']); ?></td>
                                            <td class="text-center text-success fw-bold"><?php echo number_format($prize); ?></td>
                                            <td class="text-center"><?php echo $status_badge[$status] ?? $status_badge['awaiting']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-trophy display-4 text-muted mb-3 d-block"></i>
                                <p class="text-light mb-2">No winners marked yet.</p>
                                <p class="text-muted small">Stop the game to automatically mark the top 3 players as winners, or click "Re-mark Winners" above.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>