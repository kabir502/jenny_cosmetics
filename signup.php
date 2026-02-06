<?php
// signup.php
require_once 'config/database.php';
require_once 'config/constants.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .signup-container { max-width: 500px; margin: 50px auto; }
        .form-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container signup-container">
        <div class="form-container">
            <h2 class="text-center mb-4">Create Your Account</h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <form action="process_signup.php" method="POST" id="signupForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="form-text">We'll send OTP to this email</div>
                </div>
                
                <div class="mb-3">
                    <label for="phone_cell" class="form-label">Mobile Number *</label>
                    <input type="tel" class="form-control" id="phone_cell" name="phone_cell" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                    <div class="form-text">Minimum 8 characters</div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="mb-3">
                    <label for="address_street" class="form-label">Street Address</label>
                    <input type="text" class="form-control" id="address_street" name="address_street">
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="address_city" class="form-label">City</label>
                        <input type="text" class="form-control" id="address_city" name="address_city">
                    </div>
                    <div class="col-md-6">
                        <label for="address_country" class="form-label">Country</label>
                        <select class="form-control" id="address_country" name="address_country">
                            <option value="USA">United States</option>
                            <option value="UK">United Kingdom</option>
                            <option value="Canada">Canada</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100" name="signup_btn">Sign Up</button>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Your terms and conditions content here...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>