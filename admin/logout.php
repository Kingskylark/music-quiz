<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Destroy admin session
session_start();
session_unset();
session_destroy();

// Redirect to login
redirect('index.php');
?>