<?php
/**
 * Database Configuration
 * Update these values according to your server setup
 */
// Payment settings (CHANGE THESE!)
define('PAYMENT_ENABLED', true); // Set to false to disable payments
define('PAYMENT_GATEWAY', 'paystack'); // 'paystack' or 'flutterwave'

// Prize amounts (in NGN)
define('FIRST_PRIZE', 10000);    // ₦10,000
define('SECOND_PRIZE', 7000);   // ₦7,000
define('THIRD_PRIZE', 5000);    // ₦5,000

// Paystack Configuration (Get from https://dashboard.paystack.com/#/settings/developers)
define('PAYSTACK_SECRET_KEY', 'sk_test_77462f4cc81a3c78565d15dd295580d5b94a342e'); // CHANGE THIS!
define('PAYSTACK_PUBLIC_KEY', 'pk_test_3829b3251f0c9f7632f43a9d509837286f718c0d'); // CHANGE THIS!

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

/**
 * Error Reporting (Disable in production)
 */
// For development
error_reporting(0);
ini_set('display_errors', 0);

// For production (uncomment these)
// error_reporting(0);
// ini_set('display_errors', 0);
?>