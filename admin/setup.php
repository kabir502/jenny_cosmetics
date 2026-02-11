<?php
// admin/setup.php - Create admin user
require_once '../config/database.php';

echo "<h2>Admin Setup</h2>";

// Check if administrators table exists
$check_table = "SHOW TABLES LIKE 'administrators'";
$result = mysqli_query($connection, $check_table);

if (mysqli_num_rows($result) == 0) {
    // Create administrators table
    $sql = "CREATE TABLE administrators (
        admin_id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        role ENUM('Super Admin', 'Content Manager', 'Order Manager') DEFAULT 'Super Admin',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($connection, $sql)) {
        echo "✓ Administrators table created<br>";
    } else {
        echo "✗ Error creating table: " . mysqli_error($connection) . "<br>";
    }
}

// Check if admin exists
$check_admin = "SELECT * FROM administrators WHERE username = 'admin'";
$admin_result = mysqli_query($connection, $check_admin);

if (mysqli_num_rows($admin_result) == 0) {
    // Create admin user
    $username = 'admin';
    $password = 'admin123';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $full_name = 'System Administrator';
    $email = 'admin@jennyscosmetics.com';
    $role = 'Super Admin';
    
    $sql = "INSERT INTO administrators (username, password_hash, full_name, email, role) 
            VALUES ('$username', '$password_hash', '$full_name', '$email', '$role')";
    
    if (mysqli_query($connection, $sql)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✓ Admin User Created Successfully!</h4>";
        echo "<p><strong>Username:</strong> $username</p>";
        echo "<p><strong>Password:</strong> $password</p>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<p><strong>Role:</strong> $role</p>";
        echo "</div>";
        echo "<a href='login.php' class='btn btn-success'>Go to Admin Login</a>";
    } else {
        echo "✗ Error creating admin: " . mysqli_error($connection) . "<br>";
    }
} else {
    echo "<div class='alert alert-info'>";
    echo "✓ Admin user already exists in database.<br>";
    
    // Show current admin info
    $admin = mysqli_fetch_assoc($admin_result);
    echo "<p><strong>Username:</strong> " . $admin['username'] . "</p>";
    echo "<p><strong>Email:</strong> " . $admin['email'] . "</p>";
    echo "<p><strong>Status:</strong> " . ($admin['is_active'] ? 'Active' : 'Inactive') . "</p>";
    echo "</div>";
    
    // Option to reset password
    if (isset($_GET['reset']) && $_GET['reset'] == '1') {
        $new_password = 'admin123';
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $reset_sql = "UPDATE administrators SET password_hash = '$new_hash' WHERE username = 'admin'";
        
        if (mysqli_query($connection, $reset_sql)) {
            echo "<div class='alert alert-warning'>";
            echo "Password reset to: <strong>$new_password</strong>";
            echo "</div>";
        }
    }
    
    echo "<a href='login.php' class='btn btn-primary me-2'>Go to Login</a>";
    echo "<a href='?reset=1' class='btn btn-warning'>Reset Password to 'admin123'</a>";
}

echo "<hr>";
echo "<h4>Debug: Check Administrators Table</h4>";
$debug_query = "SELECT * FROM administrators";
$debug_result = mysqli_query($connection, $debug_query);

echo "<table class='table table-bordered'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Password Hash</th></tr>";
while ($row = mysqli_fetch_assoc($debug_result)) {
    echo "<tr>";
    echo "<td>" . $row['admin_id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td><small>" . substr($row['password_hash'], 0, 30) . "...</small></td>";
    echo "</tr>";
}
echo "</table>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <?php /* Content above */ ?>
    <div class="alert alert-danger mt-4">
        <h5>⚠️ Security Notice:</h5>
        <p>Delete this file (<code>setup.php</code>) after setting up admin!</p>
    </div>
</body>
</html>