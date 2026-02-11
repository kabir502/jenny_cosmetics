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
                            phone = '$phone',
                            address = '$address',
                            city = '$city',
                            state = '$state',
                            zip_code = '$zip_code',
                            country = '$country',
                            updated_at = NOW()
                            WHERE user_id = $user_id";
            
            if (mysqli_query($connection, $update_query)) {
                $success_msg = "Profile updated successfully!";
                // Refresh user data
                $user_result = mysqli_query($connection, $user_query);
                $user = mysqli_fetch_assoc($user_result);
                
                // Update session name
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
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
    if (!password_verify($current_password, $user['password'])) {
        $error_msg = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $password_update_query = "UPDATE users SET 
                                 password = '$hashed_password',
                                 updated_at = NOW()
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

<div class="container my-5">
    <div class="row">
        <div class="col-lg-4">
            <!-- Profile Sidebar -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="profile-avatar bg-primary text-white d-flex align-items-center justify-content-center mx-auto rounded-circle" 
                             style="width: 100px; height: 100px; font-size: 2.5rem;">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                    </div>
                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="text-muted">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                    
                    <div class="d-grid gap-2 mt-4">
                        <a href="orders.php" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-bag me-2"></i>My Orders
                        </a>
                        <a href="wishlist.php" class="btn btn-outline-secondary">
                            <i class="fas fa-heart me-2"></i>My Wishlist
                        </a>
                        <a href="logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Overview</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Orders
                            <span class="badge bg-primary rounded-pill">
                                <?php 
                                $total_orders_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = $user_id";
                                $total_orders_result = mysqli_query($connection, $total_orders_query);
                                $total_orders = mysqli_fetch_assoc($total_orders_result);
                                echo $total_orders['total'];
                                ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Recent Orders (30 days)
                            <span class="badge bg-success rounded-pill">
                                <?php echo $recent_orders['order_count']; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Account Status
                            <span class="badge bg-success rounded-pill">Active</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Messages -->
            <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Profile Edit Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state" 
                                       value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                       value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" 
                                       value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-weight: bold;
}

.list-group-item {
    border: none;
    padding: 0.75rem 0;
}

.card {
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}
</style>

<?php include 'includes/footer.php'; ?>