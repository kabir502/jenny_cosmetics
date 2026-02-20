<?php
// debug_test_otp.php - Test OTP comparison

session_start();

echo "<h1>OTP Test Result</h1>";

if (!isset($_SESSION['signup_data'])) {
    echo "<p style='color:red;'>No session data found!</p>";
    exit();
}

$user_otp = $_POST['test_otp'] ?? '';
$stored_otp = $_SESSION['signup_data']['otp'] ?? '';

echo "<h2>Comparison:</h2>";
echo "<p><strong>User entered:</strong> '" . htmlspecialchars($user_otp) . "' (length: " . strlen($user_otp) . ")</p>";
echo "<p><strong>Stored OTP:</strong> '" . htmlspecialchars($stored_otp) . "' (length: " . strlen($stored_otp) . ")</p>";

// Test different comparison methods
echo "<h3>Comparison Results:</h3>";

$method1 = ($user_otp == $stored_otp);
echo "<p><strong>Method 1 (==):</strong> " . ($method1 ? 'MATCH' : 'NO MATCH') . "</p>";

$method2 = ($user_otp === $stored_otp);
echo "<p><strong>Method 2 (===):</strong> " . ($method2 ? 'MATCH' : 'NO MATCH') . "</p>";

$method3 = (trim($user_otp) == trim($stored_otp));
echo "<p><strong>Method 3 (trim ==):</strong> " . ($method3 ? 'MATCH' : 'NO MATCH') . "</p>";

$method4 = (trim($user_otp) === trim($stored_otp));
echo "<p><strong>Method 4 (trim ===):</strong> " . ($method4 ? 'MATCH' : 'NO MATCH') . "</p>";

$method5 = ((string)$user_otp === (string)$stored_otp);
echo "<p><strong>Method 5 (string cast ===):</strong> " . ($method5 ? 'MATCH' : 'NO MATCH') . "</p>";

$method6 = (trim((string)$user_otp) === trim((string)$stored_otp));
echo "<p><strong>Method 6 (trim + string cast):</strong> " . ($method6 ? 'MATCH' : 'NO MATCH') . "</p>";

echo "<p><a href='debug_otp.php'>Back to Debug</a></p>";
?>