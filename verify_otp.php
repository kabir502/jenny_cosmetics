<?php
// verify_otp.php - OTP Verification Page

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database and constants
require_once 'config/database.php';
require_once 'config/constants.php';

// Check if user has signup data
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

// For debugging - show stored OTP (remove in production)
if (isset($_GET['debug'])) {
    echo "Stored OTP: " . $_SESSION['signup_data']['otp'] . "<br>";
    echo "Expires: " . date('Y-m-d H:i:s', $_SESSION['signup_data']['otp_expiry']) . "<br>";
    echo "Current: " . date('Y-m-d H:i:s', time()) . "<br>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Jenny\'s Cosmetics & Jewelry'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Your existing CSS remains exactly the same */
        :root {
            --primary: #1e3a5f;
            --primary-light: #2b4c7c;
            --primary-dark: #0f2a44;
            --secondary: #2c3e50;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #0dcaf0;
            --dark: #212529;
            --dark-gray: #6c757d;
            --light: #f8fafc;
            --light-gray: #e9ecef;
            --border: #dee2e6;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--dark);
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        .otp-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
            margin: 2rem 0;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .otp-header {
            background: linear-gradient(145deg, var(--primary), var(--primary-light));
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .otp-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .otp-header h4 {
            color: white;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
            font-size: 1.5rem;
        }

        .otp-body {
            padding: 2.5rem;
            background: white;
        }

        .info-box {
            background: var(--light);
            border-left: 6px solid var(--primary);
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
        }

        .info-box i {
            color: var(--primary);
        }

        .info-box strong {
            color: var(--primary);
            font-weight: 600;
            word-break: break-all;
        }

        .otp-input {
            letter-spacing: 12px;
            font-size: 32px;
            text-align: center;
            font-weight: 700;
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
            transition: var(--transition);
            background: var(--light);
            color: var(--dark);
            font-family: 'Inter', monospace;
        }

        .otp-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(30,58,95,0.1);
            outline: none;
            background: white;
        }

        #expiryTimer {
            display: inline-block;
            padding: 0.35rem 1rem;
            background: var(--light);
            border-radius: 50px;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.1rem;
            border: 1px solid var(--border);
        }

        .btn-verify {
            background: linear-gradient(145deg, var(--primary), var(--primary-light));
            border: none;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 16px;
            transition: var(--transition);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(30,58,95,0.3);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .btn-outline-secondary {
            border: 2px solid var(--border);
            color: var(--dark-gray);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            background: transparent;
        }

        .alert {
            border: none;
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 6px solid #dc3545;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 6px solid #198754;
        }

        @media (max-width: 768px) {
            .otp-body {
                padding: 1.5rem;
            }
            .otp-input {
                font-size: 24px;
                letter-spacing: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-6">
                    <div class="card otp-card">
                        <div class="otp-header">
                            <h4><i class="fas fa-envelope me-2"></i>Email Verification</h4>
                        </div>
                        
                        <div class="otp-body">
                            <!-- Info Box -->
                            <div class="info-box">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fa-2x text-primary me-3"></i>
                                    <div>
                                        <p class="mb-1 fw-bold">Verification Code Sent</p>
                                        <p class="mb-0 text-muted">
                                            We've sent a 6-digit code to:<br>
                                            <strong><?php echo htmlspecialchars($_SESSION['signup_data']['email']); ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Error Message -->
                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo htmlspecialchars($_GET['error']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Success Message -->
                            <?php if (isset($_GET['resend']) && $_GET['resend'] == 'success'): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle"></i>
                                    New verification code has been sent to your email.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <!-- OTP Form -->
                            <form action="verify_otp_process.php" method="POST" id="otpForm">
                                <div class="mb-4">
                                    <label for="otp" class="form-label fw-bold">Enter 6-Digit Verification Code</label>
                                    <input type="text" 
                                           class="form-control form-control-lg otp-input" 
                                           id="otp" 
                                           name="otp" 
                                           maxlength="6" 
                                           pattern="\d{6}" 
                                           title="Please enter exactly 6 digits"
                                           placeholder="000000" 
                                           required 
                                           autofocus
                                           autocomplete="off"
                                           inputmode="numeric">
                                    <div class="form-text text-center mt-3">
                                        <i class="fas fa-clock me-1"></i>
                                        Code expires in <span id="expiryTimer">10:00</span>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-verify w-100 py-3 mb-3" name="verify_otp" id="verifyBtn">
                                    <i class="fas fa-check-circle me-2"></i>Verify Email
                                </button>
                            </form>
                            
                            <!-- Resend Options -->
                            <div class="text-center mt-4">
                                <p class="text-muted mb-3">Didn't receive the code?</p>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="resend_otp.php" class="btn btn-outline-primary" id="resendBtn">
                                        <i class="fas fa-sync-alt me-2"></i>Resend Code
                                    </a>
                                    <a href="signup.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Countdown Timer
        function startTimer(duration, display) {
            let timer = duration, minutes, seconds;
            const interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);
                
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;
                
                display.textContent = minutes + ":" + seconds;
                
                if (--timer < 0) {
                    clearInterval(interval);
                    display.textContent = "Expired";
                    display.style.color = "#dc3545";
                    
                    document.getElementById('otp').disabled = true;
                    document.getElementById('verifyBtn').disabled = true;
                    
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-warning mt-3';
                    alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Code expired. Please request a new one.';
                    document.querySelector('.otp-body').prepend(alertDiv);
                }
            }, 1000);
        }

        // Auto-submit when 6 digits are entered
        document.getElementById('otp').addEventListener('input', function(e) {
            if (this.value.length === 6) {
                document.getElementById('verifyBtn').disabled = true;
                document.getElementById('verifyBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
                document.getElementById('otpForm').submit();
            }
        });
        
        // Only allow numbers
        document.getElementById('otp').addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Prevent pasting non-numeric characters
        document.getElementById('otp').addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = pastedText.replace(/[^0-9]/g, '').slice(0, 6);
            this.value = numbers;
            
            if (numbers.length === 6) {
                document.getElementById('verifyBtn').disabled = true;
                document.getElementById('verifyBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
                document.getElementById('otpForm').submit();
            }
        });

        // Initialize timer on page load
        window.onload = function() {
            const expiryTime = <?php echo $_SESSION['signup_data']['otp_expiry']; ?>;
            const currentTime = Math.floor(Date.now() / 1000);
            const remainingSeconds = expiryTime - currentTime;
            
            if (remainingSeconds > 0) {
                const display = document.querySelector('#expiryTimer');
                startTimer(remainingSeconds, display);
            } else {
                document.querySelector('#expiryTimer').textContent = 'Expired';
            }
        };
    </script>
</body>
</html>