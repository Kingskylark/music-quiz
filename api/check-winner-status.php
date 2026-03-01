<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!check_user_session()) {
    json_response(false, 'Unauthorized');
}

$user_id = (int)$_SESSION['user_id'];
$winner_info = is_winner($user_id);

if ($winner_info['is_winner']) {
    $prize_amount = get_prize_amount($winner_info['rank']);
    json_response(true, 'Winner', [
        'is_winner' => true,
        'rank' => $winner_info['rank'],
        'prize_amount' => $prize_amount
    ]);
} else {
    json_response(true, 'Not a winner', [
        'is_winner' => false
    ]);
}
?>
