<?php
// admin/login.php - Admin login page

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
include 'config/database.php';

// Check if already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables
$error = '';
$username = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Sanitize username
        $username = mysqli_real_escape_string($connection, $username);
        
        // Get admin from database
        $query = "SELECT * FROM administrators 
                  WHERE username = '$username' OR email = '$username' 
                  AND is_active = 1 
                  LIMIT 1";
        $result = mysqli_query($connection, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $admin = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $admin['password_hash'])) {
                // Set session variables
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_last_login'] = time();
                
                // Update last login
                mysqli_query($connection, 
                    "UPDATE administrators SET last_login = NOW() 
                     WHERE admin_id = {$admin['admin_id']}");
                
                // Log the login
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $log_query = "INSERT INTO audit_logs (admin_id, action_type, ip_address, user_agent) 
                              VALUES ({$admin['admin_id']}, 'Admin Login', '$ip_address', '$user_agent')";
                mysqli_query($connection, $log_query);
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border-radius: 15px 15px 0 0;
        }
        .form-control {
            padding: 12px 15px;
        }
        .btn-login {
            padding: 12px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <!-- Login Card -->
                <div class="card login-card">
                    <!-- Header -->
                    <div class="card-header login-header text-white text-center py-4">
                        <h2><i class="fas fa-lock me-2"></i>Admin Login</h2>
                        <p class="mb-0"><?php echo SITE_NAME; ?></p>
                    </div>
                    
                    <!-- Body -->
                    <div class="card-body p-4">
                        <!-- Error Message -->
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form method="POST" action="">
                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-1"></i>Username or Email
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-at"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($username); ?>" 
                                           placeholder="Enter username or email" required autofocus>
                                </div>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-key me-1"></i>Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter password" required>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="togglePassword()">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Remember Me -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                            
                            <!-- Links -->
                            <div class="text-center">
                                <a href="../index.php" class="text-decoration-none">
                                    <i class="fas fa-home me-1"></i>Back to Home
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Footer -->
                    <div class="card-footer text-center py-3">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Secure Admin Access
                        </small>
                    </div>
                </div>
                
                <!-- Demo Credentials (Remove in Production) -->
                <div class="card mt-3">
                    <div class="card-body p-3">
                        <h6 class="card-title">
                            <i class="fas fa-info-circle me-2 text-info"></i>Demo Credentials
                        </h6>
                        <p class="mb-1"><strong>Username:</strong> admin</p>
                        <p class="mb-1"><strong>Password:</strong> admin123</p>
                        <small class="text-muted">These are for testing only</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Auto focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>