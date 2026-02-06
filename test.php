<?php
// test.php - Simple test file
echo "PHP is working!<br>";

// Test database connection
try {
    $conn = mysqli_connect('localhost', 'root', '', 'jenny_cosmetics_db');
    if ($conn) {
        echo "Database connection successful!<br>";
    } else {
        echo "Database connection failed: " . mysqli_connect_error() . "<br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Display PHP info
phpinfo();
?>