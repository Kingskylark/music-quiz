<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// If user already has active session, redirect
if (check_user_session()) {
    $user = get_user_data();
    if ($user['status'] === 'completed') {
        redirect('results.php');
    } elseif ($user['status'] === 'in_progress') {
        redirect('quiz.php');
    } else {
        redirect('quiz.php');
    }
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = clean_input($_POST['nickname'] ?? '');
    
    if (empty($nickname)) {
        $error = 'Please enter your nickname';
    } else {
        // Find user by nickname
        $query = "SELECT * FROM users WHERE nickname = '$nickname' LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if quiz already completed
            if ($user['status'] === 'completed') {
                $error = 'You have already completed the quiz. View your results on the leaderboard.';
            } else {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['session_id'] = $user['session_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_nickname'] = $user['nickname'];
                
                $success = 'Login successful! Redirecting...';
                
                if ($user['status'] === 'in_progress') {
                    header("refresh:1;url=quiz.php");
                } else {
                    header("refresh:1;url=quiz.php");
                }
            }
        } else {
            $error = 'Nickname not found. Please register first.';
        }
    }
}

$page_title = "Login";
?>

<?php include 'includes/header.php'; ?>

<div class="registration-page min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0 register-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="register-icon-wrapper">
                                <i class="bi bi-box-arrow-in-right display-3 text-success"></i>
                            </div>
                            <h3 class="mt-3 fw-bold text-white">Continue Quiz</h3>
                            <p class="text-light small">Login with your nickname</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-circle-fill me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="nickname" class="form-label text-light">
                                    <i class="bi bi-tag-fill me-2"></i>Your Nickname
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg" 
                                    id="nickname" 
                                    name="nickname" 
                                    placeholder="Enter your nickname"
                                    required
                                    value="<?php echo isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : ''; ?>"
                                >
                                <small class="text-dark">The nickname you used during registration</small>
                            </div>
                            
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login & Continue
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="text-dark small mb-2">Don't have an account?</p>
                                <a href="register.php" class="text-success text-decoration-none">
                                    <i class="bi bi-person-plus me-1"></i>Register Here
                                </a>
                                <span class="text-muted mx-2">|</span>
                                <a href="index.php" class="text-dark text-decoration-none">
                                    <i class="bi bi-house me-1"></i>Home
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>