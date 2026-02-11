<?php
// admin/test_password.php - Test password verification
require_once '../config/database.php';

$test_password = $_POST['test_password'] ?? 'admin123';

// Get current hash
$sql = "SELECT password_hash FROM administrators WHERE username = 'admin'";
$result = mysqli_query($connection, $sql);
$row = mysqli_fetch_assoc($result);
$current_hash = $row['password_hash'];

// Test verification
$verified = password_verify($test_password, $current_hash);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Password Test Results</h2>
    
    <div class="card mt-3">
        <div class="card-body">
            <h5>Test Details:</h5>
            <p><strong>Password tested:</strong> <?php echo htmlspecialchars($test_password); ?></p>
            <p><strong>Current hash in DB:</strong> <code><?php echo htmlspecialchars($current_hash); ?></code></p>
            <p><strong>Verification result:</strong> 
                <?php if ($verified): ?>
                <span class="badge bg-success">✅ SUCCESS - Password matches!</span>
                <?php else: ?>
                <span class="badge bg-danger">❌ FAILED - Password does not match</span>
                <?php endif; ?>
            </p>
            
            <h5 class="mt-4">Generate New Hash:</h5>
            <?php
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            ?>
            <code><?php echo $new_hash; ?></code>
            
            <hr>
            
            <form method="POST" action="update_password.php">
                <input type="hidden" name="new_password" value="<?php echo htmlspecialchars($test_password); ?>">
                <button type="submit" class="btn btn-warning">Update to This Hash</button>
                <a href="login.php" class="btn btn-secondary">Back to Login</a>
            </form>
        </div>
    </div>
</body>
</html>