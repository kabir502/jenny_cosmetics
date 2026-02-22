<?php
// login.php - Customer/User login page

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration and constants
require_once 'config/database.php';
require_once 'config/constants.php';

// Check if already logged in as user
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error = '';
$success = '';
$email = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Prepare SQL statement to prevent SQL injection
        $query = "SELECT user_id, first_name, last_name, email, password_hash, is_active 
                  FROM users 
                  WHERE email = ? AND is_active = 1 
                  LIMIT 1";
        
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Update last login timestamp
                $update_query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                $update_stmt = mysqli_prepare($connection, $update_query);
                mysqli_stmt_bind_param($update_stmt, "i", $user['user_id']);
                mysqli_stmt_execute($update_stmt);
                
                // Clear CSRF token
                unset($_SESSION['csrf_token']);
                
                // Redirect to home or previous page
                if (isset($_SESSION['redirect_url'])) {
                    $redirect_url = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    header("Location: " . $redirect_url);
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password. Account not found or inactive.';
        }
    }
}

// Check for success messages
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = 'Registration successful! Please login with your credentials.';
} elseif (isset($_GET['password_reset']) && $_GET['password_reset'] == 1) {
    $success = 'Password reset successful! Please login with your new password.';
} elseif (isset($_GET['logged_out']) && $_GET['logged_out'] == 1) {
    $success = 'You have been successfully logged out.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Jenny\'s Cosmetics & Jewelry'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ULTIMATE RESET - NO SPACE AT BOTTOM */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            background: linear-gradient(135deg, #1A2A4F, #2A3F6F);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .login-wrapper {
            width: 100%;
            max-width: 400px;
            margin: 0;
            padding: 0;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            border: 1px solid #D4AF37;
            margin: 0;
        }

        .login-header {
            background: linear-gradient(135deg, #1A2A4F, #2A3F6F);
            padding: 25px 20px 20px;
            text-align: center;
            border-bottom: 2px solid #D4AF37;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #F4E5C1, #D4AF37);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.8rem;
            color: #1A2A4F;
        }

        .login-header h2 {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 0 5px 0;
            font-family: 'Georgia', serif;
        }

        .login-header p {
            color: rgba(255,255,255,0.9);
            font-size: 0.75rem;
            margin: 0;
            letter-spacing: 1px;
        }

        .card-body {
            padding: 25px 20px;
            background: white;
        }

        /* FORCE NO BOTTOM SPACE ON ALL ELEMENTS */
        .card-body > *:last-child,
        form > *:last-child,
        .text-center > *:last-child,
        p:last-child {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        .alert {
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-danger {
            background: #fee;
            color: #c00;
            border-left: 4px solid #c00;
        }

        .alert-success {
            background: #efe;
            color: #090;
            border-left: 4px solid #090;
        }

        .alert i {
            font-size: 1rem;
        }

        .btn-close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.7;
            line-height: 1;
        }

        .form-label {
            font-weight: 600;
            color: #1A2A4F;
            margin-bottom: 5px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-label i {
            color: #D4AF37;
        }

        .input-group {
            display: flex;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .input-group:focus-within {
            border-color: #D4AF37;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.1);
        }

        .input-group-text {
            background: #f5f5f5;
            padding: 12px 15px;
            color: #D4AF37;
            border: none;
        }

        .form-control {
            flex: 1;
            padding: 12px 15px;
            border: none;
            background: #f5f5f5;
            font-size: 0.9rem;
        }

        .form-control:focus {
            outline: none;
            background: white;
        }

        .btn-outline-secondary {
            background: #f5f5f5;
            border: none;
            border-left: 1px solid #ddd;
            padding: 12px 15px;
            color: #D4AF37;
            cursor: pointer;
        }

        .btn-outline-secondary:hover {
            background: #D4AF37;
            color: #1A2A4F;
        }

        .text-end {
            text-align: right;
            margin-top: 5px;
        }

        .text-end a {
            color: #666;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .text-end a:hover {
            color: #D4AF37;
        }

        .form-check {
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input {
            width: 16px;
            height: 16px;
            border: 2px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #D4AF37;
            border-color: #D4AF37;
        }

        .form-check-label {
            color: #333;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #D4AF37, #AA8C2F);
            border: none;
            border-radius: 8px;
            color: #1A2A4F;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin: 5px 0 0 0;
            transition: all 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(212,175,55,0.3);
        }

        .btn-login i {
            margin-right: 5px;
        }

        .btn-login.loading {
            position: relative;
            color: transparent;
            pointer-events: none;
        }

        .btn-login.loading:after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #1A2A4F;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 15px 0 10px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #eee;
        }

        .divider span {
            padding: 0 10px;
            color: #999;
            font-size: 0.8rem;
        }

        .text-center {
            text-align: center;
        }

        .text-center p {
            margin: 0 0 8px 0;
        }

        .text-center p:last-child {
            margin: 0;
        }

        .text-center a {
            color: #D4AF37;
            text-decoration: none;
        }

        .text-center a:hover {
            color: #1A2A4F;
        }

        .text-center i {
            font-size: 0.8rem;
        }

        /* ULTIMATE BOTTOM SPACE KILLER */
        .login-wrapper,
        .login-card,
        .card-body,
        .card-body > *:last-child,
        .text-center p:last-child {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        body::before,
        body::after {
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Login Card -->
        <div class="login-card">
            <div class="login-header">
                <div class="brand-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Jenny's Cosmetics & Jewelry</p>
            </div>
            
            <div class="card-body">
                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert">×</button>
                </div>
                <?php endif; ?>
                
                <!-- Success Message -->
                <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert">×</button>
                </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="login_process.php" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Email -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   placeholder="your@email.com" required autofocus>
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-key"></i> Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" 
                                   name="password" placeholder="••••••••" required>
                            <button type="button" class="btn-outline-secondary" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="text-end">
                            <a href="forgot_password.php">Forgot password?</a>
                        </div>
                    </div>
                    
                    <!-- Remember Me -->
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Keep me signed in</label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                    
                    <!-- Divider -->
                    <div class="divider">
                        <span>New here?</span>
                    </div>
                    
                    <!-- Links -->
                    <div class="text-center">
                        <p><a href="signup.php">Create an account <i class="fas fa-arrow-right"></i></a></p>
                        <p><a href="index.php"><i class="fas fa-home"></i> Back to Home</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = event.currentTarget.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Add loading state to button
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('loginBtn').classList.add('loading');
        });

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.querySelectorAll('.alert').forEach(function(alert) {
                    alert.style.display = 'none';
                });
            }, 5000);
        });
    </script>
</body>
</html>