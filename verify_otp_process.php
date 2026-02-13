<?php
// verify_otp_process.php
require_once 'config/database.php';
require_once 'config/constants.php';
session_start();

// Check if user has signup data in session
if (!isset($_SESSION['signup_data'])) {
    header("Location: signup.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['verify_otp'])) {
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

// Debug - remove in production
// error_log("User OTP: '$user_otp', Stored OTP: '$stored_otp', Type: " . gettype($stored_otp));

// Check if OTP expired
if (time() > $otp_expiry) {
    unset($_SESSION['signup_data']);
    header("Location: signup.php?error=OTP expired. Please register again.");
    exit();
}

// FIX: Convert both to strings and trim for comparison
if (trim((string)$user_otp) !== trim((string)$stored_otp)) {
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
mysqli_stmt_bind_param(
    $stmt, 
    "ssssssss", 
    $user_data['first_name'],
    $user_data['last_name'],
    $user_data['email'],
    $user_data['password'], // This should already be hashed from signup
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
    
    // Optional: Send welcome email using PHPMailer
    /*
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_USER, SITE_NAME);
        $mail->addAddress($user_data['email'], $user_data['first_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Welcome to " . SITE_NAME;
        $mail->Body    = "<h1>Welcome " . $user_data['first_name'] . "!</h1><p>Thank you for registering.</p>";
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Welcome email failed: " . $mail->ErrorInfo);
    }
    */
    
    header("Location: index.php?signup=success");
    exit();
    
} else {
    error_log("Database error in signup: " . mysqli_error($connection));
    
    // Check if email already exists
    if (mysqli_errno($connection) == 1062) { // Duplicate entry error
        header("Location: signup.php?error=Email already registered. Please login.");
    } else {
        header("Location: signup.php?error=Registration failed. Please try again.");
    }
    exit();
}
?>