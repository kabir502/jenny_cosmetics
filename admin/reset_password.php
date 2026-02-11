<?php
// admin/reset_password.php - Reset admin password
require_once '../config/database.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $username = 'admin';
    $new_password = 'admin123';
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $sql = "UPDATE administrators SET password_hash = '$new_hash' WHERE username = '$username'";
    
    if (mysqli_query($connection, $sql)) {
        $success = "✅ Password reset to: <strong>$new_password</strong>";
    } else {
        $error = "❌ Error: " . mysqli_error($connection);
    }
}

// Check current password hash
$check_sql = "SELECT password_hash FROM administrators WHERE username = 'admin'";
$result = mysqli_query($connection, $check_sql);
$current_hash = '';
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $current_hash = $row['password_hash'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Admin Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Reset Admin Password</h2>
    
    <?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card mt-3">
        <div class="card-body">
            <h5>Current Password Hash:</h5>
            <code><?php echo htmlspecialchars($current_hash); ?></code>
            <p class="mt-2">Hash starts with: <strong><?php echo substr($current_hash, 0, 7); ?></strong></p>
            
            <hr>
            
            <form method="POST">
                <div class="mb-3">
                    <label>This will reset admin password to: <code>admin123</code></label>
                </div>
                <button type="submit" name="reset" class="btn btn-danger">Reset Password</button>
                <a href="login.php" class="btn btn-secondary">Back to Login</a>
            </form>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-body">
            <h5>Test Password Verification:</h5>
            <form method="POST" action="test_password.php">
                <div class="mb-3">
                    <label>Test Password:</label>
                    <input type="password" name="test_password" class="form-control" value="admin123">
                </div>
                <button type="submit" class="btn btn-info">Test This Password</button>
            </form>
        </div>
    </div>
</body>
</html>