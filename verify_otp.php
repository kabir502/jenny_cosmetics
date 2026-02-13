<?php
// verify_otp.php
require_once 'config/database.php';
session_start(); // ADD THIS - you had it commented out!

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

// Debug - remove after testing
// echo "Stored OTP: " . $_SESSION['signup_data']['otp'] . "<br>";
// echo "Current time: " . time() . "<br>";
// echo "Expiry time: " . $_SESSION['signup_data']['otp_expiry'] . "<br>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .otp-input {
            letter-spacing: 8px;
            font-size: 24px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-envelope"></i> Verify Email Address</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            We've sent a 6-digit OTP to: 
                            <strong><?php echo $_SESSION['signup_data']['email']; ?></strong>
                        </div>
                        
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['resend']) && $_GET['resend'] == 'success'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                New OTP has been sent to your email.
                            </div>
                        <?php endif; ?>
                        
                        <form action="verify_otp_process.php" method="POST" id="otpForm">
                            <div class="mb-3">
                                <label for="otp" class="form-label fw-bold">Enter 6-Digit OTP</label>
                                <input type="text" 
                                       class="form-control form-control-lg otp-input" 
                                       id="otp" 
                                       name="otp" 
                                       maxlength="6" 
                                       pattern="\d{6}" 
                                       title="Please enter exactly 6 digits"
                                       placeholder="000000" 
                                       required 
                                       autofocus>
                                <div class="form-text">Enter the 6-digit code sent to your email.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2" name="verify_otp">
                                <i class="fas fa-check-circle"></i> Verify OTP
                            </button>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p class="mb-2">Didn't receive OTP?</p>
                            <a href="resend_otp.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt"></i> Resend OTP
                            </a>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="signup.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left"></i> Back to Sign Up
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Auto-submit when 6 digits are entered
        document.getElementById('otp').addEventListener('input', function(e) {
            if (this.value.length === 6) {
                document.getElementById('otpForm').submit();
            }
        });
        
        // Only allow numbers
        document.getElementById('otp').addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>