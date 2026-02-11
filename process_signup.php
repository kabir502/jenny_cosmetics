<?php
// process_signup.php
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signup.php");
    exit();
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'])) {
    header("Location: signup.php?error=Invalid security token");
    exit();
}

// Sanitize inputs
$first_name = mysqli_real_escape_string($connection, trim($_POST['first_name']));
$last_name = mysqli_real_escape_string($connection, trim($_POST['last_name']));
$email = mysqli_real_escape_string($connection, trim($_POST['email']));
$phone_cell = mysqli_real_escape_string($connection, trim($_POST['phone_cell']));
$password = $_POST['password'];
$address_street = mysqli_real_escape_string($connection, trim($_POST['address_street']));
$address_city = mysqli_real_escape_string($connection, trim($_POST['address_city']));
$address_country = mysqli_real_escape_string($connection, trim($_POST['address_country']));

// Validate inputs
if (empty($first_name) || empty($last_name) || empty($email) || empty($phone_cell) || empty($password)) {
    header("Location: signup.php?error=All required fields must be filled");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: signup.php?error=Invalid email format");
    exit();
}

if (strlen($password) < 8) {
    header("Location: signup.php?error=Password must be at least 8 characters");
    exit();
}

// Check if email already exists
$check_email_query = "SELECT user_id FROM users WHERE email = '$email'";
$check_email_result = mysqli_query($connection, $check_email_query);

if (mysqli_num_rows($check_email_result) > 0) {
    header("Location: signup.php?error=Email already registered");
    exit();
}

// Generate OTP
$otp = rand(100000, 999999);

// Store data in session for verification
$_SESSION['signup_data'] = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'phone_cell' => $phone_cell,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'address_street' => $address_street,
    'address_city' => $address_city,
    'address_country' => $address_country,
    'otp' => $otp,
    'otp_expiry' => time() + 300 // 5 minutes expiry
];

// Send OTP via email (using PHPMailer)
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;

    // Recipients
    $mail->setFrom(SMTP_USER, SITE_NAME);
    $mail->addAddress($email, $first_name . ' ' . $last_name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP for ' . SITE_NAME;
    $mail->Body = "
        <h2>Email Verification</h2>
        <p>Dear $first_name,</p>
        <p>Your OTP for registration is: <strong>$otp</strong></p>
        <p>This OTP will expire in 5 minutes.</p>
        <p>If you didn't request this, please ignore this email.</p>
        <br>
        <p>Best regards,<br>" . SITE_NAME . "</p>
    ";

    $mail->send();
    
    // Redirect to OTP verification page
    header("Location: verify_otp.php");
    exit();
    
} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
    header("Location: signup.php?error=Failed to send OTP. Please try again.");
    exit();
}
?>