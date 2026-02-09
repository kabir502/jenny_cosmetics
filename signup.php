<?php
// signup.php
session_start();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .signup-container { 
            max-width: 550px; 
            margin: 30px auto;
            animation: fadeIn 0.5s ease-in;
        }
        .form-container { 
            background: white; 
            padding: 35px; 
            border-radius: 15px; 
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            border: 1px solid #eaeaea;
        }
        h2 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 25px;
        }
        .form-label {
            font-weight: 500;
            color: #34495e;
            margin-bottom: 8px;
        }
        .form-control {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        .strength-weak { background-color: #e74c3c; width: 25%; }
        .strength-medium { background-color: #f39c12; width: 50%; }
        .strength-strong { background-color: #2ecc71; width: 75%; }
        .strength-very-strong { background-color: #27ae60; width: 100%; }
        .password-requirements {
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        .requirement {
            margin-bottom: 3px;
        }
        .requirement.met {
            color: #27ae60;
        }
        .requirement.met i {
            color: #27ae60;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #1c5a7a);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .login-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link:hover {
            text-decoration: underline;
            color: #2980b9;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-check-input:checked {
            background-color: #3498db;
            border-color: #3498db;
        }
        .terms-link {
            color: #3498db;
            cursor: pointer;
            text-decoration: none;
        }
        .terms-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container signup-container">
        <div class="form-container">
            <h2 class="text-center mb-4">Create Your Account</h2>
            <p class="text-center text-muted mb-4">Join Jenny's Cosmetics & Jewelry today</p>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form action="process_signup.php" method="POST" id="signupForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label required">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required 
                               placeholder="Enter your first name">
                        <div class="invalid-feedback">Please enter your first name.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label required">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required
                               placeholder="Enter your last name">
                        <div class="invalid-feedback">Please enter your last name.</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label required">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required
                           placeholder="example@domain.com">
                    <div class="form-text">We'll send OTP to this email for verification</div>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
                
                <div class="mb-3">
                    <label for="phone_cell" class="form-label required">Mobile Number</label>
                    <input type="tel" class="form-control" id="phone_cell" name="phone_cell" required
                           placeholder="+1 (555) 123-4567">
                    <div class="invalid-feedback">Please enter a valid phone number.</div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label required">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required
                               minlength="8" placeholder="Create a strong password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength mt-2" id="passwordStrength"></div>
                    <div class="password-requirements mt-2">
                        <div class="requirement" id="reqLength"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> At least 8 characters</div>
                        <div class="requirement" id="reqUppercase"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> At least one uppercase letter</div>
                        <div class="requirement" id="reqLowercase"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> At least one lowercase letter</div>
                        <div class="requirement" id="reqNumber"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> At least one number</div>
                        <div class="requirement" id="reqSpecial"><i class="fas fa-circle" style="font-size: 0.5rem;"></i> At least one special character</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label required">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                               placeholder="Confirm your password">
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="passwordMatchMessage" class="form-text mt-1"></div>
                </div>
                
                <div class="mb-3">
                    <label for="address_street" class="form-label">Street Address</label>
                    <input type="text" class="form-control" id="address_street" name="address_street"
                           placeholder="123 Main Street">
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="address_city" class="form-label">City</label>
                        <input type="text" class="form-control" id="address_city" name="address_city"
                               placeholder="New York">
                    </div>
                    <div class="col-md-6">
                        <label for="address_country" class="form-label">Country</label>
                        <select class="form-control" id="address_country" name="address_country">
                            <option value="">Select Country</option>
                            <option value="USA">United States</option>
                            <option value="UK">United Kingdom</option>
                            <option value="Canada">Canada</option>
                            <option value="Australia">Australia</option>
                            <option value="Germany">Germany</option>
                            <option value="France">France</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                        <label class="form-check-label" for="newsletter">
                            Subscribe to our newsletter for updates and promotions
                        </label>
                    </div>
                </div>
                
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#" class="terms-link" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a> and <a href="#" class="terms-link" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                    </label>
                    <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-3" name="signup_btn">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
                
                <div class="text-center mt-4">
                    <p class="text-muted">Already have an account? 
                        <a href="login.php" class="login-link">Login here</a>
                    </p>
                    <hr class="my-4">
                    <p class="text-muted small">
                        By creating an account, you agree to our Terms and Privacy Policy.
                        Your information is secure with us.
                    </p>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By accessing and using Jenny's Cosmetics & Jewelry website, you accept and agree to be bound by the terms and provision of this agreement.</p>
                    
                    <h6>2. User Account</h6>
                    <p>You are responsible for maintaining the confidentiality of your account and password and for restricting access to your computer.</p>
                    
                    <h6>3. Products and Services</h6>
                    <p>All products and services are subject to availability. We reserve the right to discontinue any product at any time.</p>
                    
                    <h6>4. Pricing</h6>
                    <p>Prices for our products are subject to change without notice. We reserve the right to modify or discontinue products without notice.</p>
                    
                    <h6>5. Privacy Policy</h6>
                    <p>Your submission of personal information through the store is governed by our Privacy Policy.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Information We Collect</h6>
                    <p>We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us.</p>
                    
                    <h6>How We Use Your Information</h6>
                    <p>We use the information we collect to provide, maintain, and improve our services, to process transactions, and to communicate with you.</p>
                    
                    <h6>Security</h6>
                    <p>We implement reasonable security measures to protect your personal information from unauthorized access or disclosure.</p>
                    
                    <h6>Third-Party Services</h6>
                    <p>We may use third-party services to process payments and deliver products. These services have their own privacy policies.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
            };
            
            // Update requirement indicators
            Object.keys(requirements).forEach(key => {
                const element = document.getElementById(`req${key.charAt(0).toUpperCase() + key.slice(1)}`);
                if (element) {
                    if (requirements[key]) {
                        element.classList.add('met');
                        element.innerHTML = `<i class="fas fa-check-circle me-1"></i>${element.textContent.replace('●', '')}`;
                        strength++;
                    } else {
                        element.classList.remove('met');
                        element.innerHTML = `<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>${element.textContent.replace('✓', '')}`;
                    }
                }
            });
            
            // Update strength meter
            const strengthBar = document.getElementById('passwordStrength');
            strengthBar.className = 'password-strength';
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength === 3) {
                strengthBar.classList.add('strength-medium');
            } else if (strength === 4) {
                strengthBar.classList.add('strength-strong');
            } else if (strength === 5) {
                strengthBar.classList.add('strength-very-strong');
            }
            
            return requirements;
        }
        
        // Password toggle visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            if (confirmInput.type === 'password') {
                confirmInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                confirmInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Real-time password strength and match checking
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchMessage = document.getElementById('passwordMatchMessage');
            
            if (confirmPassword === '') {
                matchMessage.textContent = '';
                matchMessage.className = 'form-text mt-1';
                return;
            }
            
            if (password === confirmPassword) {
                matchMessage.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i>Passwords match';
                matchMessage.className = 'form-text mt-1 text-success';
            } else {
                matchMessage.innerHTML = '<i class="fas fa-times-circle me-1 text-danger"></i>Passwords do not match';
                matchMessage.className = 'form-text mt-1 text-danger';
            }
        }
        
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        // Form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            // Check if form is valid
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            this.classList.add('was-validated');
            
            // Custom validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const phone = document.getElementById('phone_cell').value;
            
            // Password match validation
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            // Password strength validation
            const requirements = checkPasswordStrength(password);
            if (!requirements.length || !requirements.uppercase || !requirements.lowercase || !requirements.number) {
                e.preventDefault();
                alert('Password must meet all requirements (8+ characters, uppercase, lowercase, and number)!');
                return false;
            }
            
            // Phone number validation (basic)
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            const cleanedPhone = phone.replace(/[\s\-\(\)]/g, '');
            if (!phoneRegex.test(cleanedPhone) && phone !== '') {
                e.preventDefault();
                alert('Please enter a valid phone number!');
                return false;
            }
            
            // Email validation
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address!');
                return false;
            }
            
            // Terms agreement validation
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                e.preventDefault();
                alert('You must agree to the terms and conditions!');
                return false;
            }
        });
        
        // Real-time validation feedback
        const inputs = document.querySelectorAll('#signupForm input, #signupForm select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() !== '' || this.type === 'checkbox') {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else if (this.hasAttribute('required')) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
            
            input.addEventListener('input', function() {
                if (this.value.trim() !== '' || this.checked) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>