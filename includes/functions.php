<?php
/**
 * Reusable Functions
 */

/**
 * Sanitize user input
 */
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}

/**
 * Generate unique session ID for user
 */
function generate_session_id() {
    return bin2hex(random_bytes(16)) . '_' . time();
}

/**
 * Get user's IP address
 */
function get_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Check if user session exists and is valid (matches database)
 */
function check_user_session() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
        return false;
    }

    // Validate session_id against database to prevent shared/stale sessions
    global $conn;
    $user_id = (int)$_SESSION['user_id'];
    $session_id = $conn->real_escape_string($_SESSION['session_id']);
    $result = $conn->query("SELECT id FROM users WHERE id = $user_id AND session_id = '$session_id' LIMIT 1");

    if (!$result || $result->num_rows === 0) {
        // Session doesn't match DB — another device logged in or session is stale
        unset($_SESSION['user_id'], $_SESSION['session_id'], $_SESSION['user_name'], $_SESSION['user_nickname']);
        return false;
    }

    return true;
}

/**
 * Get user data from session
 */
/**
 * Get user data from session
 */
function get_user_data() {
    global $conn;
    
    if (!check_user_session()) {
        return null; // Return null instead of false
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = $user_id LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null; // Return null instead of false
}

/**
 * Check if admin is logged in
 */
function is_admin_logged_in() {
    return isset($_SESSION[ADMIN_SESSION_NAME]) && $_SESSION[ADMIN_SESSION_NAME] === true;
}

/**
 * Redirect function
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Get total questions count
 */
function get_questions_count() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM questions";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return (int)$row['total'];
}

/**
 * Get random questions (excluding already answered)
 */
function get_next_question($user_id) {
    global $conn;
    
    // Get already answered question IDs
    $answered_query = "SELECT question_id FROM answers WHERE user_id = $user_id";
    $answered_result = $conn->query($answered_query);
    
    $answered_ids = [];
    while ($row = $answered_result->fetch_assoc()) {
        $answered_ids[] = $row['question_id'];
    }
    
    // Build query to get unanswered question
    if (count($answered_ids) > 0) {
        $ids_string = implode(',', $answered_ids);
        $query = "SELECT * FROM questions WHERE id NOT IN ($ids_string) ORDER BY RAND() LIMIT 1";
    } else {
        $query = "SELECT * FROM questions ORDER BY RAND() LIMIT 1";
    }
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Calculate user's current progress
 */
function get_user_progress($user_id) {
    global $conn;
    $query = "SELECT COUNT(*) as answered FROM answers WHERE user_id = $user_id";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return (int)$row['answered'];
}

/**
 * Calculate user's score
 */
function calculate_score($user_id) {
    global $conn;
    $query = "SELECT COUNT(*) as correct FROM answers WHERE user_id = $user_id AND is_correct = 1";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return (int)$row['correct'];
}

/**
 * Update user's final score and status
 */
function finalize_user_quiz($user_id) {
    global $conn;
    
    $score = calculate_score($user_id);
    
    // Calculate total time
    $time_query = "SELECT SUM(time_taken) as total_time FROM answers WHERE user_id = $user_id";
    $time_result = $conn->query($time_query);
    $time_row = $time_result->fetch_assoc();
    $total_time = (int)$time_row['total_time'];
    
    // Update user
    $update_query = "UPDATE users SET score = $score, total_time = $total_time, status = 'completed' WHERE id = $user_id";
    return $conn->query($update_query);
}

/**
 * Get leaderboard data
 */
function get_leaderboard($limit = 10) {
    global $conn;
    
    $limit = (int)$limit;
    $query = "SELECT id, name, nickname, score, total_time, registered_at 
              FROM users 
              WHERE status = 'completed' 
              ORDER BY score DESC, total_time ASC 
              LIMIT $limit";
    
    $result = $conn->query($query);
    $leaderboard = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $leaderboard[] = $row;
        }
    }
    
    return $leaderboard;
}

/**
 * Format time (seconds to MM:SS)
 */
function format_time($seconds) {
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return sprintf("%02d:%02d", $minutes, $seconds);
}

/**
 * Get analytics data for admin
 */
function get_analytics() {
    global $conn;
    
    $analytics = [];
    
    // Total users
    $query = "SELECT COUNT(*) as total FROM users";
    $result = $conn->query($query);
    $analytics['total_users'] = $result->fetch_assoc()['total'];
    
    // Completed users
    $query = "SELECT COUNT(*) as total FROM users WHERE status = 'completed'";
    $result = $conn->query($query);
    $analytics['completed_users'] = $result->fetch_assoc()['total'];
    
    // Average score (FIX: Handle null values)
    $query = "SELECT AVG(score) as avg_score FROM users WHERE status = 'completed'";
    $result = $conn->query($query);
    $avg = $result->fetch_assoc()['avg_score'];
    $analytics['average_score'] = $avg !== null ? round($avg, 2) : 0;
    
    // Total questions
    $analytics['total_questions'] = get_questions_count();
    
    return $analytics;
}

/**
 * JSON response helper
 */
function json_response($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Check if game is active
 */
function is_game_active() {
    global $conn;
    $query = "SELECT setting_value FROM game_settings WHERE setting_key = 'game_active' LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return ($row['setting_value'] === '1');
    }
    
    return false;
}

/**
 * Check if registration is allowed
 */
function is_registration_allowed() {
    global $conn;
    $query = "SELECT setting_value FROM game_settings WHERE setting_key = 'allow_registration' LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return ($row['setting_value'] === '1');
    }
    
    return true; // Default to allow
}

/**
 * Update game setting
 */
function update_game_setting($key, $value) {
    global $conn;
    $key = clean_input($key);
    $value = clean_input($value);
    
    $query = "UPDATE game_settings SET setting_value = '$value' WHERE setting_key = '$key'";
    return $conn->query($query);
}

/**
 * Get game setting
 */
function get_game_setting($key) {
    global $conn;
    $key = clean_input($key);
    
    $query = "SELECT setting_value FROM game_settings WHERE setting_key = '$key' LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    
    return null;
}

/**
 * Check if user is a winner
 */
function is_winner($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    
    $query = "SELECT is_winner, prize_rank FROM users WHERE id = $user_id LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'is_winner' => (bool)$row['is_winner'],
            'rank' => (int)$row['prize_rank']
        ];
    }
    
    return ['is_winner' => false, 'rank' => null];
}

/**
 * Get prize amount by rank
 */
function get_prize_amount($rank) {
    switch ((int)$rank) {
        case 1: return FIRST_PRIZE;
        case 2: return SECOND_PRIZE;
        case 3: return THIRD_PRIZE;
        default: return 0;
    }
}

/**
 * Get user payment status
 */
function get_payment_status($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    
    $query = "SELECT * FROM payments WHERE user_id = $user_id LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get Nigerian banks list
 */
function get_nigerian_banks() {
    return [
        'Access Bank',
        'Citibank Nigeria',
        'Ecobank Nigeria',
        'Fidelity Bank',
        'First Bank of Nigeria',
        'First City Monument Bank (FCMB)',
        'Guaranty Trust Bank (GTBank)',
        'Heritage Bank',
        'Keystone Bank',
        'Polaris Bank',
        'Providus Bank',
        'Stanbic IBTC Bank',
        'Standard Chartered Bank',
        'Sterling Bank',
        'Union Bank of Nigeria',
        'United Bank for Africa (UBA)',
        'Unity Bank',
        'Wema Bank',
        'Zenith Bank',
        'Kuda Bank',
        'Moniepoint',
        'OPay',
        'PalmPay',
        'Paystack',
        'VFD Microfinance Bank'
    ];
}

/**
 * Auto-mark top 3 winners if game is stopped and no winners exist
 */
function auto_mark_winners_if_needed() {
    global $conn;

    // Only run if game is stopped
    if (is_game_active()) {
        return false;
    }

    // Check if winners already exist
    $check = $conn->query("SELECT COUNT(*) as c FROM users WHERE is_winner = 1");
    if (!$check) return false;
    $count = $check->fetch_assoc()['c'];
    if ($count > 0) return false;

    // Check if there are completed users
    $completed = $conn->query("SELECT COUNT(*) as c FROM users WHERE status = 'completed'");
    if (!$completed) return false;
    if ($completed->fetch_assoc()['c'] == 0) return false;

    // Mark top 3
    $top3 = $conn->query("SELECT id FROM users WHERE status = 'completed' ORDER BY score DESC, total_time ASC LIMIT 3");
    if (!$top3) return false;

    $rank = 1;
    while ($row = $top3->fetch_assoc()) {
        $conn->query("UPDATE users SET is_winner = 1, prize_rank = $rank WHERE id = {$row['id']}");
        $rank++;
    }

    return true;
}
?>