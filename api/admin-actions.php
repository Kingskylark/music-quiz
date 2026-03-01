<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Require admin login
if (!is_admin_logged_in()) {
    json_response(false, 'Unauthorized access');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_question':
        addQuestion();
        break;
    
    case 'edit_question':
        editQuestion();
        break;
    
    case 'delete_question':
        deleteQuestion();
        break;
    
    default:
        json_response(false, 'Invalid action');
}

/**
 * Add new question
 */
function addQuestion() {
    global $conn;
    
    // Check if limit reached
    if (get_questions_count() >= TOTAL_QUESTIONS) {
        json_response(false, 'Question bank is full! Delete a question to add a new one.');
    }
    
    // Validate inputs
    $question_text = clean_input($_POST['question_text'] ?? '');
    $option_a = clean_input($_POST['option_a'] ?? '');
    $option_b = clean_input($_POST['option_b'] ?? '');
    $option_c = clean_input($_POST['option_c'] ?? '');
    $option_d = clean_input($_POST['option_d'] ?? '');
    $correct_option = clean_input($_POST['correct_option'] ?? '');
    $category = clean_input($_POST['category'] ?? 'General');
    
    if (empty($question_text) || empty($option_a) || empty($option_b) || 
        empty($option_c) || empty($option_d) || empty($correct_option)) {
        json_response(false, 'All fields are required');
    }
    
    if (!in_array($correct_option, ['A', 'B', 'C', 'D'])) {
        json_response(false, 'Invalid correct option');
    }
    
    // Insert question
    $query = "INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_option, category) 
              VALUES ('$question_text', '$option_a', '$option_b', '$option_c', '$option_d', '$correct_option', '$category')";
    
    if ($conn->query($query)) {
        json_response(true, 'Question added successfully!', [
            'question_id' => $conn->insert_id,
            'questions_count' => get_questions_count()
        ]);
    } else {
        json_response(false, 'Failed to add question: ' . $conn->error);
    }
}

/**
 * Edit existing question
 */
function editQuestion() {
    global $conn;
    
    $question_id = (int)($_POST['question_id'] ?? 0);
    
    if ($question_id <= 0) {
        json_response(false, 'Invalid question ID');
    }
    
    // Validate inputs
    $question_text = clean_input($_POST['question_text'] ?? '');
    $option_a = clean_input($_POST['option_a'] ?? '');
    $option_b = clean_input($_POST['option_b'] ?? '');
    $option_c = clean_input($_POST['option_c'] ?? '');
    $option_d = clean_input($_POST['option_d'] ?? '');
    $correct_option = clean_input($_POST['correct_option'] ?? '');
    $category = clean_input($_POST['category'] ?? 'General');
    
    if (empty($question_text) || empty($option_a) || empty($option_b) || 
        empty($option_c) || empty($option_d) || empty($correct_option)) {
        json_response(false, 'All fields are required');
    }
    
    if (!in_array($correct_option, ['A', 'B', 'C', 'D'])) {
        json_response(false, 'Invalid correct option');
    }
    
    // Update question
    $query = "UPDATE questions SET 
              question_text = '$question_text',
              option_a = '$option_a',
              option_b = '$option_b',
              option_c = '$option_c',
              option_d = '$option_d',
              correct_option = '$correct_option',
              category = '$category'
              WHERE id = $question_id";
    
    if ($conn->query($query)) {
        json_response(true, 'Question updated successfully!');
    } else {
        json_response(false, 'Failed to update question: ' . $conn->error);
    }
}

/**
 * Delete question
 */
function deleteQuestion() {
    global $conn;
    
    $question_id = (int)($_POST['question_id'] ?? 0);
    
    if ($question_id <= 0) {
        json_response(false, 'Invalid question ID');
    }
    
    // Delete question
    $query = "DELETE FROM questions WHERE id = $question_id";
    
    if ($conn->query($query)) {
        json_response(true, 'Question deleted successfully!', [
            'questions_count' => get_questions_count()
        ]);
    } else {
        json_response(false, 'Failed to delete question: ' . $conn->error);
    }
}
?>