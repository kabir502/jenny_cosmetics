<?php
// resend_otp.php - Resend OTP Code

// Start session
session_start();

// Include database and constants
require_once 'config/database.php';
require_once 'config/constants.php';

// Load PHPMailer (adjust path based on your installation)
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['signup_data'])) {
    header("Location: signup.php");
    exit();
}

// Generate new OTP
$new_otp = sprintf("%06d", mt_rand(1, 999999));
$_SESSION['signup_data']['otp'] = $new_otp;
$_SESSION['signup_data']['otp_expiry'] = time() + 600; // 10 minutes

// Send OTP via email
$mail = new PHPMailer(true);
$mail_sent = false;

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    
    // Disable SSL verification for local development
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // Recipients
    $mail->setFrom(SMTP_USER, SITE_NAME);
    $mail->addAddress($_SESSION['signup_data']['email']);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = "Your OTP for Email Verification - " . SITE_NAME;
    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Email Verification OTP</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 10px;
            }
            .header {
                background: linear-gradient(145deg, #667eea, #764ba2);
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 10px 10px 0 0;
            }
            .otp-code {
                font-size: 32px;
                font-weight: bold;
                color: #667eea;
                text-align: center;
                padding: 20px;
                letter-spacing: 5px;
                background: #f8f9fa;
                border-radius: 10px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                color: #666;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Email Verification</h2>
            </div>
            <div class='content'>
                <p>Hello,</p>
                <p>Your verification code for " . SITE_NAME . " is:</p>
                <div class='otp-code'>$new_otp</div>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $mail->send();
    $mail_sent = true;
    
} catch (Exception $e) {
    error_log("OTP resend failed: " . $mail->ErrorInfo);
    $mail_sent = false;
}

if ($mail_sent) {
    header("Location: verify_otp.php?resend=success");
} else {
    header("Location: verify_otp.php?error=Failed to resend OTP. Please try again.");
}
exit();
?>