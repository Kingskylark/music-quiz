<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Require user login
require_user_login();

$user = get_user_data();

// Completed users always go to results
if ($user && $user['status'] === 'completed') {
    redirect('results.php');
}

$page_title = "Waiting to Start";

// If game becomes active, redirect to quiz
if (is_game_active()) {
    redirect('quiz.php');
}
?>

<?php include 'includes/header.php'; ?>

<div class="waiting-page min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-lg border-0 waiting-card text-center">
                    <div class="card-body p-5">
                        <!-- Icon -->
                        <div class="waiting-icon mb-4">
                            <i class="bi bi-hourglass-split display-1 text-warning"></i>
                        </div>
                        
                        <!-- Message -->
                        <h2 class="text-white mb-3">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                        <p class="text-light lead mb-4">
                            You're registered and ready to go!
                        </p>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Please wait...</strong><br>
                            The quiz will begin shortly when the admin starts the game.
                        </div>
                        
                        <!-- Auto-refresh notice -->
                        <div class="mt-4">
                            <div class="spinner-border text-success mb-3" role="status">
                                <span class="visually-hidden">Checking...</span>
                            </div>
                            <p class="text-light small">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                This page will automatically refresh when the game starts
                            </p>
                        </div>
                        
                        <!-- Actions -->
                        <div class="mt-4">
                            <button class="btn btn-success me-2" onclick="checkGameStatus()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Check Now
                            </button>
                            <a href="index.php" class="btn btn-outline-dark">
                                <i class="bi bi-house me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-check every 5 seconds
setInterval(checkGameStatus, 5000);

function checkGameStatus() {
    fetch('api/check-game-status.php')
        .then(response => response.json())
        .then(data => {
            if (data.game_active) {
                window.location.href = 'quiz.php';
            }
        })
        .catch(error => console.error('Error:', error));
}
</script>

<?php include 'includes/footer.php'; ?>