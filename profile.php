<?php
// profile.php - User profile page

// Include session handler
require_once 'session_handler.php';

// Include database
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($connection, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Initialize variables
$success_msg = '';
$error_msg = '';

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = mysqli_real_escape_string($connection, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($connection, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $phone = mysqli_real_escape_string($connection, trim($_POST['phone']));
    $address = mysqli_real_escape_string($connection, trim($_POST['address']));
    $city = mysqli_real_escape_string($connection, trim($_POST['city']));
    $state = mysqli_real_escape_string($connection, trim($_POST['state']));
    $zip_code = mysqli_real_escape_string($connection, trim($_POST['zip_code']));
    $country = mysqli_real_escape_string($connection, trim($_POST['country']));
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        // Check if email already exists (excluding current user)
        $email_check_query = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id";
        $email_check_result = mysqli_query($connection, $email_check_query);
        
        if (mysqli_num_rows($email_check_result) > 0) {
            $error_msg = "Email address is already registered by another user.";
        } else {
            // Update user profile
            $update_query = "UPDATE users SET 
                            first_name = '$first_name',
                            last_name = '$last_name',
                            email = '$email',
                            phone_cell = '$phone',
                            address_street = '$address',
                            address_city = '$city',
                            address_state = '$state',
                            address_zip = '$zip_code',
                            address_country = '$country'
                            WHERE user_id = $user_id";
            
            if (mysqli_query($connection, $update_query)) {
                $success_msg = "Profile updated successfully!";
                // Refresh user data
                $user_result = mysqli_query($connection, $user_query);
                $user = mysqli_fetch_assoc($user_result);
                
                // Update session name
                $_SESSION['full_name'] = $first_name . ' ' . $last_name;
            } else {
                $error_msg = "Error updating profile. Please try again.";
            }
        }
    }
}

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (!password_verify($current_password, $user['password_hash'])) {
        $error_msg = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $password_update_query = "UPDATE users SET 
                                 password_hash = '$hashed_password'
                                 WHERE user_id = $user_id";
        
        if (mysqli_query($connection, $password_update_query)) {
            $success_msg = "Password changed successfully!";
        } else {
            $error_msg = "Error changing password. Please try again.";
        }
    }
}

// Get recent orders count
$recent_orders_query = "SELECT COUNT(*) as order_count FROM orders 
                        WHERE user_id = $user_id 
                        AND order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$recent_orders_result = mysqli_query($connection, $recent_orders_query);
$recent_orders = mysqli_fetch_assoc($recent_orders_result);

// Include header
include 'includes/header.php';
?>

<style>
/* ===== PROFILE PAGE LUXURY THEME ===== */
:root {
    --gold: #D4AF37;
    --gold-light: #F4E5C1;
    --gold-dark: #AA8C2F;
    --navy: #1A2A4F;
    --navy-light: #2A3F6F;
    --navy-dark: #0F1A2F;
    --pearl: #F8F6F0;
    --charcoal: #36454F;
    --transition: all 0.3s ease;
}

.profile-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

/* Page Header */
.page-header {
    margin-bottom: 2rem;
    text-align: center;
}

.page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.page-header p {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
    color: var(--charcoal);
    font-style: italic;
}

.header-decoration {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.header-decoration span {
    width: 60px;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
}

.header-decoration i {
    color: var(--gold);
    font-size: 1.2rem;
    animation: sparkle 2s infinite;
}

@keyframes sparkle {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.1); }
}

/* Profile Sidebar Card */
.profile-sidebar-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(212,175,55,0.2);
    margin-bottom: 1.5rem;
}

.profile-avatar-wrapper {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    padding: 2rem 1.5rem;
    text-align: center;
    border-bottom: 2px solid var(--gold);
    position: relative;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--navy);
    border: 3px solid white;
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    font-family: 'Playfair Display', serif;
}

.profile-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.3rem;
}

.profile-email {
    color: rgba(255,255,255,0.9);
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
}

.profile-member-since {
    color: var(--gold-light);
    font-size: 0.8rem;
    font-style: italic;
}

.profile-stats {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(212,175,55,0.2);
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px dashed #eee;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    color: var(--charcoal);
    font-size: 0.9rem;
}

.stat-value {
    font-weight: 700;
    color: var(--navy);
    background: var(--pearl);
    padding: 0.2rem 0.8rem;
    border-radius: 30px;
    font-size: 0.9rem;
}

.profile-actions {
    padding: 1.5rem;
}

.btn-profile {
    width: 100%;
    padding: 0.8rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: var(--transition);
    margin-bottom: 0.8rem;
    border: none;
}

.btn-profile:last-child {
    margin-bottom: 0;
}

.btn-profile.orders {
    background: var(--navy);
    color: white;
}

.btn-profile.orders:hover {
    background: var(--navy-light);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(26,42,79,0.3);
}

.btn-profile.wishlist {
    background: var(--pearl);
    color: var(--navy);
    border: 1px solid var(--gold);
}

.btn-profile.wishlist:hover {
    background: var(--gold);
    color: var(--navy);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212,175,55,0.3);
}

.btn-profile.logout {
    background: transparent;
    color: #c00;
    border: 1px solid #c00;
}

.btn-profile.logout:hover {
    background: #c00;
    color: white;
    transform: translateY(-2px);
}

/* Main Content Card */
.profile-content-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(212,175,55,0.2);
    margin-bottom: 1.5rem;
}

.content-header {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    padding: 1rem 1.5rem;
    border-bottom: 2px solid var(--gold);
}

.content-header h5 {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.content-header i {
    color: var(--gold);
}

.content-body {
    padding: 1.5rem;
}

/* Form Styles */
.form-label {
    font-weight: 600;
    color: var(--navy);
    margin-bottom: 0.3rem;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.form-label i {
    color: var(--gold);
    font-size: 0.9rem;
}

.form-control {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0.6rem 0.8rem;
    transition: var(--transition);
}

.form-control:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(212,175,55,0.1);
    outline: none;
}

.form-text {
    font-size: 0.8rem;
    color: var(--charcoal);
    margin-top: 0.2rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    border: none;
    color: var(--navy);
    font-weight: 600;
    padding: 0.6rem 1.5rem;
    border-radius: 8px;
    transition: var(--transition);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(212,175,55,0.3);
}

.btn-primary i {
    margin-right: 0.3rem;
}

/* Alerts */
.alert {
    border: none;
    border-radius: 12px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
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

.alert-success {
    background: #E8F5E9;
    color: #1B5E20;
    border-left: 4px solid #2E7D32;
}

.alert-danger {
    background: #FFEBEE;
    color: #B71C1C;
    border-left: 4px solid #C62828;
}

.alert i {
    font-size: 1.1rem;
}

.btn-close {
    margin-left: auto;
    opacity: 0.7;
    cursor: pointer;
}

/* Stats Badge */
.badge-stats {
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    color: var(--navy);
    padding: 0.4rem 0.8rem;
    border-radius: 30px;
    font-weight: 600;
    font-size: 0.8rem;
}

/* Responsive */
@media (max-width: 991px) {
    .profile-container {
        margin: 1rem auto;
    }
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .profile-avatar-wrapper {
        padding: 1.5rem;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 2rem;
    }
    
    .profile-name {
        font-size: 1.3rem;
    }
}

@media (max-width: 576px) {
    .page-header h1 {
        font-size: 1.8rem;
    }
    
    .page-header p {
        font-size: 1rem;
    }
    
    .content-body {
        padding: 1rem;
    }
    
    .btn-profile {
        padding: 0.7rem;
        font-size: 0.9rem;
    }
}
</style>

<div class="profile-container">
    <!-- Page Header -->
    <div class="page-header" data-aos="fade-up">
        <h1>My Profile</h1>
        <p>Manage your personal information and preferences</p>
        <div class="header-decoration">
            <span></span>
            <i class="fas fa-gem"></i>
            <span></span>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Profile Sidebar -->
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="profile-sidebar-card" data-aos="fade-right">
                <div class="profile-avatar-wrapper">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                    <h4 class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    <div class="profile-member-since">Member since <?php echo date('M Y', strtotime($user['registration_date'])); ?></div>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total Orders</span>
                        <?php 
                        $total_orders_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id";
                        $total_orders_result = mysqli_query($connection, $total_orders_query);
                        $total_orders = mysqli_fetch_assoc($total_orders_result);
                        ?>
                        <span class="stat-value"><?php echo $total_orders['total']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Recent Orders (30 days)</span>
                        <span class="stat-value"><?php echo $recent_orders['order_count']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Account Status</span>
                        <span class="badge-stats">Active</span>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="orders.php" class="btn-profile orders">
                        <i class="fas fa-shopping-bag"></i> My Orders
                    </a>
                    <a href="wishlist.php" class="btn-profile wishlist">
                        <i class="fas fa-heart"></i> My Wishlist
                    </a>
                    <a href="logout.php" class="btn-profile logout" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Profile Forms -->
        <div class="col-lg-8">
            <!-- Messages -->
            <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" data-aos="fade-up">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" data-aos="fade-up">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Edit Profile Form -->
            <div class="profile-content-card" data-aos="fade-up">
                <div class="content-header">
                    <h5><i class="fas fa-user-edit"></i> Edit Profile</h5>
                </div>
                <div class="content-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user"></i> First Name *
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">
                                    <i class="fas fa-user"></i> Last Name *
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email Address *
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i> Phone Number
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone_cell'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">
                                <i class="fas fa-home"></i> Address
                            </label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($user['address_street'] ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($user['address_city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state" 
                                       value="<?php echo htmlspecialchars($user['address_state'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                       value="<?php echo htmlspecialchars($user['address_zip'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" 
                                       value="<?php echo htmlspecialchars($user['address_country'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Form -->
            <div class="profile-content-card" data-aos="fade-up" data-aos-delay="100">
                <div class="content-header">
                    <h5><i class="fas fa-key"></i> Change Password</h5>
                </div>
                <div class="content-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                <i class="fas fa-lock"></i> Current Password *
                            </label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-key"></i> New Password *
                                </label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-check-circle"></i> Confirm New Password *
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });
</script>

<?php include 'includes/footer.php'; ?>