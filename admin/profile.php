<?php
// admin/profile.php - Admin Profile Management Page

// Include central session handler from root
require_once '../session_handler.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database
require_once '../config/database.php';
require_once '../config/constants.php';

// Initialize variables
$message = '';
$message_type = '';
$admin_id = $_SESSION['admin_id'] ?? 1; // Default to admin ID 1 if not set

// Get admin details
$admin_query = "SELECT * FROM administrators WHERE admin_id = ?";
$admin_stmt = mysqli_prepare($connection, $admin_query);
mysqli_stmt_bind_param($admin_stmt, "i", $admin_id);
mysqli_stmt_execute($admin_stmt);
$admin_result = mysqli_stmt_get_result($admin_stmt);
$admin = mysqli_fetch_assoc($admin_result);

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($connection, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($connection, trim($_POST['email']));
    $phone = mysqli_real_escape_string($connection, trim($_POST['phone'] ?? ''));
    $username = mysqli_real_escape_string($connection, trim($_POST['username']));
    
    // Check if username already exists for another admin
    $check_username = "SELECT admin_id FROM administrators WHERE username = ? AND admin_id != ?";
    $check_stmt = mysqli_prepare($connection, $check_username);
    mysqli_stmt_bind_param($check_stmt, "si", $username, $admin_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $message = "Username already exists. Please choose another.";
        $message_type = 'danger';
    } else {
        // Check if email already exists for another admin
        $check_email = "SELECT admin_id FROM administrators WHERE email = ? AND admin_id != ?";
        $check_stmt = mysqli_prepare($connection, $check_email);
        mysqli_stmt_bind_param($check_stmt, "si", $email, $admin_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $message = "Email already exists. Please use another.";
            $message_type = 'danger';
        } else {
            $update_query = "UPDATE administrators SET full_name = ?, email = ?, phone = ?, username = ? WHERE admin_id = ?";
            $update_stmt = mysqli_prepare($connection, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssssi", $full_name, $email, $phone, $username, $admin_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Update session variables
                $_SESSION['admin_name'] = $full_name;
                
                $message = "Profile updated successfully.";
                $message_type = 'success';
                
                // Refresh admin data
                $admin['full_name'] = $full_name;
                $admin['email'] = $email;
                $admin['phone'] = $phone;
                $admin['username'] = $username;
                
                // Log the action
                error_log("Admin ID: $admin_id updated their profile");
            } else {
                $message = "Failed to update profile. " . mysqli_error($connection);
                $message_type = 'danger';
            }
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $password_query = "SELECT password_hash FROM administrators WHERE admin_id = ?";
    $password_stmt = mysqli_prepare($connection, $password_query);
    mysqli_stmt_bind_param($password_stmt, "i", $admin_id);
    mysqli_stmt_execute($password_stmt);
    $password_result = mysqli_stmt_get_result($password_stmt);
    $admin_data = mysqli_fetch_assoc($password_result);
    
    if (!password_verify($current_password, $admin_data['password_hash'])) {
        $message = "Current password is incorrect.";
        $message_type = 'danger';
    } elseif (strlen($new_password) < 8) {
        $message = "New password must be at least 8 characters long.";
        $message_type = 'danger';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $message = "Password must contain at least one uppercase letter.";
        $message_type = 'danger';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $message = "Password must contain at least one lowercase letter.";
        $message_type = 'danger';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $message = "Password must contain at least one number.";
        $message_type = 'danger';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        $message = "Password must contain at least one special character.";
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
        $message_type = 'danger';
    } else {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE administrators SET password_hash = ? WHERE admin_id = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $password_hash, $admin_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Password changed successfully.";
            $message_type = 'success';
            
            // Log the action
            error_log("Admin ID: $admin_id changed their password");
        } else {
            $message = "Failed to change password. " . mysqli_error($connection);
            $message_type = 'danger';
        }
    }
}

// Handle Profile Picture Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $target_dir = "../assets/images/admin/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Check file size (max 2MB)
            if ($_FILES["profile_photo"]["size"] > 2 * 1024 * 1024) {
                $message = "File size must be less than 2MB.";
                $message_type = 'danger';
            } else {
                $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                    // Delete old profile photo if exists and not default
                    if (!empty($admin['profile_photo']) && file_exists('../' . $admin['profile_photo'])) {
                        unlink('../' . $admin['profile_photo']);
                    }
                    
                    $photo_path = 'assets/images/admin/' . $new_filename;
                    
                    // Update database - you may need to add profile_photo column to administrators table
                    // For now, we'll just store in session
                    $_SESSION['admin_avatar'] = '../' . $photo_path;
                    
                    $message = "Profile photo uploaded successfully.";
                    $message_type = 'success';
                } else {
                    $message = "Failed to upload profile photo.";
                    $message_type = 'danger';
                }
            }
        } else {
            $message = "Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.";
            $message_type = 'danger';
        }
    } else {
        $message = "Please select a file to upload.";
        $message_type = 'danger';
    }
}

// Get admin activity log
$activity_query = "SELECT * FROM audit_logs WHERE admin_id = ? ORDER BY created_at DESC LIMIT 10";
$activity_stmt = mysqli_prepare($connection, $activity_query);
mysqli_stmt_bind_param($activity_stmt, "i", $admin_id);
mysqli_stmt_execute($activity_stmt);
$activity_result = mysqli_stmt_get_result($activity_stmt);

// Include admin header
include '../includes/admin_header.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header-box">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-user-circle me-2"></i>
                            My Profile
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Profile</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Card - Left Column -->
        <div class="col-lg-4 mb-4">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar-wrapper">
                        <div class="profile-avatar">
                            <?php 
                            $avatar_letter = strtoupper(substr($admin['full_name'] ?? $admin['username'], 0, 1));
                            $avatar_color = '#' . substr(md5($admin['username']), 0, 6);
                            ?>
                            <span style="background-color: <?php echo $avatar_color; ?>; color: white; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 600;">
                                <?php echo $avatar_letter; ?>
                            </span>
                        </div>
                        <div class="profile-avatar-upload" onclick="document.getElementById('photoInput').click();">
                            <i class="fas fa-camera"></i>
                        </div>
                        <form method="POST" enctype="multipart/form-data" id="photoUploadForm" style="display: none;">
                            <input type="file" id="photoInput" name="profile_photo" accept="image/*" onchange="this.form.submit();">
                            <input type="hidden" name="upload_photo" value="1">
                        </form>
                    </div>
                    <h3 class="profile-name"><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></h3>
                    <p class="profile-role">
                        <span class="role-badge"><?php echo $admin['role'] ?? 'Administrator'; ?></span>
                    </p>
                    <p class="profile-username">@<?php echo $admin['username']; ?></p>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Member Since</span>
                            <span class="stat-value"><?php echo date('M d, Y', strtotime($admin['created_at'] ?? 'now')); ?></span>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Last Login</span>
                            <span class="stat-value">
                                <?php 
                                if (!empty($admin['last_login'])) {
                                    echo date('M d, Y h:i A', strtotime($admin['last_login']));
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Status</span>
                            <span class="stat-value status-active">
                                <i class="fas fa-check-circle me-1"></i>Active
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-contact">
                    <h5>Contact Information</h5>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo $admin['email']; ?></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo !empty($admin['phone']) ? $admin['phone'] : 'Not provided'; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Tabs for Profile Edit, Password Change, Activity -->
        <div class="col-lg-8 mb-4">
            <div class="profile-tabs-card">
                <ul class="nav nav-tabs profile-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                            <i class="fas fa-lock me-2"></i>Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">
                            <i class="fas fa-history me-2"></i>Activity Log
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content profile-tab-content" id="profileTabsContent">
                    <!-- Edit Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <form method="POST" action="" class="profile-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                                    <small class="text-muted">Username must be unique</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="<?php echo $admin['role'] ?? 'Administrator'; ?>" readonly disabled>
                                    <small class="text-muted">Role cannot be changed</small>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <form method="POST" action="" class="profile-form" id="passwordForm">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <div class="password-requirements">
                                        <h6>Password Requirements:</h6>
                                        <ul class="requirements-list">
                                            <li id="req-length"><i class="fas fa-circle"></i> At least 8 characters</li>
                                            <li id="req-uppercase"><i class="fas fa-circle"></i> One uppercase letter</li>
                                            <li id="req-lowercase"><i class="fas fa-circle"></i> One lowercase letter</li>
                                            <li id="req-number"><i class="fas fa-circle"></i> One number</li>
                                            <li id="req-special"><i class="fas fa-circle"></i> One special character (!@#$%^&*)</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="current_password" class="form-label">Current Password *</label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">New Password *</label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                    <div class="password-input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div id="passwordMatch" class="mb-3"></div>
                                    <button type="submit" name="change_password" class="btn btn-warning" id="changePasswordBtn">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Activity Log Tab -->
                    <div class="tab-pane fade" id="activity" role="tabpanel">
                        <div class="activity-log">
                            <?php if ($activity_result && mysqli_num_rows($activity_result) > 0): ?>
                                <?php while ($log = mysqli_fetch_assoc($activity_result)): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?php 
                                            echo $log['action_type'] == 'Admin Login' ? 'sign-in-alt' : 
                                                ($log['action_type'] == 'Admin Logout' ? 'sign-out-alt' : 'edit'); 
                                        ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <strong><?php echo $log['action_type']; ?></strong>
                                        </div>
                                        <div class="activity-meta">
                                            <span class="activity-time">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                            </span>
                                            <?php if (!empty($log['ip_address'])): ?>
                                            <span class="activity-ip">
                                                <i class="fas fa-network-wired me-1"></i>
                                                <?php echo $log['ip_address']; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($log['table_name'])): ?>
                                        <div class="activity-details small text-muted">
                                            Table: <?php echo $log['table_name']; ?> | Record: <?php echo $log['record_id']; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-history" style="font-size: 3rem; color: var(--light-gray);"></i>
                                    <p class="mt-3 mb-0">No activity logs found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Session Information -->
    <div class="row">
        <div class="col-12">
            <div class="session-card">
                <div class="session-header">
                    <h5><i class="fas fa-info-circle me-2"></i>Session Information</h5>
                </div>
                <div class="session-body">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-2">
                            <span class="session-label">Session ID:</span>
                            <span class="session-value"><?php echo substr(session_id(), 0, 8) . '...'; ?></span>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <span class="session-label">IP Address:</span>
                            <span class="session-value"><?php echo $_SERVER['REMOTE_ADDR']; ?></span>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <span class="session-label">Browser:</span>
                            <span class="session-value"><?php echo substr($_SERVER['HTTP_USER_AGENT'], 0, 50) . '...'; ?></span>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <span class="session-label">Session Start:</span>
                            <span class="session-value"><?php echo date('M d, H:i', $_SESSION['login_time'] ?? time()); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Page-specific scripts -->
<script>
// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggleBtn = event.currentTarget;
    const icon = toggleBtn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Check password strength and match
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordMatch = document.getElementById('passwordMatch');
    
    if (newPassword) {
        newPassword.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
    }
    
    if (confirmPassword) {
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }
});

function checkPasswordStrength(password) {
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };
    
    // Update requirement indicators
    document.getElementById('req-length').className = requirements.length ? 'valid' : '';
    document.getElementById('req-uppercase').className = requirements.uppercase ? 'valid' : '';
    document.getElementById('req-lowercase').className = requirements.lowercase ? 'valid' : '';
    document.getElementById('req-number').className = requirements.number ? 'valid' : '';
    document.getElementById('req-special').className = requirements.special ? 'valid' : '';
    
    // Update icons
    document.querySelectorAll('.requirements-list li').forEach(li => {
        const icon = li.querySelector('i');
        if (li.classList.contains('valid')) {
            icon.className = 'fas fa-check-circle';
        } else {
            icon.className = 'fas fa-circle';
        }
    });
}

function checkPasswordMatch() {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPass.length === 0) {
        matchDiv.innerHTML = '';
        return;
    }
    
    if (newPass === confirmPass) {
        matchDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Passwords match</span>';
    } else {
        matchDiv.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> Passwords do not match</span>';
    }
}

// Form validation for password change
document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    
    if (newPass !== confirmPass) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
});

// Auto-hide photo upload form after submission
document.getElementById('photoInput')?.addEventListener('change', function() {
    document.getElementById('photoUploadForm').submit();
});
</script>

<style>
/* ===== PROFILE PAGE SPECIFIC STYLES ===== */

/* Profile Card */
.profile-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    height: 100%;
}

.profile-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    padding: 2rem 1.5rem 1.5rem;
    text-align: center;
    position: relative;
    color: white;
}

.profile-avatar-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 1rem;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    background: white;
}

.profile-avatar span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-size: 3rem;
    font-weight: 600;
}

.profile-avatar-upload {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 36px;
    height: 36px;
    background: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    border: 2px solid white;
    transition: var(--transition);
}

.profile-avatar-upload:hover {
    background: var(--primary-light);
    transform: scale(1.1);
}

.profile-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: white;
}

.profile-role {
    margin-bottom: 0.5rem;
}

.role-badge {
    display: inline-block;
    padding: 0.3rem 1rem;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.profile-username {
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Profile Stats */
.profile-stats {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item:last-child {
    margin-bottom: 0;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 1.2rem;
}

.stat-info {
    flex: 1;
}

.stat-label {
    display: block;
    font-size: 0.75rem;
    color: var(--dark-gray);
    margin-bottom: 0.2rem;
}

.stat-value {
    display: block;
    font-weight: 600;
    color: var(--dark);
}

.stat-value.status-active {
    color: var(--success);
}

/* Profile Contact */
.profile-contact {
    padding: 1.5rem;
}

.profile-contact h5 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 1rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px dashed var(--border);
}

.contact-item:last-child {
    border-bottom: none;
}

.contact-item i {
    width: 20px;
    color: var(--primary);
}

.contact-item span {
    color: var(--dark);
    word-break: break-word;
}

/* Profile Tabs Card */
.profile-tabs-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    height: 100%;
}

.profile-tabs {
    background: var(--light);
    padding: 0.5rem 1rem 0;
    border-bottom: 1px solid var(--border);
}

.profile-tabs .nav-link {
    border: none;
    color: var(--dark-gray);
    font-weight: 500;
    padding: 0.75rem 1.25rem;
    margin-right: 0.5rem;
    border-radius: 8px 8px 0 0;
    transition: var(--transition);
}

.profile-tabs .nav-link:hover {
    color: var(--primary);
    background: white;
}

.profile-tabs .nav-link.active {
    color: var(--primary);
    background: white;
    font-weight: 600;
    border-bottom: 2px solid var(--primary);
}

.profile-tab-content {
    padding: 2rem;
}

/* Profile Form */
.profile-form .form-label {
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.profile-form .form-control {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 0.6rem 1rem;
    transition: var(--transition);
}

.profile-form .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(30,58,95,0.1);
    outline: none;
}

.profile-form .form-control:disabled,
.profile-form .form-control[readonly] {
    background: var(--light);
    cursor: not-allowed;
}

/* Password Input Group */
.password-input-group {
    position: relative;
}

.password-input-group .form-control {
    padding-right: 45px;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--dark-gray);
    cursor: pointer;
    padding: 5px;
}

.password-toggle:hover {
    color: var(--primary);
}

/* Password Requirements */
.password-requirements {
    background: var(--light);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border);
}

.password-requirements h6 {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.75rem;
}

.requirements-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.requirements-list li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 0;
    color: var(--dark-gray);
    font-size: 0.85rem;
}

.requirements-list li.valid {
    color: var(--success);
}

.requirements-list li i {
    width: 16px;
    font-size: 0.8rem;
}

.requirements-list li.valid i {
    color: var(--success);
}

/* Activity Log */
.activity-log {
    max-height: 500px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    transition: var(--transition);
}

.activity-item:hover {
    background: var(--light);
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 1.1rem;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    margin-bottom: 0.3rem;
    color: var(--dark);
}

.activity-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.85rem;
    color: var(--dark-gray);
    margin-bottom: 0.2rem;
}

.activity-time i,
.activity-ip i {
    margin-right: 0.3rem;
}

.activity-details {
    font-size: 0.8rem;
    color: var(--dark-gray);
}

/* Session Card */
.session-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.session-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.session-header h5 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
    font-size: 1rem;
}

.session-body {
    padding: 1.5rem;
}

.session-label {
    font-size: 0.8rem;
    color: var(--dark-gray);
    display: block;
    margin-bottom: 0.2rem;
}

.session-value {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--dark);
    word-break: break-word;
}

/* Responsive */
@media (max-width: 768px) {
    .profile-avatar-wrapper {
        width: 100px;
        height: 100px;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
    }
    
    .profile-avatar span {
        font-size: 2.5rem;
    }
    
    .profile-name {
        font-size: 1.3rem;
    }
    
    .profile-tab-content {
        padding: 1.5rem;
    }
    
    .activity-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.3rem;
    }
}

@media (max-width: 576px) {
    .profile-header {
        padding: 1.5rem 1rem;
    }
    
    .profile-avatar-wrapper {
        width: 80px;
        height: 80px;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
    }
    
    .profile-avatar span {
        font-size: 2rem;
    }
    
    .profile-avatar-upload {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
    
    .profile-name {
        font-size: 1.2rem;
    }
    
    .profile-tabs .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
    }
    
    .profile-tab-content {
        padding: 1rem;
    }
    
    .stat-item {
        gap: 0.75rem;
    }
    
    .stat-icon {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
    
    .session-body .row > div {
        margin-bottom: 0.5rem;
    }
}

/* Print Styles */
@media print {
    .profile-avatar-upload,
    .profile-tabs,
    .btn,
    .password-toggle,
    .back-to-top {
        display: none !important;
    }
    
    .profile-card,
    .profile-tabs-card,
    .session-card {
        break-inside: avoid;
        border: 1px solid #000;
        box-shadow: none;
    }
    
    .profile-header {
        background: #f0f0f0 !important;
        color: #000 !important;
    }
    
    .role-badge {
        border: 1px solid #000;
        background: transparent !important;
    }
}
</style>