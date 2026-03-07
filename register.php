<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Handle AJAX registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    header('Content-Type: application/json');
    
    // Check if registration is allowed
    if (!is_registration_allowed()) {
        echo json_encode(['success' => false, 'message' => 'Registration is currently closed. Please check back later.']);
        exit;
    }
    
    if (!isset($_POST['nickname']) || empty($_POST['nickname'])) {
        echo json_encode(['success' => false, 'message' => 'Username/Nickname is required']);
        exit;
    }

    if (!isset($_POST['password']) || empty($_POST['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password is required']);
        exit;
    }

    $nickname = clean_input($_POST['nickname']);
    $name = isset($_POST['name']) && !empty($_POST['name']) ? clean_input($_POST['name']) : $nickname;
    $raw_password = $_POST['password'];

    // Validate nickname length
    if (strlen($nickname) < 3) {
        echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
        exit;
    }

    // Validate password length
    if (strlen($raw_password) < 4) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters']);
        exit;
    }
    
    // Check if nickname already exists
    $check_query = "SELECT id FROM users WHERE nickname = '$nickname' LIMIT 1";
    $check_result = $conn->query($check_query);
    
    if ($check_result && $check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This username is already taken. Please choose another.']);
        exit;
    }
    
    // Check if we have enough questions
    $questions_count = get_questions_count();
    if ($questions_count < TOTAL_QUESTIONS) {
        echo json_encode(['success' => false, 'message' => "Quiz is not ready yet. Only $questions_count questions available."]);
        exit;
    }
    
    // Generate session ID and hash password
    $session_id = generate_session_id();
    $ip_address = get_ip_address();
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    // Insert user into database
    $insert_query = "INSERT INTO users (name, nickname, password, session_id, ip_address, status, registered_at)
                     VALUES ('$name', '$nickname', '$hashed_password', '$session_id', '$ip_address', 'not_started', NOW())";
    
    if ($conn->query($insert_query)) {
        $user_id = $conn->insert_id;
        
        // Set session variables (session already started by config.php)
        $_SESSION['user_id'] = $user_id;
        $_SESSION['session_id'] = $session_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful!',
            'data' => ['redirect' => 'waiting.php']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
    exit;
}

// If user already has active session, redirect
if (check_user_session()) {
    $user = get_user_data();
    if ($user !== null && isset($user['status'])) {
        if ($user['status'] === 'completed') {
            redirect('results.php');
        } elseif ($user['status'] === 'in_progress') {
            redirect('quiz.php');
        } else {
            // User registered but waiting
            redirect('waiting.php');
        }
    }
}

// Check if registration is allowed
$registration_allowed = is_registration_allowed();
if (!$registration_allowed) {
    $error_message = "Registration is currently closed. Please check back later or contact the administrator.";
    $error_type = "closed";
}

// Check if we have enough questions
if (!isset($error_message)) {
    $questions_count = get_questions_count();
    if ($questions_count < TOTAL_QUESTIONS) {
        $error_message = "Quiz is not ready yet. Only $questions_count questions available. Please contact admin.";
        $error_type = "not_ready";
    }
}

$page_title = "Register for Quiz";
?>

<?php include 'includes/header.php'; ?>

<div class="registration-page min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <!-- Registration Card -->
                <div class="card shadow-lg border-0 register-card">
                    <div class="card-body p-5">
                        <!-- Logo/Icon -->
                        <div class="text-center mb-4">
                            <div class="register-icon-wrapper">
                                <?php if (isset($error_type) && $error_type === 'closed'): ?>
                                    <i class="bi bi-lock-fill display-3 text-danger"></i>
                                <?php else: ?>
                                    <i class="bi bi-person-plus-fill display-3 text-success"></i>
                                <?php endif; ?>
                            </div>
                            <h3 class="mt-3 fw-bold text-white">
                                <?php echo isset($error_type) && $error_type === 'closed' ? 'Registration Closed' : 'Join the Quiz'; ?>
                            </h3>
                            <p class="text-light small">
                                <?php echo isset($error_type) && $error_type === 'closed' ? 'Check back later' : 'Enter your details to get started'; ?>
                            </p>
                        </div>
                        
                        <?php if (isset($error_message)): ?>
                            <!-- Error Message -->
                            <div class="alert alert-<?php echo $error_type === 'closed' ? 'warning' : 'danger'; ?>" role="alert">
                                <i class="bi bi-<?php echo $error_type === 'closed' ? 'lock-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
                                <strong><?php echo $error_type === 'closed' ? 'Registration Closed!' : 'Quiz Unavailable!'; ?></strong><br>
                                <?php echo $error_message; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Home
                                </a>
                                <?php if ($error_type === 'closed'): ?>
                                    <a href="login.php" class="btn btn-success ms-2">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Continue
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- Error/Success Messages (AJAX will populate this) -->
                            <div id="alertContainer"></div>
                            
                            <!-- Registration Form -->
                            <form id="registrationForm">
                                <input type="hidden" name="action" value="register">
                                
                                <div class="mb-4">
                                    <label for="name" class="form-label text-light">
                                        <i class="bi bi-person-fill me-2"></i>Full Name (Optional)
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control form-control-lg" 
                                        id="name" 
                                        name="name" 
                                        placeholder="Enter your full name"
                                        maxlength="100"
                                    >
                                    <small class="text-light">If left blank, your username will be used</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="nickname" class="form-label text-light">
                                        <i class="bi bi-tag-fill me-2"></i>Username *
                                    </label>
                                    <input
                                        type="text"
                                        class="form-control form-control-lg"
                                        id="nickname"
                                        name="nickname"
                                        placeholder="Choose a unique username"
                                        required
                                        minlength="3"
                                        maxlength="50"
                                        aria-required="true"
                                    >
                                    <small class="text-light">
                                        <strong>Remember this!</strong> You'll use it to login.
                                    </small>
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label text-light">
                                        <i class="bi bi-lock-fill me-2"></i>Password *
                                    </label>
                                    <input
                                        type="password"
                                        class="form-control form-control-lg"
                                        id="password"
                                        name="password"
                                        placeholder="Choose a password"
                                        required
                                        minlength="4"
                                        maxlength="50"
                                    >
                                    <small class="text-light">At least 4 characters. You'll need this to login.</small>
                                </div>

                                <!-- Quiz Info -->
                                <div class="quiz-info-box mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="bi bi-question-circle-fill text-success me-2"></i>Questions:</span>
                                        <strong class="text-success"><?php echo TOTAL_QUESTIONS; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="bi bi-clock-fill text-success me-2"></i>Time per question:</span>
                                        <strong class="text-success"><?php echo QUIZ_TIME_PER_QUESTION; ?> seconds</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span><i class="bi bi-trophy-fill text-success me-2"></i>Max Score:</span>
                                        <strong class="text-success"><?php echo TOTAL_QUESTIONS; ?> points</strong>
                                    </div>
                                </div>
                                
                                <!-- Terms Checkbox -->
                                <div class="form-check mb-4">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="agreeTerms" 
                                        required
                                    >
                                    <label class="form-check-label text-light" for="agreeTerms">
                                        I understand the rules and I'm ready to start the quiz
                                    </label>
                                </div>
                                
                                <div class="d-grid gap-2 mb-3">
                                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                                        <i class="bi bi-play-circle-fill me-2"></i>Register & Join
                                    </button>
                                </div>
                                
                                <div class="text-center">
                                    <a href="index.php" class="text-light text-decoration-none small">
                                        <i class="bi bi-arrow-left me-1"></i>Back to Home
                                    </a>
                                    <span class="text-muted mx-2">|</span>
                                    <a href="login.php" class="text-light text-decoration-none small">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>Already Registered?
                                    </a>
                                    <span class="text-muted mx-2">|</span>
                                    <a href="leaderboard.php" class="text-light text-decoration-none small">
                                        <i class="bi bi-trophy me-1"></i>Leaderboard
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Additional Info -->
                <div class="text-center mt-4">
                    <p class="text-light small">
                        <i class="bi bi-shield-check text-success me-2"></i>
                        Your information is secure and will only be used for this quiz
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="assets/js/register.js"></script>';
include 'includes/footer.php';
?>