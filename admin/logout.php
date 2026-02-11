<?php
// admin/logout.php - Admin logout

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database for logging if needed
require_once '../config/database.php';

// Log the logout action
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
    $admin_name = $_SESSION['admin_name'] ?? 'Admin';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // You can log the logout action here if you have an audit table
    // $log_query = "INSERT INTO audit_logs (admin_id, action_type, ip_address) 
    //               VALUES ($admin_id, 'Admin Logout', '$ip_address')";
    // mysqli_query($connection, $log_query);
}

// Store admin name for message
$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Clear all admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_role']);
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_last_login']);

// If you want to keep user session (if user is also logged in as customer)
// Keep the regular user session variables if they exist

// Optional: Destroy the entire session if you want complete logout
// session_destroy();

// Redirect to login page with message
header("Location: login.php?logout=1&name=" . urlencode($admin_name));
exit();
?>