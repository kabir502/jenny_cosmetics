<?php
// user_login.php - Customer/User login page

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config/database.php';

// Check if already logged in as user
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit();
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
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
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
            
            if (password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (30 * 24 * 60 * 60);
                    
                    $token_query = "INSERT INTO user_tokens (user_id, token, expiry) VALUES (?, ?, FROM_UNIXTIME(?))";
                    $token_stmt = mysqli_prepare($connection, $token_query);
                    mysqli_stmt_bind_param($token_stmt, "isi", $user['user_id'], $token, $expiry);
                    mysqli_stmt_execute($token_stmt);
                    
                    setcookie('remember_token', $token, $expiry, '/', '', true, true);
                }
                
                $update_query = "UPDATE users SET last_login = NOW(), last_login = ? WHERE user_id = ?";
                $update_stmt = mysqli_prepare($connection, $update_query);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                mysqli_stmt_bind_param($update_stmt, "si", $ip_address, $user['user_id']);
                mysqli_stmt_execute($update_stmt);
                
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="Login to Jenny's Cosmetics & Jewelry">
    <title>Login - Jenny's Cosmetics & Jewelry</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* CSS Variables */
        :root {
            --primary-gradient: linear-gradient(145deg, #667eea, #764ba2);
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
            --text-dark: #2d3748;
            --text-muted: #718096;
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 30px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.2);
            --border-radius-lg: 24px;
            --border-radius-md: 18px;
            --border-radius-sm: 14px;
            --transition-base: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: radial-gradient(circle at 0% 0%, rgba(102, 126, 234, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 100% 100%, rgba(118, 75, 162, 0.15) 0%, transparent 50%),
                        linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
            position: relative;
        }

        /* Fixed width container - NO BOOTSTRAP CONTAINER CONSTRAINTS */
        .login-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Main Card - Fixed width behavior */
        .login-card {
            width: 100%;
            max-width: 460px;
            min-width: 320px;
            border: none;
            border-radius: var(--border-radius-lg);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin: 0 auto;
            transition: all 0.3s ease;
        }

        /* When screen is very large, maintain max-width */
        @media (min-width: 1600px) {
            .login-card {
                max-width: 480px;
            }
        }

        /* When screen is very small, maintain min-width */
        @media (max-width: 360px) {
            .login-card {
                min-width: 280px;
            }
        }

        /* Header Section */
        .login-header {
            background: var(--primary-gradient);
            padding: 40px 30px 35px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .brand-icon {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 50%;
            margin-bottom: 20px;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: var(--transition-base);
            border: 3px solid rgba(255,255,255,0.3);
            display: inline-block;
        }

        .login-header h2 {
            color: white;
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: -1px;
            text-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .login-header p {
            color: rgba(255,255,255,0.95);
            font-size: 0.9rem;
            margin: 0;
            letter-spacing: 4px;
            font-weight: 500;
            text-transform: uppercase;
        }

        /* Card Body */
        .card-body {
            padding: 40px 35px;
            background: white;
        }

        /* Form Labels */
        .form-label {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        /* Input Groups */
        .input-group {
            margin-bottom: 5px;
            box-shadow: var(--shadow-sm);
            border-radius: var(--border-radius-sm);
            overflow: hidden;
            transition: var(--transition-base);
        }

        .input-group:hover {
            box-shadow: 0 5px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .input-group-text {
            background: var(--light-bg);
            border: 2px solid var(--border-color);
            border-right: none;
            padding: 14px 20px;
            color: var(--primary-color);
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-left: none;
            border-right: 2px solid var(--border-color) !important;
            padding: 14px 18px;
            font-size: 0.95rem;
            transition: var(--transition-base);
            background: white;
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: none;
            background: white;
        }

        /* Password Toggle Button */
        .btn-outline-secondary {
            border: 2px solid var(--border-color);
            border-left: none;
            background: white;
            padding: 14px 18px;
            transition: var(--transition-base);
            color: #718096;
        }

        .btn-outline-secondary:hover {
            background: var(--light-bg);
            color: var(--primary-color);
        }

        /* Login Button */
        .btn-login {
            background: var(--primary-gradient);
            border: none;
            padding: 16px 24px;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-radius: var(--border-radius-sm);
            transition: var(--transition-base);
            margin-top: 20px;
            position: relative;
            overflow: hidden;
            color: white;
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.4);
            background: linear-gradient(145deg, #764ba2, #667eea);
        }

        .btn-login i {
            margin-right: 10px;
            transition: transform 0.3s ease;
        }

        .btn-login:hover i {
            transform: translateX(6px);
        }

        /* Remember Me Checkbox */
        .form-check {
            margin: 25px 0;
            padding-left: 1.8em;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            color: var(--text-muted);
            font-size: 0.95rem;
            cursor: pointer;
            font-weight: 500;
            margin-left: 8px;
        }

        /* Forgot Password Link */
        .text-end a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: var(--transition-base);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .text-end a:hover {
            color: var(--primary-color);
            transform: translateX(4px);
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 30px 0 25px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 2px solid #e9ecef;
        }

        .divider span {
            padding: 0 20px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            background: white;
        }

        /* Links */
        a {
            color: var(--primary-color);
            transition: var(--transition-base);
            font-weight: 600;
            text-decoration: none;
        }

        a:hover {
            color: var(--secondary-color);
            transform: translateX(4px);
        }

        .text-decoration-none {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .text-decoration-none:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background: var(--primary-gradient);
            transition: width 0.4s var(--transition-base);
        }

        .text-decoration-none:hover:after {
            width: 100%;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: var(--border-radius-sm);
            padding: 16px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 6px solid;
            font-weight: 500;
        }

        .alert-danger {
            background: linear-gradient(145deg, #fff8f8, #ffe9e9);
            color: #c53030;
            border-left-color: #c53030;
        }

        .alert-success {
            background: linear-gradient(145deg, #f0fdf4, #dcfce7);
            color: #276749;
            border-left-color: #276749;
        }

        .alert i {
            font-size: 1.2rem;
        }

        /* Demo Card */
        .demo-card {
            width: 100%;
            max-width: 460px;
            min-width: 320px;
            margin: 25px auto 0;
            border: none;
            border-radius: var(--border-radius-md);
            background: white;
            box-shadow: var(--shadow-md);
            transition: var(--transition-base);
        }

        @media (min-width: 1600px) {
            .demo-card {
                max-width: 480px;
            }
        }

        @media (max-width: 360px) {
            .demo-card {
                min-width: 280px;
            }
        }

        .demo-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .demo-card .card-body {
            padding: 25px;
            background: linear-gradient(145deg, #ffffff, #fafbfc);
        }

        .demo-card .card-title {
            color: var(--text-dark);
            font-weight: 800;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.1rem;
        }

        .demo-card .card-title i {
            color: #ffc107;
            font-size: 1.4rem;
        }

        .demo-credentials {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius-sm);
            margin: 15px 0 5px;
            border: 2px dashed #e9ecef;
        }

        .demo-credentials p {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.95rem;
        }

        .demo-credentials p:last-child {
            margin-bottom: 0;
        }

        .demo-credentials strong {
            color: var(--text-dark);
            min-width: 80px;
            font-size: 0.9rem;
        }

        .demo-credentials i {
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        .text-small {
            font-size: 0.8rem;
            color: #a0aec0;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
        }

        .text-primary {
            color: var(--primary-color) !important;
            font-weight: 700;
        }

        /* Loading State */
        .btn-login.loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }

        .btn-login.loading:after {
            content: '';
            position: absolute;
            width: 26px;
            height: 26px;
            top: 50%;
            left: 50%;
            margin-left: -13px;
            margin-top: -13px;
            border: 3px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            border-right-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design - FIXED BEHAVIOR */
        @media (max-width: 768px) {
            .login-card,
            .demo-card {
                max-width: 440px;
            }
            
            .card-body {
                padding: 35px 30px;
            }
            
            .login-header {
                padding: 35px 25px 30px;
            }
            
            .login-header h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .login-card,
            .demo-card {
                max-width: 100%;
                min-width: 280px;
            }
            
            .card-body {
                padding: 30px 25px;
            }
            
            .login-header {
                padding: 30px 20px 25px;
            }
            
            .login-header h2 {
                font-size: 1.8rem;
            }
            
            .brand-icon {
                padding: 18px;
                font-size: 2.2rem;
            }
            
            .demo-credentials p {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .demo-credentials strong {
                min-width: auto;
            }
        }

        @media (max-width: 380px) {
            .login-card,
            .demo-card {
                min-width: 260px;
            }
            
            .card-body {
                padding: 25px 20px;
            }
            
            .login-header h2 {
                font-size: 1.6rem;
            }
            
            .brand-icon {
                padding: 15px;
                font-size: 2rem;
            }
            
            .form-label {
                font-size: 0.75rem;
            }
            
            .input-group-text,
            .form-control,
            .btn-outline-secondary {
                padding: 12px;
            }
        }

        /* Landscape Mode */
        @media (max-height: 700px) and (orientation: landscape) {
            body {
                padding: 10px;
            }
            
            .login-card,
            .demo-card {
                max-width: 440px;
            }
            
            .login-header {
                padding: 20px 20px 15px;
            }
            
            .brand-icon {
                padding: 12px;
                font-size: 1.8rem;
                margin-bottom: 10px;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
                margin-bottom: 5px;
            }
            
            .card-body {
                padding: 20px;
            }
        }

        /* Very Large Screens - Maintain size */
        @media (min-width: 1400px) {
            .login-card,
            .demo-card {
                max-width: 480px;
            }
        }

        /* Ultra Wide Screens - Still maintain size */
        @media (min-width: 2000px) {
            .login-card,
            .demo-card {
                max-width: 500px;
            }
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .login-card {
                box-shadow: none;
                border: 2px solid #ddd;
                max-width: 100%;
            }
            
            .btn-login,
            .demo-card,
            .btn-close,
            .btn-outline-secondary {
                display: none !important;
            }
        }

        /* Remove Bootstrap container constraints */
        .container,
        .container-fluid,
        .container-lg,
        .container-md,
        .container-sm,
        .container-xl,
        .container-xxl {
            width: 100%;
            padding-right: 0;
            padding-left: 0;
            margin-right: 0;
            margin-left: 0;
            max-width: none;
        }

        .row {
            margin-right: 0;
            margin-left: 0;
            width: 100%;
        }

        [class*="col-"] {
            padding-right: 0;
            padding-left: 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Login Card -->
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <i class="fas fa-gem brand-icon"></i>
                <h2>Welcome Back!</h2>
                <p>Jenny's Cosmetics & Jewelry</p>
            </div>
            
            <!-- Body -->
            <div class="card-body">
                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Success Message -->
                <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="loginForm">
                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-at"></i>
                            </span>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   placeholder="Enter your email address" 
                                   required 
                                   autofocus
                                   autocomplete="email">
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-key"></i>
                            Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password" 
                                   required
                                   autocomplete="current-password">
                            <button type="button" 
                                    class="btn btn-outline-secondary" 
                                    onclick="togglePassword()"
                                    aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="text-end mt-2">
                            <a href="forgot_password.php">
                                <i class="fas fa-question-circle"></i>
                                Forgot password?
                            </a>
                        </div>
                    </div>
                    
                    <!-- Remember Me -->
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Keep me logged in
                        </label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                    
                    <!-- Divider -->
                    <div class="divider">
                        <span>OR</span>
                    </div>
                    
                    <!-- Links -->
                    <div class="text-center">
                        <p class="mb-3">
                            New to Jenny's? 
                            <a href="signup.php" class="fw-bold">
                                Create an account 
                                <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </p>
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-home"></i>
                            Back to Home
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Demo User Credentials -->
        <div class="demo-card">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-flask"></i>
                    Demo Account
                </h6>
                <div class="demo-credentials">
                    <p>
                        <i class="fas fa-envelope"></i>
                        <strong>Email:</strong> 
                        <span class="text-primary">customer@jenny.com</span>
                    </p>
                    <p>
                        <i class="fas fa-key"></i>
                        <strong>Password:</strong> 
                        <span class="text-primary">demo123456</span>
                    </p>
                </div>
                <small class="text-small">
                    <i class="fas fa-info-circle"></i>
                    Use this account for testing the application
                </small>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = event.currentTarget;
            const icon = toggleButton.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                toggleButton.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                toggleButton.setAttribute('aria-label', 'Show password');
            }
        }

        // DOM Ready
        document.addEventListener('DOMContentLoaded', function() {
            // Auto focus on email field
            document.getElementById('email').focus();
            
            // Add loading state to form submission
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    loginBtn.classList.add('loading');
                    loginBtn.disabled = true;
                });
            }
            
            // Remove loading state when page loads (for back button)
            window.addEventListener('pageshow', function() {
                if (loginBtn) {
                    loginBtn.classList.remove('loading');
                    loginBtn.disabled = false;
                }
            });
        });
    </script>
</body>
</html>