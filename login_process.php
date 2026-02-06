<?php
// login_process.php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'])) {
    header("Location: login.php?error=Invalid security token");
    exit();
}

$email = mysqli_real_escape_string($connection, trim($_POST['email']));
$password = $_POST['password'];
$remember = isset($_POST['remember']) ? true : false;

// Validate inputs
if (empty($email) || empty($password)) {
    header("Location: login.php?error=Email and password are required");
    exit();
}

// Check if user exists
$query = "SELECT * FROM users WHERE email = '$email' AND is_active = 1";
$result = mysqli_query($connection, $query);

if (mysqli_num_rows($result) === 0) {
    header("Location: login.php?error=Invalid email or password");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    // Update failed login attempts
    $failed_attempts = $user['failed_login_attempts'] + 1;
    $update_query = "UPDATE users SET failed_login_attempts = $failed_attempts, 
                     last_failed_login = NOW() WHERE user_id = {$user['user_id']}";
    mysqli_query($connection, $update_query);
    
    header("Location: login.php?error=Invalid email or password");
    exit();
}

// Reset failed login attempts on successful login
$reset_query = "UPDATE users SET failed_login_attempts = 0, last_login = NOW() 
                WHERE user_id = {$user['user_id']}";
mysqli_query($connection, $reset_query);

// Set session variables
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['user_role'] = 'customer';
$_SESSION['logged_in'] = true;
$_SESSION['LAST_ACTIVITY'] = time();

// Set remember me cookie
if ($remember) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
    
    setcookie('remember_token', $token, $expiry, '/');
    
    // Store token in database
    $token_hash = password_hash($token, PASSWORD_DEFAULT);
    $update_token = "UPDATE users SET remember_token = '$token_hash' 
                     WHERE user_id = {$user['user_id']}";
    mysqli_query($connection, $update_token);
}

// Redirect to original page or home
if (isset($_SESSION['redirect_url'])) {
    $redirect_url = $_SESSION['redirect_url'];
    unset($_SESSION['redirect_url']);
    header("Location: $redirect_url");
} else {
    header("Location: index.php");
}
exit();
?>