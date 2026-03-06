<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Require admin login
require_admin_login();

$page_title = "Winners & Payments";
$css_path = '../assets/css/custom.css';
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_winners') {
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

    } elseif ($_POST['action'] === 'mark_paid' && isset($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        $conn->query("UPDATE payments SET payment_status = 'completed' WHERE user_id = $uid");
        $message = 'Payment marked as completed!';

    } elseif ($_POST['action'] === 'mark_unpaid' && isset($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        $conn->query("UPDATE payments SET payment_status = 'pending' WHERE user_id = $uid");
        $message = 'Payment status reset to pending.';
    }
}

// Get current winners (no JOIN — separate queries for reliability)
$current_winners = [];
$winners_q = $conn->query("SELECT id, name, nickname, score, total_time, prize_rank FROM users WHERE is_winner = 1 ORDER BY prize_rank ASC");
if ($winners_q) {
    while ($w = $winners_q->fetch_assoc()) {
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

// Get top 3 completed users for reference
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="winners.php"><i class="bi bi-trophy-fill me-1"></i>Winners</a></li>
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
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="text-white"><i class="bi bi-trophy-fill text-warning me-2"></i>Winners & Payments</h3>
                    <p class="text-light">View winner details and process payments</p>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="mark_winners">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Mark top 3 completed players as winners?');">
                        <i class="bi bi-trophy me-1"></i>Mark Top 3 as Winners
                    </button>
                </form>
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

        <?php if (count($current_winners) > 0): ?>
        <!-- Winners Cards -->
        <?php foreach ($current_winners as $w):
            $medals = [1 => '1st', 2 => '2nd', 3 => '3rd'];
            $medal_colors = [1 => 'warning', 2 => 'secondary', 3 => 'info'];
            $prize = get_prize_amount($w['prize_rank']);
            $status = $w['payment_status'];
        ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-dark border-<?php echo $medal_colors[$w['prize_rank']] ?? 'secondary'; ?>">
                    <div class="card-header bg-<?php echo $medal_colors[$w['prize_rank']] ?? 'secondary'; ?> <?php echo $w['prize_rank'] == 1 ? 'text-dark' : 'text-white'; ?>">
                        <h5 class="mb-0">
                            <i class="bi bi-trophy-fill me-2"></i>
                            <?php echo $medals[$w['prize_rank']] ?? $w['prize_rank']; ?> Place — <?php echo number_format($prize); ?> NGN
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Player Info -->
                            <div class="col-md-4">
                                <h6 class="text-warning mb-3">Player Info</h6>
                                <p class="text-white mb-1"><strong class="text-white">Name:</strong> <?php echo htmlspecialchars($w['name']); ?></p>
                                <?php if (!empty($w['nickname'])): ?>
                                <p class="text-white mb-1"><strong class="text-white">Nickname:</strong> <?php echo htmlspecialchars($w['nickname']); ?></p>
                                <?php endif; ?>
                                <p class="text-white mb-1"><strong>Score:</strong> <?php echo $w['score']; ?>/<?php echo TOTAL_QUESTIONS; ?></p>
                                <p class="text-white mb-0"><strong>Time:</strong> <?php echo format_time($w['total_time']); ?></p>
                            </div>

                            <!-- Bank Details -->
                            <div class="col-md-4">
                                <h6 class="text-warning mb-3">Bank Details</h6>
                                <?php if (!empty($w['bank_name'])): ?>
                                <p class="text-white mb-1"><strong>Bank:</strong> <?php echo htmlspecialchars($w['bank_name']); ?></p>
                                <p class="text-white mb-1"><strong>Account No:</strong> <span class="text-success fs-5"><?php echo htmlspecialchars($w['account_number']); ?></span></p>
                                <p class="text-white mb-0"><strong>Account Name:</strong> <?php echo htmlspecialchars($w['account_name']); ?></p>
                                <?php else: ?>
                                <p class="text-muted"><i class="bi bi-clock me-1"></i>Awaiting bank details submission from player</p>
                                <?php endif; ?>
                            </div>

                            <!-- Payment Status -->
                            <div class="col-md-4">
                                <h6 class="text-warning mb-3">Payment Status</h6>
                                <?php
                                $status_info = [
                                    'pending' => ['badge' => 'warning', 'text' => 'Details Submitted', 'icon' => 'clock'],
                                    'processing' => ['badge' => 'info', 'text' => 'Processing', 'icon' => 'arrow-repeat'],
                                    'completed' => ['badge' => 'success', 'text' => 'Paid', 'icon' => 'check-circle'],
                                    'failed' => ['badge' => 'danger', 'text' => 'Failed', 'icon' => 'x-circle'],
                                ];
                                $si = $status_info[$status] ?? null;
                                ?>
                                <?php if ($si): ?>
                                <span class="badge bg-<?php echo $si['badge']; ?> fs-6 px-3 py-2">
                                    <i class="bi bi-<?php echo $si['icon']; ?> me-1"></i><?php echo $si['text']; ?>
                                </span>
                                <?php else: ?>
                                <span class="badge bg-secondary fs-6 px-3 py-2">
                                    <i class="bi bi-dash-circle me-1"></i>Not submitted yet
                                </span>
                                <?php endif; ?>
                                <p class="text-muted small mt-2 mb-0">Prize: <?php echo number_format($prize); ?> NGN</p>

                                <!-- Action buttons -->
                                <?php if (!empty($w['bank_name'])): ?>
                                <div class="mt-3">
                                    <?php if ($status !== 'completed'): ?>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="user_id" value="<?php echo $w['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark payment as completed for <?php echo htmlspecialchars($w['name']); ?>?');">
                                            <i class="bi bi-check-circle me-1"></i>Mark as Paid
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="action" value="mark_unpaid">
                                        <input type="hidden" name="user_id" value="<?php echo $w['id']; ?>">
                                        <button type="submit" class="btn btn-outline-warning btn-sm" onclick="return confirm('Undo paid status for <?php echo htmlspecialchars($w['name']); ?>?');">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Undo
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php else: ?>
        <!-- No Winners Yet -->
        <div class="row">
            <div class="col-12">
                <div class="card bg-dark border-secondary">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-trophy display-1 text-muted mb-3"></i>
                        <h4 class="text-white">No Winners Marked Yet</h4>

                        <?php if (count($top3) > 0): ?>
                        <p class="text-muted mb-4">Top completed players who will become winners:</p>
                        <div class="table-responsive" style="max-width: 600px; margin: 0 auto;">
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
                        <p class="text-warning small">Click "Mark Top 3 as Winners" above or stop the game to auto-mark winners.</p>
                        <?php else: ?>
                        <p class="text-muted">No completed players yet. Winners will appear here once the game is stopped.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
