<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Require user login
require_user_login();

// Get user data
$user = get_user_data();

// Redirect if not completed
if ($user['status'] !== 'completed') {
    redirect('quiz.php');
}

// Get user's answers breakdown
$correct_query = "SELECT COUNT(*) as correct FROM answers WHERE user_id = {$user['id']} AND is_correct = 1";
$correct_result = $conn->query($correct_query);
$correct_count = $correct_result->fetch_assoc()['correct'];

$incorrect_count = TOTAL_QUESTIONS - $correct_count;
$percentage = round(($correct_count / TOTAL_QUESTIONS) * 100, 1);

$page_title = "Quiz Results";
?>

<?php include 'includes/header.php'; ?>

<div class="results-page min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Results Card -->
                <div class="card results-card shadow-lg border-0 text-center">
                    <div class="card-body p-5">
                        <!-- Trophy Icon -->
                        <div class="trophy-animation mb-4">
                            <i class="bi bi-trophy-fill display-1 text-success"></i>
                        </div>
                        
                        <!-- Completion Message -->
                        <h2 class="text-white mb-3">Quiz Completed!</h2>
                        <p class="text-light mb-4">
                            Congratulations, <strong class="text-success"><?php echo htmlspecialchars($user['name']); ?></strong>!
                        </p>
                        
                        <!-- Score Display -->
                        <div class="score-display-large mb-4">
                            <div class="score-circle">
                                <h1 class="display-1 fw-bold text-success mb-0">
                                    <?php echo $user['score']; ?>
                                </h1>
                                <p class="text-light">out of <?php echo TOTAL_QUESTIONS; ?></p>
                            </div>
                        </div>

                        <?php
// Check if user is a winner
$winner_info = is_winner($user['id']);
if ($winner_info['is_winner'] && PAYMENT_ENABLED && !is_game_active()):
    $prize_amount = get_prize_amount($winner_info['rank']);
    $payment = get_payment_status($user['id']);
?>
    <?php if ($payment && $payment['payment_status'] === 'completed'): ?>
    <div id="winnerBanner" class="alert alert-success mb-4">
        <i class="bi bi-check-circle-fill me-2"></i>
        <strong>Prize Claimed & Paid!</strong> Your ₦<?php echo number_format($prize_amount, 2); ?> has been sent to your account.
    </div>
    <?php elseif ($payment && $payment['bank_name']): ?>
    <div id="winnerBanner" class="alert alert-info mb-4">
        <i class="bi bi-clock-fill me-2"></i>
        <strong>Prize Claimed!</strong> Your bank details have been submitted. Payment of ₦<?php echo number_format($prize_amount, 2); ?> is being processed.
    </div>
    <?php else: ?>
    <div id="winnerBanner" class="alert alert-success mb-4 animate__animated animate__pulse">
        <i class="bi bi-trophy-fill me-2"></i>
        <strong>You're a Winner!</strong> You've won ₦<?php echo number_format($prize_amount, 2); ?>!
        <br>
        <a href="winner-portal.php" class="btn btn-warning btn-sm mt-2">
            <i class="bi bi-bank me-2"></i>Claim Your Prize
        </a>
    </div>
    <?php endif; ?>
<?php else: ?>
    <!-- Placeholder that will be filled by polling if user becomes a winner -->
    <div id="winnerBanner" class="mb-4" style="display:none;"></div>
<?php endif; ?>
                        
                        <!-- Stats Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="stat-box bg-success">
                                    <i class="bi bi-check-circle-fill fs-2 mb-2"></i>
                                    <h3 class="mb-0"><?php echo $correct_count; ?></h3>
                                    <small>Correct</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-box bg-danger">
                                    <i class="bi bi-x-circle-fill fs-2 mb-2"></i>
                                    <h3 class="mb-0"><?php echo $incorrect_count; ?></h3>
                                    <small>Incorrect</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-box bg-info">
                                    <i class="bi bi-clock-fill fs-2 mb-2"></i>
                                    <h3 class="mb-0"><?php echo format_time($user['total_time']); ?></h3>
                                    <small>Total Time</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Percentage Badge -->
                        <div class="mb-4">
                            <span class="badge bg-success fs-4 px-4 py-3">
                                <?php echo $percentage; ?>% Score
                            </span>
                        </div>
                        
                        <!-- Performance Message -->
                        <div class="performance-message mb-4">
                            <?php
                            if ($percentage >= 90) {
                                echo '<p class="text-success fs-5">🌟 Outstanding! You\'re a music genius!</p>';
                            } elseif ($percentage >= 75) {
                                echo '<p class="text-success fs-5">🎵 Excellent work! You really know your music!</p>';
                            } elseif ($percentage >= 60) {
                                echo '<p class="text-info fs-5">🎶 Good job! Keep listening and learning!</p>';
                            } elseif ($percentage >= 40) {
                                echo '<p class="text-warning fs-5">🎸 Not bad! There\'s room for improvement!</p>';
                            } else {
                                echo '<p class="text-warning fs-5">🎤 Keep trying! Practice makes perfect!</p>';
                            }
                            ?>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                            <a href="leaderboard.php" class="btn btn-success btn-lg px-5">
                                <i class="bi bi-trophy me-2"></i>View Leaderboard
                            </a>
                            <a href="index.php" class="btn btn-outline-light btn-lg px-5">
                                <i class="bi bi-house me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php if (!$winner_info['is_winner'] && PAYMENT_ENABLED && !is_game_active()): ?>
<script>
// Poll every 5 seconds to check if this user has been marked as a winner
(function() {
    let winnerCheckInterval = setInterval(function() {
        fetch('api/check-winner-status.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.is_winner) {
                    clearInterval(winnerCheckInterval);
                    const banner = document.getElementById('winnerBanner');
                    const amount = Number(data.data.prize_amount).toLocaleString('en-NG', {minimumFractionDigits: 2});
                    banner.className = 'alert alert-success mb-4 animate__animated animate__pulse';
                    banner.innerHTML = '<i class="bi bi-trophy-fill me-2"></i>' +
                        '<strong>You\'re a Winner!</strong> You\'ve won \u20A6' + amount + '!' +
                        '<br><a href="winner-portal.php" class="btn btn-warning btn-sm mt-2">' +
                        '<i class="bi bi-bank me-2"></i>Claim Your Prize</a>';
                    banner.style.display = '';
                }
            })
            .catch(function() {});
    }, 5000);
})();
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>