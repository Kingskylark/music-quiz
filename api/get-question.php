<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, we'll return JSON
ini_set('log_errors', 1);

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Check if user is logged in
if (!check_user_session()) {
    json_response(false, 'Unauthorized: No session found');
}

$user_id = (int)$_SESSION['user_id'];

// Verify user exists in database
$user_check_query = "SELECT id, status FROM users WHERE id = $user_id LIMIT 1";
$user_check_result = $conn->query($user_check_query);

if (!$user_check_result || $user_check_result->num_rows === 0) {
    json_response(false, 'User not found in database');
}

$user_data = $user_check_result->fetch_assoc();

// Check user status
if ($user_data['status'] === 'completed') {
    json_response(false, 'Quiz already completed', ['completed' => true]);
}

// Get total questions count
$total_questions_query = "SELECT COUNT(*) as total FROM questions";
$total_result = $conn->query($total_questions_query);
$total_questions = $total_result->fetch_assoc()['total'];

if ($total_questions < TOTAL_QUESTIONS) {
    json_response(false, "Not enough questions. Found: $total_questions, Required: " . TOTAL_QUESTIONS);
}

// Get user's progress
$progress = get_user_progress($user_id);

if ($progress >= TOTAL_QUESTIONS) {
    json_response(false, 'All questions answered', ['completed' => true]);
}

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

if (!$result) {
    json_response(false, 'Database error: ' . $conn->error);
}

if ($result->num_rows === 0) {
    json_response(false, 'No unanswered questions found', ['completed' => true]);
}

$question = $result->fetch_assoc();

// Remove correct_option from response
$response_data = [
    'id' => (int)$question['id'],
    'question_text' => $question['question_text'],
    'option_a' => $question['option_a'],
    'option_b' => $question['option_b'],
    'option_c' => $question['option_c'],
    'option_d' => $question['option_d'],
    'category' => $question['category'],
    'progress' => $progress + 1,
    'total' => TOTAL_QUESTIONS
];

json_response(true, 'Question loaded successfully', $response_data);
?>