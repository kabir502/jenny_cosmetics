<?php
// // config/database.php
// session_start();

// // Database configuration
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'jenny_cosmetics_db');

// // Create connection
// $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// // Check connection
// if (!$connection) {
//     die("Connection failed: " . mysqli_connect_error());
// }

// // Set charset
// mysqli_set_charset($connection, "utf8mb4");

// // Error reporting
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// config/database.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include constants first
require_once __DIR__ . '/constants.php';

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Your password here
$database = 'jenny_cosmetics_db';

// Create connection
$connection = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$connection) {
    // Log error instead of displaying sensitive information
    error_log("Database connection failed: " . mysqli_connect_error());
    
    // Show user-friendly message
    if (DEBUG_MODE) {
        die("Database connection failed: " . mysqli_connect_error());
    } else {
        die("We're experiencing technical difficulties. Please try again later.");
    }
}

// Set charset
mysqli_set_charset($connection, "utf8mb4");

// Set timezone
date_default_timezone_set('UTC');

// Function to check if database is properly set up
function checkDatabaseSetup($connection) {
    $required_tables = ['users', 'products', 'categories', 'orders'];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        $result = mysqli_query($connection, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) == 0) {
            $missing_tables[] = $table;
        }
    }
    
    return $missing_tables;
}

// Optional: Check database setup
if (DEBUG_MODE) {
    $missing_tables = checkDatabaseSetup($connection);
    if (!empty($missing_tables)) {
        error_log("Missing database tables: " . implode(', ', $missing_tables));
    }
}
?>