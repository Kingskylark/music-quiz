<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!check_user_session()) {
    json_response(false, 'Unauthorized');
}

$user_id = (int)$_SESSION['user_id'];

// Check user is currently in_progress
$user = get_user_data();
if (!$user || $user['status'] !== 'in_progress') {
    json_response(false, 'User is not currently playing');
}

// Auto-submit wrong answers for all remaining questions
$progress = get_user_progress($user_id);
$remaining = TOTAL_QUESTIONS - $progress;

if ($remaining > 0) {
    // Get unanswered questions
    $answered_ids = [];
    $ans_q = $conn->query("SELECT question_id FROM answers WHERE user_id = $user_id");
    while ($row = $ans_q->fetch_assoc()) {
        $answered_ids[] = $row['question_id'];
    }

    if (count($answered_ids) > 0) {
        $ids_str = implode(',', $answered_ids);
        $q = "SELECT id FROM questions WHERE id NOT IN ($ids_str) LIMIT $remaining";
    } else {
        $q = "SELECT id FROM questions LIMIT $remaining";
    }

    $questions = $conn->query($q);
    if ($questions) {
        while ($qrow = $questions->fetch_assoc()) {
            $qid = (int)$qrow['id'];
            $conn->query("INSERT INTO answers (user_id, question_id, selected_option, is_correct, time_taken)
                          VALUES ($user_id, $qid, '', 0, 0)");
        }
    }
}

// Finalize the quiz
finalize_user_quiz($user_id);

// Invalidate session
$conn->query("UPDATE users SET session_id = '' WHERE id = $user_id");
unset($_SESSION['user_id'], $_SESSION['session_id']);

json_response(true, 'User disqualified for leaving the quiz');
