<?php
// verify_otp_process.php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['signup_data']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signup.php");
    exit();
}

$user_otp = trim($_POST['otp']);
$stored_otp = $_SESSION['signup_data']['otp'];

if ($user_otp !== $stored_otp) {
    header("Location: verify_otp.php?error=Invalid OTP. Please try again.");
    exit();
}

// OTP verified, create user account
$user_data = $_SESSION['signup_data'];

$query = "INSERT INTO users (
    first_name, last_name, email, password_hash, 
    phone_cell, address_street, address_city, address_country,
    registration_date, is_active
) VALUES (
    '{$user_data['first_name']}', 
    '{$user_data['last_name']}', 
    '{$user_data['email']}', 
    '{$user_data['password']}', 
    '{$user_data['phone_cell']}', 
    '{$user_data['address_street']}', 
    '{$user_data['address_city']}', 
    '{$user_data['address_country']}',
    NOW(), 
    1
)";

if (mysqli_query($connection, $query)) {
    $user_id = mysqli_insert_id($connection);
    
    // Clear signup data from session
    unset($_SESSION['signup_data']);
    
    // Auto-login user
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['LAST_ACTIVITY'] = time();
    
    // Send welcome email
    // (Add welcome email code here)
    
    header("Location: index.php?signup=success");
    exit();
} else {
    error_log("Database error: " . mysqli_error($connection));
    header("Location: signup.php?error=Registration failed. Please try again.");
    exit();
}
?>