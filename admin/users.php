<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Require admin login
require_admin_login();

// Handle AJAX delete requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'delete_user' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete user's answers first
            $delete_answers = "DELETE FROM answers WHERE user_id = $user_id";
            $conn->query($delete_answers);
            
            // Delete user
            $delete_user = "DELETE FROM users WHERE id = $user_id";
            if ($conn->query($delete_user) && $conn->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                throw new Exception('User not found');
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete_all') {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete all answers first
            $conn->query("DELETE FROM answers");
            
            // Delete all users
            $result = $conn->query("DELETE FROM users");
            $deleted_count = $conn->affected_rows;
            
            // Reset auto increment
            $conn->query("ALTER TABLE users AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE answers AUTO_INCREMENT = 1");
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Deleted $deleted_count users successfully"]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

$page_title = "Manage Users";
$css_path = '../assets/css/custom.css';

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// Build query based on filter
$where_clause = '';
if ($status_filter !== 'all') {
    $status_filter = clean_input($status_filter);
    $where_clause = "WHERE status = '$status_filter'";
}

// Get all users
$users_query = "SELECT * FROM users $where_clause ORDER BY registered_at DESC";
$users_result = $conn->query($users_query);

// Get counts for each status
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$not_started = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'not_started'")->fetch_assoc()['count'];
$in_progress = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'in_progress'")->fetch_assoc()['count'];
$completed = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'completed'")->fetch_assoc()['count'];
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
                    <li class="nav-item"><a class="nav-link" href="winners.php"><i class="bi bi-trophy-fill me-1"></i>Winners</a></li>
                    <li class="nav-item"><a class="nav-link" href="questions.php"><i class="bi bi-question-circle me-1"></i>Questions</a></li>
                    <li class="nav-item"><a class="nav-link active" href="users.php"><i class="bi bi-people me-1"></i>Users</a></li>
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
            <div class="col-md-6">
                <h3 class="text-white">
                    <i class="bi bi-people-fill text-success me-2"></i>
                    User Management
                </h3>
                <p class="text-light">View and manage all registered users</p>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-danger" onclick="deleteAllUsers()">
                    <i class="bi bi-trash-fill me-2"></i>Delete All Users
                </button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <a href="users.php?status=all" class="text-decoration-none">
                    <div class="card stat-card bg-dark border-white <?php echo $status_filter === 'all' ? 'border-3' : ''; ?>">
                        <div class="card-body text-center">
                            <i class="bi bi-people-fill display-6 text-white mb-2"></i>
                            <h3 class="fw-bold text-white mb-0"><?php echo $total_users; ?></h3>
                            <p class="text-light mb-0 small">Total Users</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="users.php?status=not_started" class="text-decoration-none">
                    <div class="card stat-card bg-dark border-secondary <?php echo $status_filter === 'not_started' ? 'border-3' : ''; ?>">
                        <div class="card-body text-center">
                            <i class="bi bi-hourglass-split display-6 text-secondary mb-2"></i>
                            <h3 class="fw-bold text-white mb-0"><?php echo $not_started; ?></h3>
                            <p class="text-light mb-0 small">Not Started</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="users.php?status=in_progress" class="text-decoration-none">
                    <div class="card stat-card bg-dark border-warning <?php echo $status_filter === 'in_progress' ? 'border-3' : ''; ?>">
                        <div class="card-body text-center">
                            <i class="bi bi-clock-history display-6 text-warning mb-2"></i>
                            <h3 class="fw-bold text-white mb-0"><?php echo $in_progress; ?></h3>
                            <p class="text-light mb-0 small">In Progress</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <a href="users.php?status=completed" class="text-decoration-none">
                    <div class="card stat-card bg-dark border-success <?php echo $status_filter === 'completed' ? 'border-3' : ''; ?>">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle-fill display-6 text-success mb-2"></i>
                            <h3 class="fw-bold text-white mb-0"><?php echo $completed; ?></h3>
                            <p class="text-light mb-0 small">Completed</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="row">
            <div class="col-12">
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-secondary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-list-ul me-2"></i>
                            <?php 
                            $filter_names = [
                                'all' => 'All Users',
                                'not_started' => 'Not Started Users',
                                'in_progress' => 'In Progress Users',
                                'completed' => 'Completed Users'
                            ];
                            echo $filter_names[$status_filter];
                            ?>
                            (<?php echo $users_result->num_rows; ?>)
                        </h5>
                        <?php if ($status_filter !== 'all'): ?>
                            <a href="users.php" class="btn btn-sm btn-light">
                                <i class="bi bi-arrow-left me-1"></i>View All
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="20%">Name</th>
                                        <th width="15%">Nickname</th>
                                        <th width="12%">Status</th>
                                        <th width="8%">Score</th>
                                        <th width="10%">Time</th>
                                        <th width="15%">Registered</th>
                                        <th width="10%">IP Address</th>
                                        <th width="5%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                                        <?php while ($user = $users_result->fetch_assoc()): ?>
                                            <tr id="user-<?php echo $user['id']; ?>">
                                                <td><?php echo $user['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo $user['nickname'] ? htmlspecialchars($user['nickname']) : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_badges = [
                                                        'not_started' => '<span class="badge bg-secondary">Not Started</span>',
                                                        'in_progress' => '<span class="badge bg-warning">In Progress</span>',
                                                        'completed' => '<span class="badge bg-success">Completed</span>'
                                                    ];
                                                    echo $status_badges[$user['status']];
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($user['status'] === 'completed') {
                                                        echo '<strong class="text-success">' . $user['score'] . ' / 20</strong>';
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($user['status'] === 'completed' && $user['total_time'] > 0) {
                                                        echo format_time($user['total_time']);
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y H:i', strtotime($user['registered_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['ip_address']); ?></small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="deleteUser(<?php echo $user['id']; ?>)"
                                                            title="Delete User">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                <p class="mb-0">No users found with this filter</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Delete single user
function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone!')) {
        return;
    }
    
    $.ajax({
        url: 'users.php',
        type: 'POST',
        data: {
            action: 'delete_user',
            user_id: userId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Remove row from table
                $('#user-' + userId).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if table is empty
                    if ($('#usersTableBody tr').length === 0) {
                        location.reload();
                    }
                });
                
                showAlert('success', response.message);
                
                // Reload after 1.5 seconds to update counts
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showAlert('danger', 'Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete error:', error);
            showAlert('danger', 'Failed to delete user');
        }
    });
}

// Delete all users
function deleteAllUsers() {
    if (!confirm('⚠️ WARNING: This will delete ALL users and their data!\n\nAre you absolutely sure?')) {
        return;
    }
    
    if (!confirm('This is your LAST CHANCE! All user data will be permanently deleted. Continue?')) {
        return;
    }
    
    $.ajax({
        url: 'users.php',
        type: 'POST',
        data: {
            action: 'delete_all'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                
                // Reload page after 1.5 seconds
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showAlert('danger', 'Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete all error:', error);
            showAlert('danger', 'Failed to delete users');
        }
    });
}

// Show alert helper
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
             style="z-index: 9999; min-width: 300px;" role="alert">
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto-remove after 3 seconds
    setTimeout(function() {
        $('.alert').fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}
</script>

<?php include '../includes/footer.php'; ?>