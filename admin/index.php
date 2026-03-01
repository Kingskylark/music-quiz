<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// If already logged in, redirect to dashboard
if (is_admin_logged_in()) {
    redirect('dashboard.php');
}

// Handle login form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check credentials
        $query = "SELECT * FROM admin WHERE username = '$username' LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Set session
                $_SESSION[ADMIN_SESSION_NAME] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                
                $success = 'Login successful! Redirecting...';
                header("refresh:1;url=dashboard.php");
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$page_title = "Admin Login";
$css_path = '../assets/css/custom.css';
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-login-page min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <!-- Login Card -->
                <div class="card shadow-lg border-0 admin-card">
                    <div class="card-body p-5">
                        <!-- Logo/Icon -->
                        <div class="text-center mb-4">
                            <div class="admin-icon-wrapper">
                                <i class="bi bi-shield-lock-fill display-3 text-success"></i>
                            </div>
                            <h3 class="mt-3 fw-bold text-white">Admin Portal</h3>
                            <p class="text-light small">Music Quiz Management</p>
                        </div>
                        
                        <!-- Error/Success Messages -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle-fill me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form method="POST" action="" id="loginForm">
                            <div class="mb-4">
                                <label for="username" class="form-label text-light">
                                    <i class="bi bi-person-fill me-2"></i>Username
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg" 
                                    id="username" 
                                    name="username" 
                                    placeholder="Enter username"
                                    required
                                    autocomplete="username"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                >
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label text-light">
                                    <i class="bi bi-lock-fill me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <input 
                                        type="password" 
                                        class="form-control form-control-lg" 
                                        id="password" 
                                        name="password" 
                                        placeholder="Enter password"
                                        required
                                        autocomplete="current-password"
                                    >
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <a href="../index.php" class="text-light text-decoration-none small">
                                    <i class="bi bi-arrow-left me-1"></i>Back to Quiz
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="../assets/js/admin-login.js"></script>';
include '../includes/footer.php';
?>