<?php
// signup.php - User Registration with Luxury Theme
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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ===== SIGNUP PAGE LUXURY THEME ===== */
        :root {
            --gold: #D4AF37;
            --gold-light: #F4E5C1;
            --gold-dark: #AA8C2F;
            --navy: #1A2A4F;
            --navy-light: #2A3F6F;
            --navy-dark: #0F1A2F;
            --pearl: #F8F6F0;
            --charcoal: #36454F;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            position: relative;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212,175,55,0.1) 0%, transparent 70%);
            animation: rotate 60s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Main content wrapper - THIS IS KEY FOR FOOTER POSITIONING */
        .main-wrapper {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            position: relative;
            z-index: 2;
            width: 100%;
        }

        .signup-container {
            max-width: 650px;
            width: 100%;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Luxury Card */
        .luxury-card {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            border: 2px solid var(--gold);
            position: relative;
        }

        .luxury-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--gold-light), var(--gold), var(--gold-dark));
            z-index: 3;
        }

        /* Card Header */
        .card-header-luxury {
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            padding: 1.8rem 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header-luxury::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212,175,55,0.15) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        .brand-icon {
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, var(--gold-light), var(--gold));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.8rem;
            font-size: 1.8rem;
            color: var(--navy);
            position: relative;
            z-index: 2;
            animation: sparkle 2s infinite;
            border: 3px solid rgba(255,255,255,0.3);
        }

        @keyframes sparkle {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 0 20px var(--gold);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 0 40px var(--gold), 0 0 60px var(--gold-light);
            }
        }

        .card-header-luxury h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.2rem;
            position: relative;
            z-index: 2;
            letter-spacing: -0.02em;
        }

        .card-header-luxury p {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.1rem;
            color: var(--gold-light);
            position: relative;
            z-index: 2;
            font-style: italic;
            margin-bottom: 0;
        }

        /* Card Body */
        .card-body-luxury {
            padding: 1.8rem;
            background: white;
        }

        /* Form Labels */
        .form-label {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .form-label i {
            color: var(--gold);
            font-size: 0.8rem;
        }

        .required::after {
            content: "*";
            color: var(--gold);
            margin-left: 0.2rem;
            font-size: 0.9rem;
        }

        /* Form Controls */
        .form-control, .form-select {
            border: 2px solid rgba(212,175,55,0.2);
            border-radius: 10px;
            padding: 0.6rem 0.9rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            transition: var(--transition);
            background: var(--pearl);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212,175,55,0.15);
            outline: none;
            background: white;
        }

        .input-group {
            border-radius: 10px;
            overflow: hidden;
        }

        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group .btn-outline-luxury {
            border: 2px solid rgba(212,175,55,0.2);
            border-left: none;
            background: var(--pearl);
            color: var(--gold);
            padding: 0 0.9rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .input-group .btn-outline-luxury:hover {
            background: var(--gold);
            color: var(--navy);
            border-color: var(--gold);
        }

        /* Form Text */
        .form-text {
            font-family: 'Cormorant Garamond', serif;
            color: var(--charcoal);
            font-size: 0.8rem;
            margin-top: 0.2rem;
            font-style: italic;
        }

        /* Password Strength */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.4rem;
            background: #eee;
            overflow: hidden;
            position: relative;
        }

        .password-strength::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--strength-width, 0%);
            background: linear-gradient(90deg, var(--gold-light), var(--gold));
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        /* Password Requirements */
        .password-requirements {
            background: var(--pearl);
            padding: 0.7rem;
            border-radius: 10px;
            margin-top: 0.7rem;
            border: 1px solid rgba(212,175,55,0.2);
        }

        .requirement {
            font-size: 0.75rem;
            color: var(--charcoal);
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition);
        }

        .requirement:last-child {
            margin-bottom: 0;
        }

        .requirement i {
            font-size: 0.45rem;
            color: var(--charcoal);
            transition: var(--transition);
            width: 14px;
        }

        .requirement.met {
            color: var(--navy);
        }

        .requirement.met i {
            color: var(--gold);
            font-size: 0.8rem;
        }

        /* Alerts */
        .alert-luxury {
            border: none;
            border-radius: 10px;
            padding: 0.9rem 1.1rem;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            animation: slideDown 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .alert-luxury::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .alert-danger {
            background: #FFEBEE;
            color: #B71C1C;
            border: 1px solid #FFCDD2;
        }

        .alert-danger::before {
            background: #C62828;
        }

        .alert-success {
            background: #E8F5E9;
            color: #1B5E20;
            border: 1px solid #C8E6C9;
        }

        .alert-success::before {
            background: var(--gold);
        }

        .alert-luxury i {
            font-size: 1.1rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Submit Button */
        .btn-luxury {
            background: linear-gradient(135deg, var(--gold), var(--gold-dark));
            border: none;
            color: var(--navy);
            font-weight: 700;
            padding: 0.9rem;
            border-radius: 50px;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            width: 100%;
            margin: 0.8rem 0 0.6rem;
            border: 1px solid transparent;
        }

        .btn-luxury::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-luxury:hover::before {
            width: 400px;
            height: 400px;
        }

        .btn-luxury:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(212,175,55,0.3);
        }

        .btn-luxury i {
            margin-right: 0.4rem;
        }

        /* Checkbox */
        .form-check {
            margin-bottom: 0.7rem;
        }

        .form-check-input {
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(212,175,55,0.3);
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.1rem;
        }

        .form-check-input:checked {
            background-color: var(--gold);
            border-color: var(--gold);
        }

        .form-check-label {
            color: var(--charcoal);
            font-size: 0.85rem;
            cursor: pointer;
        }

        .terms-link {
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border-bottom: 1px solid transparent;
        }

        .terms-link:hover {
            color: var(--navy);
            border-bottom-color: var(--gold);
        }

        /* Login Link */
        .login-link {
            color: var(--gold);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
            border-bottom: 2px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.9rem;
        }

        .login-link:hover {
            color: var(--navy);
            border-bottom-color: var(--gold);
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 0.8rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(212,175,55,0.2);
        }

        .divider span {
            padding: 0 0.8rem;
            color: var(--gold);
            font-family: 'Cormorant Garamond', serif;
            font-size: 0.85rem;
        }

        /* Footer note */
        .footer-note {
            text-align: center;
            margin-top: 0.8rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(212,175,55,0.1);
        }

        .footer-note p {
            color: var(--charcoal);
            font-size: 0.75rem;
            margin-bottom: 0;
        }

        .footer-note i {
            color: var(--gold);
            margin-right: 0.3rem;
            font-size: 0.65rem;
        }

        /* Spacing adjustments */
        .mb-4 {
            margin-bottom: 1rem !important;
        }

        .mb-3 {
            margin-bottom: 0.8rem !important;
        }

        .row {
            margin-bottom: 0;
        }

        /* Footer styling - ensure it's visible */
        footer, .footer {
            position: relative;
            z-index: 2;
            width: 100%;
            flex-shrink: 0;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 20px;
            overflow: hidden;
            border: 2px solid var(--gold);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: white;
            border-bottom: 2px solid var(--gold);
            padding: 1rem 1.2rem;
        }

        .modal-header .modal-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .modal-header .btn-close {
            background: white;
            opacity: 0.8;
        }

        .modal-body {
            padding: 1.2rem;
        }

        .modal-body h6 {
            font-family: 'Playfair Display', serif;
            color: var(--navy);
            font-weight: 700;
            margin-top: 0.8rem;
            margin-bottom: 0.2rem;
            font-size: 0.95rem;
        }

        .modal-body h6:first-child {
            margin-top: 0;
        }

        .modal-body p {
            color: var(--charcoal);
            font-family: 'Cormorant Garamond', serif;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 0.6rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(212,175,55,0.2);
            padding: 1rem 1.2rem;
        }

        .modal-footer .btn-secondary {
            background: var(--pearl);
            color: var(--navy);
            border: 2px solid var(--gold);
            border-radius: 50px;
            padding: 0.4rem 1.5rem;
            font-weight: 600;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .modal-footer .btn-secondary:hover {
            background: var(--gold);
            color: var(--navy);
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-wrapper {
                padding: 1rem;
            }
            
            .card-header-luxury {
                padding: 1.5rem 1rem;
            }
            
            .card-header-luxury h2 {
                font-size: 1.8rem;
            }
            
            .card-header-luxury p {
                font-size: 1rem;
            }
            
            .card-body-luxury {
                padding: 1.2rem;
            }
            
            .brand-icon {
                width: 55px;
                height: 55px;
                font-size: 1.6rem;
            }
        }

        @media (max-width: 576px) {
            .main-wrapper {
                padding: 0.8rem;
            }
            
            .card-header-luxury {
                padding: 1.2rem 0.8rem;
            }
            
            .card-header-luxury h2 {
                font-size: 1.5rem;
            }
            
            .card-body-luxury {
                padding: 1rem;
            }
            
            .btn-luxury {
                padding: 0.7rem;
                font-size: 0.9rem;
            }
        }

        /* Loading State */
        .btn-luxury.loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }

        .btn-luxury.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid var(--navy);
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Main content wrapper - pushes footer down -->
    <div class="main-wrapper">
        <div class="signup-container">
            <!-- Luxury Card -->
            <div class="luxury-card">
                <div class="card-header-luxury">
                    <div class="brand-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h2>Create Your Account</h2>
                    <p>Join our exclusive circle of elegance</p>
                </div>
                
                <div class="card-body-luxury">
                    <!-- Alert Messages -->
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert-luxury alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert-luxury alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Signup Form -->
                    <form action="process_signup.php" method="POST" id="signupForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Name Fields -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="first_name" class="form-label required">
                                    <i class="fas fa-user"></i> First Name
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       required placeholder="Enter your first name">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label required">
                                    <i class="fas fa-user"></i> Last Name
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       required placeholder="Enter your last name">
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="form-label required">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   placeholder="example@domain.com">
                            <div class="form-text">
                                <i class="fas fa-gem me-1"></i>We'll send OTP to this email for verification
                            </div>
                        </div>
                        
                        <!-- Mobile Number -->
                        <div class="mb-4">
                            <label for="phone_cell" class="form-label required">
                                <i class="fas fa-phone-alt"></i> Mobile Number
                            </label>
                            <input type="tel" class="form-control" id="phone_cell" name="phone_cell" required
                                   placeholder="+1 (555) 123-4567">
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label required">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" 
                                       required minlength="8" placeholder="Create a strong password">
                                <button class="btn-outline-luxury" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                            
                            <!-- Password Requirements -->
                            <div class="password-requirements">
                                <div class="requirement" id="reqLength">
                                    <i class="fas fa-circle"></i> At least 8 characters
                                </div>
                                <div class="requirement" id="reqUppercase">
                                    <i class="fas fa-circle"></i> At least one uppercase letter
                                </div>
                                <div class="requirement" id="reqLowercase">
                                    <i class="fas fa-circle"></i> At least one lowercase letter
                                </div>
                                <div class="requirement" id="reqNumber">
                                    <i class="fas fa-circle"></i> At least one number
                                </div>
                                <div class="requirement" id="reqSpecial">
                                    <i class="fas fa-circle"></i> At least one special character
                                </div>
                            </div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label required">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required placeholder="Confirm your password">
                                <button class="btn-outline-luxury" type="button" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatchMessage" class="form-text mt-2"></div>
                        </div>
                        
                        <!-- Address Fields -->
                        <div class="mb-4">
                            <label for="address_street" class="form-label">
                                <i class="fas fa-home"></i> Street Address
                            </label>
                            <input type="text" class="form-control" id="address_street" name="address_street"
                                   placeholder="123 Main Street">
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="address_city" class="form-label">
                                    <i class="fas fa-city"></i> City
                                </label>
                                <input type="text" class="form-control" id="address_city" name="address_city"
                                       placeholder="New York">
                            </div>
                            <div class="col-md-6">
                                <label for="address_country" class="form-label">
                                    <i class="fas fa-globe"></i> Country
                                </label>
                                <select class="form-select" id="address_country" name="address_country">
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
                        
                        <!-- Newsletter -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                                <label class="form-check-label" for="newsletter">
                                    <i class="fas fa-gem me-1" style="color: var(--gold);"></i>
                                    Subscribe to our newsletter for exclusive updates
                                </label>
                            </div>
                        </div>
                        
                        <!-- Terms -->
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the 
                                <a href="#" class="terms-link" data-bs-toggle="modal" data-bs-target="#termsModal">
                                    Terms & Conditions
                                </a> 
                                and 
                                <a href="#" class="terms-link" data-bs-toggle="modal" data-bs-target="#privacyModal">
                                    Privacy Policy
                                </a>
                            </label>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn-luxury" name="signup_btn" id="signupBtn">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                        
                        <!-- Divider -->
                        <div class="divider">
                            <span>Already have an account?</span>
                        </div>
                        
                        <!-- Login Link -->
                        <div class="text-center">
                            <a href="login.php" class="login-link">
                                <i class="fas fa-sign-in-alt"></i>
                                Sign In Here
                            </a>
                        </div>
                        
                        <!-- Footer Note -->
                        <div class="footer-note">
                            <p>
                                <i class="fas fa-gem"></i>
                                Your information is secure with us
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FOOTER - This will now be visible -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-gem me-2" style="color: var(--gold);"></i>
                        Terms & Conditions
                    </h5>
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
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-gem me-2" style="color: var(--gold);"></i>
                        Privacy Policy
                    </h5>
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

    <!-- Bootstrap JS -->
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
                        element.innerHTML = `<i class="fas fa-check-circle"></i> ${element.textContent.split(' ').slice(1).join(' ')}`;
                        strength++;
                    } else {
                        element.classList.remove('met');
                        element.innerHTML = `<i class="fas fa-circle" style="font-size: 0.45rem;"></i> ${element.textContent.split(' ').slice(1).join(' ')}`;
                    }
                }
            });
            
            // Update strength meter
            const strengthBar = document.getElementById('passwordStrength');
            const width = (strength / 5) * 100;
            strengthBar.style.setProperty('--strength-width', width + '%');
            
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
                return;
            }
            
            if (password === confirmPassword) {
                matchMessage.innerHTML = '<i class="fas fa-check-circle" style="color: var(--gold);"></i> Passwords match';
                matchMessage.style.color = 'var(--gold)';
            } else {
                matchMessage.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Passwords do not match';
                matchMessage.style.color = '#dc3545';
            }
        }
        
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        // Form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            document.getElementById('signupBtn').classList.add('loading');
        });
        
        // Auto-dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.querySelectorAll('.alert-luxury').forEach(function(alert) {
                    alert.style.display = 'none';
                });
            }, 5000);
        });
    </script>
</body>
</html>