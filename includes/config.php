<?php
// Error reporting FIRST — before anything else
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Database Configuration
 * Update these values according to your server setup
 */
// Payment settings
define('PAYMENT_ENABLED', true);

// Prize amounts (in NGN)
define('FIRST_PRIZE', 10000);    // ₦10,000
define('SECOND_PRIZE', 7500);   // ₦7,500
define('THIRD_PRIZE', 5000);    // ₦5,000

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'oghepmgn_id_rsa');           // Change this to your MySQL username
define('DB_PASS', 'OCFoundation1');               // Change this to your MySQL password
define('DB_NAME', 'oghepmgn_musicquiz');

// Application settings
define('QUIZ_TIME_PER_QUESTION', 20); // seconds
define('TOTAL_QUESTIONS', 20);
define('POINTS_PER_CORRECT', 1);

// Admin settings
define('ADMIN_SESSION_NAME', 'music_quiz_admin');

// Timezone
date_default_timezone_set('Africa/Lagos'); // Change to your timezone

/**
 * Database Connection
 */
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for emoji support
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Error reporting is set at the top of this file
?>