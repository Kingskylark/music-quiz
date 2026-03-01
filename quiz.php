<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/session.php';

// Require user login
require_user_login();

// Check quiz status FIRST — completed users always go to results
check_quiz_status();

// Then check if game is active for non-completed users
if (!is_game_active()) {
    redirect('waiting.php');
}

// Get user data
$user = get_user_data();

// Get user progress
$progress = get_user_progress($user['id']);

// Start quiz if not started
if ($user['status'] === 'not_started' && $progress === 0) {
    start_quiz($user['id']);
}

$page_title = "Music Quiz";
?>

<?php include 'includes/header.php'; ?>

<div class="quiz-page min-vh-100">
    <!-- Quiz Header -->
    <div class="quiz-header bg-dark shadow-sm">
        <div class="container-fluid">
            <div class="row align-items-center py-3">
                <div class="col-3">
                    <div class="user-info">
                        <i class="bi bi-person-circle text-success me-2"></i>
                        <span class="text-white fw-bold"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                </div>
                <div class="col-6 text-center">
                    <div class="timer-display" id="timerDisplay">
                        <i class="bi bi-clock-fill text-warning me-2"></i>
                        <span id="timeLeft" class="text-warning fw-bold fs-4">20</span>
                    </div>
                </div>
                <div class="col-3 text-end">
                    <div class="score-display">
                        <i class="bi bi-trophy-fill text-success me-2"></i>
                        <span class="text-white">Score: <strong id="currentScore">0</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Progress Bar -->
    <div class="quiz-progress-bar">
        <div class="progress" style="height: 8px; border-radius: 0;">
            <div class="progress-bar bg-success" role="progressbar" id="progressBar" 
                 style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>
        <div class="container-fluid">
            <div class="text-center py-2">
                <small class="text-white">
                    Question <strong id="currentQuestion">1</strong> of <strong><?php echo TOTAL_QUESTIONS; ?></strong>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Quiz Content -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Question Card -->
                <div class="card quiz-card shadow-lg border-0" id="questionCard">
                    <div class="card-body p-5">
                        <!-- Loading State -->
                        <div id="loadingState" class="text-center py-5">
                            <div class="spinner-border text-success mb-3" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-dark mb-3">Loading question...</p>
                        </div>
                        
                        <!-- Question Content (Hidden initially) -->
                        <div id="questionContent" >
                            <!-- Question Number Badge -->
                            <div class="mb-4">
                                <span class="badge bg-success fs-6">
                                    <i class="bi bi-music-note me-1"></i>
                                    <span id="questionCategory">General</span>
                                </span>
                            </div>
                            
                            <!-- Question Text -->
                            <h4 class="question-text text-light mb-4" id="questionText">
                                <!-- Question will be loaded here -->
                            </h4>
                            
                            <!-- Answer Options -->
                            <div class="options-container" id="optionsContainer">
                                <div class="option-item" data-option="A">
                                    <input type="radio" class="btn-check" name="answer" id="optionA" value="A">
                                    <label class="btn btn-option w-100 text-start" for="optionA">
                                        <span class="option-letter">A</span>
                                        <span class="option-text" id="optionAText"></span>
                                    </label>
                                </div>
                                
                                <div class="option-item" data-option="B">
                                    <input type="radio" class="btn-check" name="answer" id="optionB" value="B">
                                    <label class="btn btn-option w-100 text-start" for="optionB">
                                        <span class="option-letter">B</span>
                                        <span class="option-text" id="optionBText"></span>
                                    </label>
                                </div>
                                
                                <div class="option-item" data-option="C">
                                    <input type="radio" class="btn-check" name="answer" id="optionC" value="C">
                                    <label class="btn btn-option w-100 text-start" for="optionC">
                                        <span class="option-letter">C</span>
                                        <span class="option-text" id="optionCText"></span>
                                    </label>
                                </div>
                                
                                <div class="option-item" data-option="D">
                                    <input type="radio" class="btn-check" name="answer" id="optionD" value="D">
                                    <label class="btn btn-option w-100 text-start" for="optionD">
                                        <span class="option-letter">D</span>
                                        <span class="option-text" id="optionDText"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Feedback Message (Hidden initially) -->
                            <div id="feedbackMessage" class="mt-4" style="display: none;">
                                <!-- Feedback will appear here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quiz Info Footer -->
                <div class="text-center mt-4">
                    <p class="text-light small">
                        <i class="bi bi-info-circle me-2"></i>
                        Answer within <strong><?php echo QUIZ_TIME_PER_QUESTION; ?> seconds</strong> or the question will auto-submit
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Warning Modal (Prevent page leave) -->
<div class="modal fade" id="warningModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-warning">
            <div class="modal-header border-warning">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Warning!
                </h5>
            </div>
            <div class="modal-body">
                <p class="mb-0">Do not refresh or close this page during the quiz. Your progress may be lost!</p>
            </div>
            <div class="modal-footer border-warning">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
    const USER_ID = ' . $user['id'] . ';
    const TOTAL_QUESTIONS = ' . TOTAL_QUESTIONS . ';
    const TIME_PER_QUESTION = ' . QUIZ_TIME_PER_QUESTION . ';
    const CURRENT_PROGRESS = ' . $progress . ';
    
    console.log("=== QUIZ DEBUG INFO ===");
    console.log("User ID:", USER_ID);
    console.log("Total Questions:", TOTAL_QUESTIONS);
    console.log("Time per Question:", TIME_PER_QUESTION);
    console.log("Current Progress:", CURRENT_PROGRESS);
    console.log("======================");
</script>
<script src="assets/js/quiz.js"></script>
';
include 'includes/footer.php';
?>
