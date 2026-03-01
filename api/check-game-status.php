<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$game_active = is_game_active();

header('Content-Type: application/json');
echo json_encode([
    'game_active' => $game_active,
    'timestamp' => time()
]);
?>