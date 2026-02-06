<?php
// verify_otp.php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['signup_data'])) {
    header("Location: signup.php");
    exit();
}

// Check OTP expiry
if (time() > $_SESSION['signup_data']['otp_expiry']) {
    unset($_SESSION['signup_data']);
    header("Location: signup.php?error=OTP expired. Please register again.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Verify Email Address</h4>
                    </div>
                    <div class="card-body">
                        <p>We've sent a 6-digit OTP to: <strong><?php echo $_SESSION['signup_data']['email']; ?></strong></p>
                        
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                        <?php endif; ?>
                        
                        <form action="verify_otp_process.php" method="POST">
                            <div class="mb-3">
                                <label for="otp" class="form-label">Enter OTP</label>
                                <input type="text" class="form-control" id="otp" name="otp" 
                                       maxlength="6" pattern="\d{6}" required 
                                       placeholder="000000">
                            </div>
                            <button type="submit" class="btn btn-primary w-100" name="verify_otp">Verify OTP</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <p>Didn't receive OTP? <a href="resend_otp.php">Resend</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>