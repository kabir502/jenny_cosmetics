<?php
// login_process.php - Login Processing

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'config/constants.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header("Location: login.php");
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header("Location: login.php");
    exit();
}

// Get form data
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) ? true : false;

// Validate inputs
if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email and password are required.';
    header("Location: login.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    header("Location: login.php");
    exit();
}

// Prepare SQL statement
$query = "SELECT user_id, first_name, last_name, email, password_hash, is_active 
          FROM users 
          WHERE email = ? AND is_active = 1 
          LIMIT 1";

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = 'Invalid email or password.';
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['error'] = 'Invalid email or password.';
    header("Location: login.php");
    exit();
}

// Regenerate session ID for security
session_regenerate_id(true);

// Set session variables
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();

// Update last login
$update_query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
$update_stmt = mysqli_prepare($connection, $update_query);
mysqli_stmt_bind_param($update_stmt, "i", $user['user_id']);
mysqli_stmt_execute($update_stmt);

// Clear CSRF token
unset($_SESSION['csrf_token']);

// Redirect to original page or home
if (isset($_SESSION['redirect_url'])) {
    $redirect_url = $_SESSION['redirect_url'];
    unset($_SESSION['redirect_url']);
    header("Location: " . $redirect_url);
} else {
    $_SESSION['success'] = 'Welcome back, ' . $user['first_name'] . '!';
    header("Location: index.php");
}
exit();
?>