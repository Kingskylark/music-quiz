<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

// Validate inputs
$name = clean_input($_POST['name'] ?? '');
$nickname = clean_input($_POST['nickname'] ?? '');

// Nickname validation (now required)
if (empty($nickname)) {
    json_response(false, 'Username/Nickname is required');
}

if (strlen($nickname) < 3) {
    json_response(false, 'Username must be at least 3 characters long');
}

if (strlen($nickname) > 50) {
    json_response(false, 'Username is too long (max 50 characters)');
}

// Name validation (use nickname if empty)
if (empty($name)) {
    $name = $nickname;
}

if (strlen($name) > 100) {
    json_response(false, 'Name is too long (max 100 characters)');
}

// Check if nickname already exists (MUST BE UNIQUE)
$check_nickname_query = "SELECT id FROM users WHERE nickname = '$nickname' LIMIT 1";
$check_nickname_result = $conn->query($check_nickname_query);

if ($check_nickname_result && $check_nickname_result->num_rows > 0) {
    json_response(false, 'This username is already taken. Please choose another one.');
}

// Check if enough questions exist
$questions_count = get_questions_count();
if ($questions_count < TOTAL_QUESTIONS) {
    json_response(false, "Quiz is not ready yet. Only $questions_count questions available.");
}

// Generate session ID
$session_id = generate_session_id();
$ip_address = get_ip_address();

// Insert user into database
$query = "INSERT INTO users (name, nickname, session_id, ip_address, status) 
          VALUES ('$name', '$nickname', '$session_id', '$ip_address', 'not_started')";

if ($conn->query($query)) {
    $user_id = $conn->insert_id;
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['session_id'] = $session_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_nickname'] = $nickname;
    
    json_response(true, 'Registration successful!', [
        'user_id' => $user_id,
        'redirect' => 'quiz.php'
    ]);
} else {
    json_response(false, 'Registration failed. Please try again. Error: ' . $conn->error);
}
?>