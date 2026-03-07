<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Check if user is logged in
if (!check_user_session()) {
    json_response(false, 'Unauthorized access. Please login again.');
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

$user_id = (int)$_SESSION['user_id'];
$question_id = (int)($_POST['question_id'] ?? 0);
$selected_option = clean_input($_POST['selected_option'] ?? '');
$time_taken = (int)($_POST['time_taken'] ?? 0);

// Validation
if ($question_id <= 0) {
    json_response(false, 'Invalid question ID');
}

if (!empty($selected_option) && !in_array($selected_option, ['A', 'B', 'C', 'D'])) {
    json_response(false, 'Invalid option selected');
}

if ($time_taken < 0 || $time_taken > QUIZ_TIME_PER_QUESTION) {
    $time_taken = QUIZ_TIME_PER_QUESTION; // Cap at max time
}

// Get correct answer
$query = "SELECT correct_option FROM questions WHERE id = $question_id LIMIT 1";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    json_response(false, 'Question not found');
}

$question = $result->fetch_assoc();
$correct_option = $question['correct_option'];

// Check if answer is correct
$is_correct = ($selected_option === $correct_option) ? 1 : 0;

// Check if user already answered this question
$check_query = "SELECT id FROM answers WHERE user_id = $user_id AND question_id = $question_id LIMIT 1";
$check_result = $conn->query($check_query);

if ($check_result && $check_result->num_rows > 0) {
    json_response(false, 'You have already answered this question');
}

// Insert answer
$insert_query = "INSERT INTO answers (user_id, question_id, selected_option, is_correct, time_taken) 
                 VALUES ($user_id, $question_id, " . 
                 ($selected_option ? "'$selected_option'" : "NULL") . ", $is_correct, $time_taken)";

if ($conn->query($insert_query)) {
    // Get updated progress
    $progress = get_user_progress($user_id);
    $score = calculate_score($user_id);
    
    // Check if quiz is completed OR if admin stopped the game
    $is_completed = ($progress >= TOTAL_QUESTIONS) || !is_game_active();

    if ($is_completed) {
        // Finalize quiz
        finalize_user_quiz($user_id);
    }
    
    json_response(true, 'Answer submitted', [
        'is_correct' => (bool)$is_correct,
        'correct_option' => $correct_option,
        'progress' => $progress,
        'score' => $score,
        'completed' => $is_completed
    ]);
} else {
    json_response(false, 'Failed to submit answer: ' . $conn->error);
}
?>