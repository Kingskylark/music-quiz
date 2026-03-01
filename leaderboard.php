<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Leaderboard";

// Get leaderboard data
$leaderboard = get_leaderboard(50); // Top 50 users

// Get current user's rank if logged in
$user_rank = null;
if (check_user_session()) {
    $user = get_user_data();
    if ($user['status'] === 'completed') {
        $rank_query = "SELECT COUNT(*) + 1 as rank FROM users 
                       WHERE status = 'completed' 
                       AND (score > {$user['score']} OR (score = {$user['score']} AND total_time < {$user['total_time']}))";
        $rank_result = $conn->query($rank_query);
        $user_rank = $rank_result->fetch_assoc()['rank'];
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="leaderboard-page min-vh-100">
    <div class="container py-5">
        <!-- Header -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <i class="bi bi-trophy-fill display-1 text-success mb-3"></i>
                <h1 class="display-4 fw-bold text-white mb-3">
                    🏆 Leaderboard
                </h1>
                <p class="text-light lead">Top performers in the Music Quiz Challenge</p>
            </div>
        </div>
        
        <!-- User's Rank (if logged in and completed) -->
        <?php if ($user_rank): ?>
        <div class="row mb-4">
            <div class="col-lg-8 mx-auto">
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-star-fill fs-3 me-3"></i>
                    <div>
                        <strong>Your Rank:</strong> #<?php echo $user_rank; ?> 
                        (Score: <?php echo $user['score']; ?>/<?php echo TOTAL_QUESTIONS; ?>)
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Leaderboard Table -->
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="card bg-dark border-success shadow-lg">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-list-ol me-2"></i>
                            Top <?php echo count($leaderboard); ?> Players
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($leaderboard) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0 leaderboard-table">
                                <thead>
                                    <tr>
                                        <th width="10%">Rank</th>
                                        <th width="40%">Player</th>
                                        <th width="15%" class="text-center">Score</th>
                                        <th width="15%" class="text-center">Time</th>
                                        <th width="20%">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderboard as $index => $player): 
                                        $rank = $index + 1;
                                        $medal = '';
                                        $row_class = '';
                                        
                                        if ($rank === 1) {
                                            $medal = '🥇';
                                            $row_class = 'rank-1';
                                        } elseif ($rank === 2) {
                                            $medal = '🥈';
                                            $row_class = 'rank-2';
                                        } elseif ($rank === 3) {
                                            $medal = '🥉';
                                            $row_class = 'rank-3';
                                        }
                                        
                                        // Highlight current user
                                        if ($user_rank && $user_rank == $rank) {
                                            $row_class .= ' current-user';
                                        }
                                    ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td>
                                            <span class="rank-badge"><?php echo $medal . ' #' . $rank; ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($player['name']); ?></strong>
                                            <?php if ($player['nickname']): ?>
                                                <br><small class="text-muted">"<?php echo htmlspecialchars($player['nickname']); ?>"</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success fs-6">
                                                <?php echo $player['score']; ?> / <?php echo TOTAL_QUESTIONS; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo format_time($player['total_time']); ?>
                                        </td>
                                        <td>
                                            <small><?php echo date('M d, Y', strtotime($player['registered_at'])); ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted mb-3 d-block"></i>
                            <p class="text-light">No players yet. Be the first to complete the quiz!</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="text-center mt-4">
                    <?php if (!check_user_session()): ?>
                    <a href="register.php" class="btn btn-success btn-lg px-5 me-2">
                        <i class="bi bi-play-circle me-2"></i>Take the Quiz
                    </a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline-light btn-lg px-5">
                        <i class="bi bi-house me-2"></i>Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>