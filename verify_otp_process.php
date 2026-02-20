<?php
// verify_otp_process.php - Process OTP Verification

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and constants
require_once 'config/database.php';
require_once 'config/constants.php';

// Check if user has signup data in session
if (!isset($_SESSION['signup_data'])) {
    header("Location: signup.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: verify_otp.php");
    exit();
}

// Get and sanitize OTP
$user_otp = trim($_POST['otp'] ?? '');

// Validate OTP format (must be 6 digits)
if (!preg_match('/^\d{6}$/', $user_otp)) {
    header("Location: verify_otp.php?error=Please enter a valid 6-digit OTP.");
    exit();
}

// Get stored OTP from session
$stored_otp = $_SESSION['signup_data']['otp'] ?? '';
$otp_expiry = $_SESSION['signup_data']['otp_expiry'] ?? 0;

// Check if OTP expired
if (time() > $otp_expiry) {
    unset($_SESSION['signup_data']);
    header("Location: signup.php?error=OTP expired. Please register again.");
    exit();
}

// CRITICAL FIX: Convert to string and compare exactly
// Sometimes the OTP is stored as integer vs string
$user_otp_str = (string)$user_otp;
$stored_otp_str = (string)$stored_otp;

// Debug logging (remove in production)
error_log("User OTP: '$user_otp_str' (type: " . gettype($user_otp_str) . ")");
error_log("Stored OTP: '$stored_otp_str' (type: " . gettype($stored_otp_str) . ")");

// Simple string comparison
if ($user_otp_str !== $stored_otp_str) {
    error_log("OTP mismatch");
    header("Location: verify_otp.php?error=Invalid OTP. Please try again.");
    exit();
}

// OTP verified successfully, create user account
$user_data = $_SESSION['signup_data'];

// Use prepared statement to prevent SQL injection
$query = "INSERT INTO users (
    first_name, last_name, email, password_hash, 
    phone_cell, address_street, address_city, address_country,
    registration_date, is_active
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)";

$stmt = mysqli_prepare($connection, $query);

if (!$stmt) {
    error_log("Prepare failed: " . mysqli_error($connection));
    header("Location: signup.php?error=Registration failed. Please try again.");
    exit();
}

mysqli_stmt_bind_param(
    $stmt, 
    "ssssssss", 
    $user_data['first_name'],
    $user_data['last_name'],
    $user_data['email'],
    $user_data['password'],
    $user_data['phone_cell'],
    $user_data['address_street'],
    $user_data['address_city'],
    $user_data['address_country']
);

if (mysqli_stmt_execute($stmt)) {
    $user_id = mysqli_insert_id($connection);
    
    // Clear signup data from session
    unset($_SESSION['signup_data']);
    
    // Auto-login user
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['full_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
    $_SESSION['first_name'] = $user_data['first_name'];
    $_SESSION['last_name'] = $user_data['last_name'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Redirect to home with success message
    header("Location: index.php?signup=success");
    exit();
    
} else {
    error_log("Database error in signup: " . mysqli_error($connection));
    
    // Check if email already exists
    if (mysqli_errno($connection) == 1062) {
        header("Location: signup.php?error=Email already registered. Please login.");
    } else {
        header("Location: signup.php?error=Registration failed. Please try again.");
    }
    exit();
}
?>