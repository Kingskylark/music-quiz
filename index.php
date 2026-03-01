<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// If user already has active session, redirect to appropriate page
if (check_user_session()) {
    $user = get_user_data();
    if ($user !== null && isset($user['status'])) {
        if ($user['status'] === 'completed') {
            redirect('results.php');
        } elseif ($user['status'] === 'in_progress') {
            redirect('quiz.php');
        }
    }
}

$page_title = "Music Quiz Game - Home";

// Get analytics safely
$analytics = get_analytics();
if (!$analytics || !is_array($analytics)) {
    $analytics = [
        'total_users' => 0,
        'completed_users' => 0,
        'average_score' => 0,
        'total_questions' => get_questions_count()
    ];
}

// Check if game is active
$game_active = is_game_active();
$allow_registration = is_registration_allowed();

// Get game status message
$game_status_message = '';
if (!$game_active) {
    $game_status_message = 'Game is currently paused. Please wait for the admin to start.';
}
if (!$allow_registration) {
    $game_status_message = 'Registration is currently closed.';
}
?>

<?php include 'includes/header.php'; ?>

<!-- Landing Page Content -->
<div class="landing-page">
    <!-- Hero Section -->
    <section class="hero-section min-vh-100 d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <!-- Logo/Icon -->
                    <div class="logo-container mb-4" data-aos="fade-down">
                        <i class="bi bi-music-note-beamed display-1"></i>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="display-3 fw-bold mb-3 gradient-text" data-aos="fade-up">
                        🎵 Music Quiz Challenge
                    </h1>
                    
                    <!-- Subtitle -->
                    <p class="lead mb-4 text-light" data-aos="fade-up" data-aos-delay="100">
                        Test your music knowledge in this exciting timed quiz!<br>
                        <span class="text-success fw-bold">20 Questions • 20 Seconds Each • Real-time Leaderboard</span>
                    </p>
                    
                    <!-- CTA Buttons -->
<div class="d-grid gap-3 d-md-flex justify-content-md-center mb-5" data-aos="fade-up" data-aos-delay="200">
    <button type="button" class="btn btn-success btn-lg px-5 py-3 rounded-pill shadow-lg" data-bs-toggle="modal" data-bs-target="#rulesModal">
        <i class="bi bi-play-circle-fill me-2"></i>Start Quiz
    </button>
    <a href="login.php" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill">
        <i class="bi bi-box-arrow-in-right me-2"></i>Continue Quiz
    </a>
    <a href="leaderboard.php" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill">
        <i class="bi bi-trophy-fill me-2"></i>Leaderboard
    </a>
</div>
                    
                    <!-- Stats Cards -->
                    <div class="row g-4 mt-4" data-aos="fade-up" data-aos-delay="300">
                        <?php
                        $analytics = get_analytics();
                        ?>
                        <div class="col-md-4">
                            <div class="stat-card card bg-dark border-success">
                                <div class="card-body text-center">
                                    <i class="bi bi-people-fill display-4 text-success mb-3"></i>
                                    <h3 class="fw-bold text-white"><?php echo $analytics['total_users']; ?></h3>
                                    <p class="text-light mb-0">Total Players</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card card bg-dark border-success">
                                <div class="card-body text-center">
                                    <i class="bi bi-patch-check-fill display-4 text-success mb-3"></i>
                                    <h3 class="fw-bold text-white"><?php echo $analytics['total_questions']; ?></h3>
                                    <p class="text-light mb-0">Questions</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card card bg-dark border-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-star-fill display-4 text-white mb-3"></i>
                                    <h3 class="fw-bold text-white"><?php echo number_format($analytics['average_score'], 1); ?></h3>
                                    <p class="text-light mb-0">Average Score</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Animated Background Elements -->
        <div class="music-notes">
            <i class="bi bi-music-note note1"></i>
            <i class="bi bi-music-note-beamed note2"></i>
            <i class="bi bi-music-note note3"></i>
            <i class="bi bi-music-note-beamed note4"></i>
            <i class="bi bi-music-note note5"></i>
        </div>
    </section>
</div>

<!-- Rules Modal -->
<div class="modal fade" id="rulesModal" tabindex="-1" aria-labelledby="rulesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content text-white">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="rulesModalLabel">
                    <i class="bi bi-info-circle-fill text-success me-2"></i>Quiz Rules
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="rules-content">
                    <div class="rule-item mb-4">
                        <div class="d-flex align-items-start">
                            <div class="rule-icon me-3">
                                <i class="bi bi-1-circle-fill text-success fs-3"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold">Total Questions</h6>
                                <p class="mb-0 text-light">Answer <strong>20 music-related questions</strong> to complete the quiz.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-item mb-4">
                        <div class="d-flex align-items-start">
                            <div class="rule-icon me-3">
                                <i class="bi bi-2-circle-fill text-success fs-3"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold">Time Limit</h6>
                                <p class="mb-0 text-light">You have <strong>20 seconds</strong> to answer each question. The timer starts automatically.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-item mb-4">
                        <div class="d-flex align-items-start">
                            <div class="rule-icon me-3">
                                <i class="bi bi-3-circle-fill text-success fs-3"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold">Scoring</h6>
                                <p class="mb-0 text-light">Earn <strong>1 point</strong> for each correct answer. Wrong or unanswered questions earn 0 points.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-item mb-4">
                        <div class="d-flex align-items-start">
                            <div class="rule-icon me-3">
                                <i class="bi bi-4-circle-fill text-success fs-3"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold">Auto-Submit</h6>
                                <p class="mb-0 text-light">Questions automatically move to the next when you select an answer or time runs out.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-item mb-4">
                        <div class="d-flex align-items-start">
                            <div class="rule-icon me-3">
                                <i class="bi bi-5-circle-fill text-success fs-3"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold">One Attempt Only</h6>
                                <p class="mb-0 text-light">You can only take the quiz <strong>once</strong>. Make each answer count!</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>
                            <strong>Important:</strong> Do not refresh the page or use the back button during the quiz!
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="register.php" class="btn btn-success">
                    <i class="bi bi-check-circle-fill me-2"></i>I Understand, Let's Go!
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="assets/js/landing.js"></script>';
include 'includes/footer.php';
?>