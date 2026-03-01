<?php
/**
 * Session Management
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require user login (for quiz pages)
 */
function require_user_login($redirect_to = 'register.php') {
    if (!check_user_session()) {
        redirect($redirect_to);
    }
}

/**
 * Require admin login (for admin pages)
 */
function require_admin_login($redirect_to = 'index.php') {
    if (!is_admin_logged_in()) {
        redirect($redirect_to);
    }
}

/**
 * Prevent already completed users from retaking quiz
 */
function check_quiz_status() {
    $user = get_user_data();
    if ($user && $user['status'] === 'completed') {
        redirect('results.php');
    }
}

/**
 * Start user quiz (update status)
 */
function start_quiz($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    $query = "UPDATE users SET status = 'in_progress' WHERE id = $user_id";
    return $conn->query($query);
}
?>