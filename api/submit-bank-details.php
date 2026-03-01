<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!check_user_session()) {
    json_response(false, 'Unauthorized access');
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method');
}

$user_id = (int)$_SESSION['user_id'];

// Check if user is a winner
$winner_info = is_winner($user_id);
if (!$winner_info['is_winner']) {
    json_response(false, 'You are not eligible for payment');
}

// Check if payment is enabled
if (!PAYMENT_ENABLED) {
    json_response(false, 'Payment portal is currently disabled');
}

// Check if already submitted
$existing_payment = get_payment_status($user_id);
if ($existing_payment) {
    json_response(false, 'Bank details already submitted');
}

// Validate inputs
$bank_name = clean_input($_POST['bank_name'] ?? '');
$account_number = clean_input($_POST['account_number'] ?? '');
$account_name = clean_input($_POST['account_name'] ?? '');
$rank = (int)($_POST['rank'] ?? 0);
$prize_amount = (float)($_POST['prize_amount'] ?? 0);

if (empty($bank_name) || empty($account_number) || empty($account_name)) {
    json_response(false, 'All fields are required');
}

// Validate account number format
if (!preg_match('/^[0-9]{10}$/', $account_number)) {
    json_response(false, 'Invalid account number format. Must be 10 digits.');
}

// Insert payment record
$query = "INSERT INTO payments (user_id, rank, prize_amount, bank_name, account_number, account_name, payment_status, submitted_at) 
          VALUES ($user_id, $rank, $prize_amount, '$bank_name', '$account_number', '$account_name', 'pending', NOW())";

if ($conn->query($query)) {
    json_response(true, 'Bank details submitted successfully! Payment will be processed within 10-30 minutes.', [
        'payment_id' => $conn->insert_id
    ]);
} else {
    json_response(false, 'Failed to submit bank details. Please try again.');
}
?>