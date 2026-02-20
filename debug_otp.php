<?php
// debug_otp.php - Debug OTP issues

session_start();

echo "<h1>OTP Debug Page</h1>";

if (!isset($_SESSION['signup_data'])) {
    echo "<p style='color:red;'>No signup_data in session!</p>";
    echo "<p><a href='signup.php'>Go to Signup</a></p>";
    exit();
}

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION['signup_data']);
echo "</pre>";

$stored_otp = $_SESSION['signup_data']['otp'] ?? 'Not set';
$expiry = $_SESSION['signup_data']['otp_expiry'] ?? 0;
$now = time();

echo "<h2>OTP Information:</h2>";
echo "<p><strong>Stored OTP:</strong> " . htmlspecialchars($stored_otp) . "</p>";
echo "<p><strong>OTP Type:</strong> " . gettype($stored_otp) . "</p>";
echo "<p><strong>Expiry Time:</strong> " . date('Y-m-d H:i:s', $expiry) . "</p>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s', $now) . "</p>";
echo "<p><strong>Time Remaining:</strong> " . ($expiry - $now) . " seconds</p>";

if ($expiry < $now) {
    echo "<p style='color:red;'>OTP has EXPIRED</p>";
} else {
    echo "<p style='color:green;'>OTP is still valid</p>";
}

echo "<h2>Test OTP Verification:</h2>";
echo "<form method='post' action='debug_test_otp.php'>";
echo "<input type='text' name='test_otp' placeholder='Enter OTP to test'>";
echo "<button type='submit'>Test OTP</button>";
echo "</form>";

echo "<p><a href='resend_otp.php'>Resend OTP</a></p>";
echo "<p><a href='signup.php'>Back to Signup</a></p>";
?>