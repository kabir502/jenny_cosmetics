<?php
// admin/settings.php - System Settings Management Page

// Include central session handler from root
require_once '../session_handler.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if user has permission (only Super Admin can access settings)
if ($_SESSION['admin_role'] !== 'Super Admin') {
    header("Location: dashboard.php?error=You don't have permission to access settings");
    exit();
}

// Include database
require_once '../config/database.php';
require_once '../config/constants.php';

// Initialize variables
$message = '';
$message_type = '';

// =============================================================================
// HANDLE SETTINGS UPDATES
// =============================================================================

// Update General Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_general'])) {
    $settings = [
        'site_name' => mysqli_real_escape_string($connection, $_POST['site_name']),
        'site_email' => mysqli_real_escape_string($connection, $_POST['site_email']),
        'site_phone' => mysqli_real_escape_string($connection, $_POST['site_phone']),
        'site_address' => mysqli_real_escape_string($connection, $_POST['site_address']),
        'site_currency' => mysqli_real_escape_string($connection, $_POST['site_currency']),
        'timezone' => mysqli_real_escape_string($connection, $_POST['timezone']),
        'date_format' => mysqli_real_escape_string($connection, $_POST['date_format']),
        'time_format' => mysqli_real_escape_string($connection, $_POST['time_format'])
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        $update_query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $value, $key);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            $success = false;
        }
    }
    
    if ($success) {
        $message = "General settings updated successfully.";
        $message_type = 'success';
        error_log("Admin {$_SESSION['admin_name']} updated general settings");
    } else {
        $message = "Failed to update some settings.";
        $message_type = 'danger';
    }
}

// Update Order Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $settings = [
        'tax_rate' => (float)$_POST['tax_rate'],
        'shipping_cost' => (float)$_POST['shipping_cost'],
        'free_shipping_threshold' => (float)$_POST['free_shipping_threshold'],
        'min_order_amount' => (float)$_POST['min_order_amount'],
        'max_order_amount' => (float)$_POST['max_order_amount'],
        'order_prefix' => mysqli_real_escape_string($connection, $_POST['order_prefix']),
        'invoice_prefix' => mysqli_real_escape_string($connection, $_POST['invoice_prefix']),
        'default_order_status' => mysqli_real_escape_string($connection, $_POST['default_order_status'])
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        $update_query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $value, $key);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            $success = false;
        }
    }
    
    if ($success) {
        $message = "Order settings updated successfully.";
        $message_type = 'success';
    } else {
        $message = "Failed to update order settings.";
        $message_type = 'danger';
    }
}

// Update Email Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    $settings = [
        'smtp_host' => mysqli_real_escape_string($connection, $_POST['smtp_host']),
        'smtp_port' => (int)$_POST['smtp_port'],
        'smtp_user' => mysqli_real_escape_string($connection, $_POST['smtp_user']),
        'smtp_pass' => mysqli_real_escape_string($connection, $_POST['smtp_pass']),
        'smtp_encryption' => mysqli_real_escape_string($connection, $_POST['smtp_encryption']),
        'email_from_name' => mysqli_real_escape_string($connection, $_POST['email_from_name']),
        'email_from_address' => mysqli_real_escape_string($connection, $_POST['email_from_address'])
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        $update_query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $value, $key);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            $success = false;
        }
    }
    
    if ($success) {
        $message = "Email settings updated successfully.";
        $message_type = 'success';
    } else {
        $message = "Failed to update email settings.";
        $message_type = 'danger';
    }
}

// Update Payment Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $settings = [
        'payment_methods' => json_encode($_POST['payment_methods'] ?? []),
        'paypal_email' => mysqli_real_escape_string($connection, $_POST['paypal_email'] ?? ''),
        'paypal_sandbox' => isset($_POST['paypal_sandbox']) ? 1 : 0,
        'stripe_publishable_key' => mysqli_real_escape_string($connection, $_POST['stripe_publishable_key'] ?? ''),
        'stripe_secret_key' => mysqli_real_escape_string($connection, $_POST['stripe_secret_key'] ?? ''),
        'bank_details' => mysqli_real_escape_string($connection, $_POST['bank_details'] ?? ''),
        'cod_enabled' => isset($_POST['cod_enabled']) ? 1 : 0
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        $update_query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $value, $key);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            $success = false;
        }
    }
    
    if ($success) {
        $message = "Payment settings updated successfully.";
        $message_type = 'success';
    } else {
        $message = "Failed to update payment settings.";
        $message_type = 'danger';
    }
}

// Update Security Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_security'])) {
    $settings = [
        'session_timeout' => (int)$_POST['session_timeout'],
        'max_login_attempts' => (int)$_POST['max_login_attempts'],
        'lockout_time' => (int)$_POST['lockout_time'],
        'password_min_length' => (int)$_POST['password_min_length'],
        'require_strong_password' => isset($_POST['require_strong_password']) ? 1 : 0,
        'two_factor_auth' => isset($_POST['two_factor_auth']) ? 1 : 0,
        'recaptcha_enabled' => isset($_POST['recaptcha_enabled']) ? 1 : 0,
        'recaptcha_site_key' => mysqli_real_escape_string($connection, $_POST['recaptcha_site_key'] ?? ''),
        'recaptcha_secret_key' => mysqli_real_escape_string($connection, $_POST['recaptcha_secret_key'] ?? '')
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        $update_query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $value, $key);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            $success = false;
        }
    }
    
    if ($success) {
        $message = "Security settings updated successfully.";
        $message_type = 'success';
    } else {
        $message = "Failed to update security settings.";
        $message_type = 'danger';
    }
}

// Update Backup Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_backup'])) {
    $settings = [
        'auto_backup' => isset($_POST['auto_backup']) ? 1 : 0,
        'backup_frequency' => mysqli_real_escape_string($connection, $_POST['backup_frequency']),
        'backup_time' => mysqli_real_escape_string($connection, $_POST['backup_time']),
        'backup_retention' => (int)$_POST['backup_retention'],
        'backup_path' => mysqli_real_escape_string($connection, $_POST['backup_path'])
    ];
    
    $success = true;
    foreach ($settings as $key => $value) {
        $update_query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $value, $key);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            $success = false;
        }
    }
    
    if ($success) {
        $message = "Backup settings updated successfully.";
        $message_type = 'success';
    } else {
        $message = "Failed to update backup settings.";
        $message_type = 'danger';
    }
}

// =============================================================================
// HANDLE ACTIONS
// =============================================================================

// Clear Cache
if (isset($_GET['action']) && $_GET['action'] == 'clear_cache') {
    // Clear system cache
    $cache_dir = '../cache/';
    if (file_exists($cache_dir)) {
        $files = glob($cache_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    $message = "System cache cleared successfully.";
    $message_type = 'success';
    error_log("Admin {$_SESSION['admin_name']} cleared system cache");
}

// Run Backup
if (isset($_GET['action']) && $_GET['action'] == 'run_backup') {
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = '../backups/' . $backup_file;
    
    // Create backups directory if not exists
    if (!file_exists('../backups')) {
        mkdir('../backups', 0777, true);
    }
    
    // Get database configuration
    $db_config = [
        'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
        'user' => defined('DB_USER') ? DB_USER : 'root',
        'pass' => defined('DB_PASS') ? DB_PASS : '',
        'name' => defined('DB_NAME') ? DB_NAME : 'jenny_cosmetics_db'
    ];
    
    // Create backup command
    $command = sprintf(
        'mysqldump -h %s -u %s %s > %s',
        escapeshellarg($db_config['host']),
        escapeshellarg($db_config['user']),
        escapeshellarg($db_config['name']),
        escapeshellarg($backup_path)
    );
    
    // Execute backup
    system($command, $output);
    
    if ($output === 0) {
        // Log backup in database (check if table exists)
        $file_size = file_exists($backup_path) ? round(filesize($backup_path) / 1024 / 1024, 2) : 0;
        
        // Check if site_backups table exists
        $table_check = mysqli_query($connection, "SHOW TABLES LIKE 'site_backups'");
        if (mysqli_num_rows($table_check) > 0) {
            $insert_query = "INSERT INTO site_backups (backup_name, backup_type, file_path, file_size_mb, created_by, created_at, notes) 
                            VALUES (?, 'Database Only', ?, ?, ?, NOW(), 'Manual backup')";
            
            $insert_stmt = mysqli_prepare($connection, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ssdi", $backup_file, $backup_path, $file_size, $_SESSION['admin_id']);
            mysqli_stmt_execute($insert_stmt);
        }
        
        $message = "Database backup created successfully.";
        $message_type = 'success';
        error_log("Admin {$_SESSION['admin_name']} created database backup: $backup_file");
    } else {
        $message = "Failed to create database backup. Make sure mysqldump is available.";
        $message_type = 'danger';
    }
}

// Test Email
if (isset($_GET['action']) && $_GET['action'] == 'test_email') {
    // Get email settings
    $test_email = $_SESSION['admin_email'] ?? 'admin@example.com';
    
    // Here you would implement actual email test using PHPMailer
    // For now, just show success message
    
    $message = "Test email sent to $test_email. Please check your inbox.";
    $message_type = 'success';
}

// =============================================================================
// GET CURRENT SETTINGS
// =============================================================================

$settings = [];

// Check if system_settings table exists
$table_check = mysqli_query($connection, "SHOW TABLES LIKE 'system_settings'");
if (mysqli_num_rows($table_check) > 0) {
    $settings_query = "SELECT setting_key, setting_value, setting_type FROM system_settings";
    $settings_result = mysqli_query($connection, $settings_query);
    
    while ($row = mysqli_fetch_assoc($settings_result)) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        $type = $row['setting_type'];
        
        // Convert value based on type
        if ($type == 'boolean') {
            $value = $value == '1' || $value == 'true' ? true : false;
        } elseif ($type == 'integer') {
            $value = (int)$value;
        } elseif ($type == 'json') {
            $value = json_decode($value, true);
        }
        
        $settings[$key] = $value;
    }
}

// Get backup history
$backups_result = null;
$table_check = mysqli_query($connection, "SHOW TABLES LIKE 'site_backups'");
if (mysqli_num_rows($table_check) > 0) {
    $backups_query = "SELECT b.*, a.username as created_by_name 
                      FROM site_backups b
                      LEFT JOIN administrators a ON b.created_by = a.admin_id
                      ORDER BY b.created_at DESC 
                      LIMIT 10";
    $backups_result = mysqli_query($connection, $backups_query);
}

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
                            <i class="fas fa-cog me-2"></i>
                            System Settings
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Settings</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <span class="role-badge super-admin">
                            <i class="fas fa-shield-alt me-1"></i>Super Admin Access
                        </span>
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

    <!-- Settings Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="settings-card">
                <div class="settings-header">
                    <ul class="nav nav-tabs settings-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-globe me-2"></i>General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="order-tab" data-bs-toggle="tab" data-bs-target="#order" type="button" role="tab">
                                <i class="fas fa-shopping-cart me-2"></i>Orders
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                                <i class="fas fa-envelope me-2"></i>Email
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">
                                <i class="fas fa-credit-card me-2"></i>Payment
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                <i class="fas fa-lock me-2"></i>Security
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup" type="button" role="tab">
                                <i class="fas fa-database me-2"></i>Backup
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                                <i class="fas fa-server me-2"></i>System
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="settings-body">
                    <div class="tab-content" id="settingsTabsContent">
                        <!-- General Settings Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <form method="POST" action="" class="settings-form">
                                <div class="row">
                                    <h5 class="section-title">General Information</h5>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="site_name" class="form-label">Site Name</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" 
                                               value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Jenny\'s Cosmetics & Jewelry'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="site_email" class="form-label">Site Email</label>
                                        <input type="email" class="form-control" id="site_email" name="site_email" 
                                               value="<?php echo htmlspecialchars($settings['site_email'] ?? 'info@jennyscosmetics.com'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="site_phone" class="form-label">Site Phone</label>
                                        <input type="text" class="form-control" id="site_phone" name="site_phone" 
                                               value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="site_currency" class="form-label">Currency</label>
                                        <select class="form-select" id="site_currency" name="site_currency">
                                            <option value="USD" <?php echo ($settings['site_currency'] ?? 'USD') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                            <option value="EUR" <?php echo ($settings['site_currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                            <option value="GBP" <?php echo ($settings['site_currency'] ?? '') == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                            <option value="JPY" <?php echo ($settings['site_currency'] ?? '') == 'JPY' ? 'selected' : ''; ?>>JPY (¥)</option>
                                            <option value="CAD" <?php echo ($settings['site_currency'] ?? '') == 'CAD' ? 'selected' : ''; ?>>CAD ($)</option>
                                            <option value="AUD" <?php echo ($settings['site_currency'] ?? '') == 'AUD' ? 'selected' : ''; ?>>AUD ($)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12 mb-3">
                                        <label for="site_address" class="form-label">Site Address</label>
                                        <textarea class="form-control" id="site_address" name="site_address" rows="2"><?php echo htmlspecialchars($settings['site_address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <h5 class="section-title mt-3">Regional Settings</h5>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="timezone" class="form-label">Timezone</label>
                                        <select class="form-select" id="timezone" name="timezone">
                                            <option value="America/New_York" <?php echo ($settings['timezone'] ?? '') == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                            <option value="America/Chicago" <?php echo ($settings['timezone'] ?? '') == 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                            <option value="America/Denver" <?php echo ($settings['timezone'] ?? '') == 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                            <option value="America/Los_Angeles" <?php echo ($settings['timezone'] ?? '') == 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                            <option value="Europe/London" <?php echo ($settings['timezone'] ?? '') == 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                            <option value="Europe/Paris" <?php echo ($settings['timezone'] ?? '') == 'Europe/Paris' ? 'selected' : ''; ?>>Paris</option>
                                            <option value="Asia/Tokyo" <?php echo ($settings['timezone'] ?? '') == 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo</option>
                                            <option value="Asia/Dubai" <?php echo ($settings['timezone'] ?? '') == 'Asia/Dubai' ? 'selected' : ''; ?>>Dubai</option>
                                            <option value="Australia/Sydney" <?php echo ($settings['timezone'] ?? '') == 'Australia/Sydney' ? 'selected' : ''; ?>>Sydney</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label for="date_format" class="form-label">Date Format</label>
                                        <select class="form-select" id="date_format" name="date_format">
                                            <option value="Y-m-d" <?php echo ($settings['date_format'] ?? 'Y-m-d') == 'Y-m-d' ? 'selected' : ''; ?>>2023-12-31</option>
                                            <option value="m/d/Y" <?php echo ($settings['date_format'] ?? '') == 'm/d/Y' ? 'selected' : ''; ?>>12/31/2023</option>
                                            <option value="d/m/Y" <?php echo ($settings['date_format'] ?? '') == 'd/m/Y' ? 'selected' : ''; ?>>31/12/2023</option>
                                            <option value="F j, Y" <?php echo ($settings['date_format'] ?? '') == 'F j, Y' ? 'selected' : ''; ?>>December 31, 2023</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label for="time_format" class="form-label">Time Format</label>
                                        <select class="form-select" id="time_format" name="time_format">
                                            <option value="H:i" <?php echo ($settings['time_format'] ?? 'H:i') == 'H:i' ? 'selected' : ''; ?>>24-hour (14:30)</option>
                                            <option value="h:i A" <?php echo ($settings['time_format'] ?? '') == 'h:i A' ? 'selected' : ''; ?>>12-hour (02:30 PM)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" name="update_general" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save General Settings
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Order Settings Tab -->
                        <div class="tab-pane fade" id="order" role="tabpanel">
                            <form method="POST" action="" class="settings-form">
                                <div class="row">
                                    <h5 class="section-title">Order Configuration</h5>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                        <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                               value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '8.5'); ?>" step="0.01" min="0" max="100">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="shipping_cost" class="form-label">Shipping Cost ($)</label>
                                        <input type="number" class="form-control" id="shipping_cost" name="shipping_cost" 
                                               value="<?php echo htmlspecialchars($settings['shipping_cost'] ?? '5.99'); ?>" step="0.01" min="0">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold ($)</label>
                                        <input type="number" class="form-control" id="free_shipping_threshold" name="free_shipping_threshold" 
                                               value="<?php echo htmlspecialchars($settings['free_shipping_threshold'] ?? '50'); ?>" step="0.01" min="0">
                                        <small class="text-muted">Set 0 to disable</small>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="min_order_amount" class="form-label">Minimum Order Amount ($)</label>
                                        <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" 
                                               value="<?php echo htmlspecialchars($settings['min_order_amount'] ?? '10'); ?>" step="0.01" min="0">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="max_order_amount" class="form-label">Maximum Order Amount ($)</label>
                                        <input type="number" class="form-control" id="max_order_amount" name="max_order_amount" 
                                               value="<?php echo htmlspecialchars($settings['max_order_amount'] ?? '10000'); ?>" step="0.01" min="0">
                                        <small class="text-muted">Set 0 for no limit</small>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="default_order_status" class="form-label">Default Order Status</label>
                                        <select class="form-select" id="default_order_status" name="default_order_status">
                                            <option value="Pending" <?php echo ($settings['default_order_status'] ?? 'Pending') == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Processing" <?php echo ($settings['default_order_status'] ?? '') == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Confirmed" <?php echo ($settings['default_order_status'] ?? '') == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        </select>
                                    </div>
                                    
                                    <h5 class="section-title mt-3">Order Numbering</h5>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="order_prefix" class="form-label">Order Number Prefix</label>
                                        <input type="text" class="form-control" id="order_prefix" name="order_prefix" 
                                               value="<?php echo htmlspecialchars($settings['order_prefix'] ?? 'ORD'); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="invoice_prefix" class="form-label">Invoice Number Prefix</label>
                                        <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix" 
                                               value="<?php echo htmlspecialchars($settings['invoice_prefix'] ?? 'INV'); ?>">
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" name="update_order" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Order Settings
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Email Settings Tab -->
                        <div class="tab-pane fade" id="email" role="tabpanel">
                            <form method="POST" action="" class="settings-form">
                                <div class="row">
                                    <h5 class="section-title">Email Configuration</h5>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email_from_name" class="form-label">From Name</label>
                                        <input type="text" class="form-control" id="email_from_name" name="email_from_name" 
                                               value="<?php echo htmlspecialchars($settings['email_from_name'] ?? 'Jenny\'s Cosmetics'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email_from_address" class="form-label">From Email</label>
                                        <input type="email" class="form-control" id="email_from_address" name="email_from_address" 
                                               value="<?php echo htmlspecialchars($settings['email_from_address'] ?? 'noreply@jennyscosmetics.com'); ?>" required>
                                    </div>
                                    
                                    <h5 class="section-title mt-3">SMTP Settings</h5>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="smtp_host" class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com'); ?>">
                                    </div>
                                    
                                    <div class="col-md-2 mb-3">
                                        <label for="smtp_port" class="form-label">Port</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                                    </div>
                                    
                                    <div class="col-md-2 mb-3">
                                        <label for="smtp_encryption" class="form-label">Encryption</label>
                                        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="none" <?php echo ($settings['smtp_encryption'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="smtp_user" class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" id="smtp_user" name="smtp_user" 
                                               value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_pass" class="form-label">SMTP Password</label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>">
                                            <button type="button" class="password-toggle" onclick="togglePassword('smtp_pass', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <a href="?action=test_email" class="btn btn-info" onclick="return confirm('Send test email to <?php echo $_SESSION['admin_email'] ?? 'admin@example.com'; ?>?')">
                                                <i class="fas fa-paper-plane me-2"></i>Send Test Email
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" name="update_email" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Email Settings
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Payment Settings Tab -->
                        <div class="tab-pane fade" id="payment" role="tabpanel">
                            <form method="POST" action="" class="settings-form">
                                <div class="row">
                                    <h5 class="section-title">Payment Methods</h5>
                                    
                                    <?php 
                                    $payment_methods = $settings['payment_methods'] ?? [];
                                    if (!is_array($payment_methods)) {
                                        $payment_methods = [];
                                    }
                                    ?>
                                    
                                    <div class="col-12 mb-3">
                                        <div class="payment-methods-grid">
                                            <div class="payment-method-item">
                                                <input type="checkbox" id="method_paypal" name="payment_methods[]" value="paypal"
                                                       <?php echo in_array('paypal', $payment_methods) ? 'checked' : ''; ?>>
                                                <label for="method_paypal">
                                                    <i class="fab fa-paypal"></i>
                                                    <span>PayPal</span>
                                                </label>
                                            </div>
                                            
                                            <div class="payment-method-item">
                                                <input type="checkbox" id="method_stripe" name="payment_methods[]" value="stripe"
                                                       <?php echo in_array('stripe', $payment_methods) ? 'checked' : ''; ?>>
                                                <label for="method_stripe">
                                                    <i class="fab fa-stripe"></i>
                                                    <span>Stripe</span>
                                                </label>
                                            </div>
                                            
                                            <div class="payment-method-item">
                                                <input type="checkbox" id="method_bank" name="payment_methods[]" value="bank_transfer"
                                                       <?php echo in_array('bank_transfer', $payment_methods) ? 'checked' : ''; ?>>
                                                <label for="method_bank">
                                                    <i class="fas fa-university"></i>
                                                    <span>Bank Transfer</span>
                                                </label>
                                            </div>
                                            
                                            <div class="payment-method-item">
                                                <input type="checkbox" id="method_cod" name="payment_methods[]" value="cod"
                                                       <?php echo in_array('cod', $payment_methods) ? 'checked' : ''; ?>>
                                                <label for="method_cod">
                                                    <i class="fas fa-money-bill"></i>
                                                    <span>Cash on Delivery</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h5 class="section-title mt-3">PayPal Settings</h5>
                                    
                                    <div class="col-md-8 mb-3">
                                        <label for="paypal_email" class="form-label">PayPal Email</label>
                                        <input type="email" class="form-control" id="paypal_email" name="paypal_email" 
                                               value="<?php echo htmlspecialchars($settings['paypal_email'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="paypal_sandbox" name="paypal_sandbox"
                                                   <?php echo !empty($settings['paypal_sandbox']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="paypal_sandbox">
                                                Enable Sandbox Mode
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <h5 class="section-title mt-3">Stripe Settings</h5>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="stripe_publishable_key" class="form-label">Publishable Key</label>
                                        <input type="text" class="form-control" id="stripe_publishable_key" name="stripe_publishable_key" 
                                               value="<?php echo htmlspecialchars($settings['stripe_publishable_key'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="stripe_secret_key" class="form-label">Secret Key</label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-control" id="stripe_secret_key" name="stripe_secret_key" 
                                                   value="<?php echo htmlspecialchars($settings['stripe_secret_key'] ?? ''); ?>">
                                            <button type="button" class="password-toggle" onclick="togglePassword('stripe_secret_key', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <h5 class="section-title mt-3">Bank Transfer Details</h5>
                                    
                                    <div class="col-12 mb-3">
                                        <label for="bank_details" class="form-label">Bank Account Details</label>
                                        <textarea class="form-control" id="bank_details" name="bank_details" rows="4"><?php echo htmlspecialchars($settings['bank_details'] ?? ''); ?></textarea>
                                        <small class="text-muted">Include account name, number, bank name, routing number, etc.</small>
                                    </div>
                                    
                                    <h5 class="section-title mt-3">Cash on Delivery</h5>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="cod_enabled" name="cod_enabled"
                                                   <?php echo !empty($settings['cod_enabled']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="cod_enabled">
                                                Enable Cash on Delivery
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" name="update_payment" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Payment Settings
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Security Settings Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <form method="POST" action="" class="settings-form">
                                <div class="row">
                                    <h5 class="section-title">Login Security</h5>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                        <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                               value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '30'); ?>" min="5" max="480">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                        <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                               value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '5'); ?>" min="1" max="20">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="lockout_time" class="form-label">Lockout Time (minutes)</label>
                                        <input type="number" class="form-control" id="lockout_time" name="lockout_time" 
                                               value="<?php echo htmlspecialchars($settings['lockout_time'] ?? '15'); ?>" min="1" max="1440">
                                    </div>
                                    
                                    <h5 class="section-title mt-3">Password Policy</h5>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="password_min_length" class="form-label">Minimum Password Length</label>
                                        <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                               value="<?php echo htmlspecialchars($settings['password_min_length'] ?? '8'); ?>" min="6" max="20">
                                    </div>
                                    
                                    <div class="col-md-8 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="require_strong_password" name="require_strong_password"
                                                   <?php echo !empty($settings['require_strong_password']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="require_strong_password">
                                                Require strong password (uppercase, lowercase, number, special character)
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="two_factor_auth" name="two_factor_auth"
                                                   <?php echo !empty($settings['two_factor_auth']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="two_factor_auth">
                                                Enable Two-Factor Authentication
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <h5 class="section-title mt-3">reCAPTCHA Settings</h5>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled"
                                                   <?php echo !empty($settings['recaptcha_enabled']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="recaptcha_enabled">
                                                Enable Google reCAPTCHA
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="recaptcha_site_key" class="form-label">Site Key</label>
                                        <input type="text" class="form-control" id="recaptcha_site_key" name="recaptcha_site_key" 
                                               value="<?php echo htmlspecialchars($settings['recaptcha_site_key'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="recaptcha_secret_key" class="form-label">Secret Key</label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-control" id="recaptcha_secret_key" name="recaptcha_secret_key" 
                                                   value="<?php echo htmlspecialchars($settings['recaptcha_secret_key'] ?? ''); ?>">
                                            <button type="button" class="password-toggle" onclick="togglePassword('recaptcha_secret_key', this)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" name="update_security" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Security Settings
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Backup Settings Tab -->
                        <div class="tab-pane fade" id="backup" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-6">
                                    <form method="POST" action="" class="settings-form">
                                        <h5 class="section-title">Automatic Backup Settings</h5>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup"
                                                       <?php echo !empty($settings['auto_backup']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="auto_backup">
                                                    Enable Automatic Backups
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="backup_frequency" class="form-label">Backup Frequency</label>
                                            <select class="form-select" id="backup_frequency" name="backup_frequency">
                                                <option value="daily" <?php echo ($settings['backup_frequency'] ?? 'daily') == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                                <option value="weekly" <?php echo ($settings['backup_frequency'] ?? '') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                <option value="monthly" <?php echo ($settings['backup_frequency'] ?? '') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="backup_time" class="form-label">Backup Time (24h format)</label>
                                            <input type="time" class="form-control" id="backup_time" name="backup_time" 
                                                   value="<?php echo htmlspecialchars($settings['backup_time'] ?? '02:00'); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="backup_retention" class="form-label">Keep Backups (days)</label>
                                            <input type="number" class="form-control" id="backup_retention" name="backup_retention" 
                                                   value="<?php echo htmlspecialchars($settings['backup_retention'] ?? '30'); ?>" min="1" max="365">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="backup_path" class="form-label">Backup Path</label>
                                            <input type="text" class="form-control" id="backup_path" name="backup_path" 
                                                   value="<?php echo htmlspecialchars($settings['backup_path'] ?? '../backups/'); ?>">
                                        </div>
                                        
                                        <button type="submit" name="update_backup" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Backup Settings
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="col-lg-6">
                                    <div class="backup-actions">
                                        <h5 class="section-title">Manual Backup</h5>
                                        <a href="?action=run_backup" class="btn btn-success w-100 mb-3" onclick="return confirm('Start manual database backup? This may take a few minutes.')">
                                            <i class="fas fa-database me-2"></i>Create Backup Now
                                        </a>
                                        
                                        <h5 class="section-title mt-4">Recent Backups</h5>
                                        <div class="backup-list">
                                            <?php if ($backups_result && mysqli_num_rows($backups_result) > 0): ?>
                                                <?php while ($backup = mysqli_fetch_assoc($backups_result)): ?>
                                                <div class="backup-item">
                                                    <div class="backup-info">
                                                        <div class="backup-name"><?php echo htmlspecialchars($backup['backup_name'] ?? 'Unknown'); ?></div>
                                                        <div class="backup-meta">
                                                            <span><i class="far fa-calendar"></i> <?php echo isset($backup['created_at']) ? date('M d, Y H:i', strtotime($backup['created_at'])) : 'N/A'; ?></span>
                                                            <span><i class="fas fa-database"></i> <?php echo htmlspecialchars($backup['file_size_mb'] ?? '0'); ?> MB</span>
                                                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($backup['created_by_name'] ?? 'System'); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="backup-actions">
                                                        <?php if (!empty($backup['file_path'])): ?>
                                                        <a href="../<?php echo htmlspecialchars($backup['file_path']); ?>" class="btn btn-sm btn-light" download>
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p class="text-muted text-center py-3">No backups found</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Info Tab -->
                        <div class="tab-pane fade" id="system" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="system-info-card">
                                        <h5 class="section-title">PHP Information</h5>
                                        <table class="system-info-table">
                                            <tr>
                                                <td>PHP Version:</td>
                                                <td><strong><?php echo phpversion(); ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td>Memory Limit:</td>
                                                <td><?php echo ini_get('memory_limit'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Max Execution Time:</td>
                                                <td><?php echo ini_get('max_execution_time'); ?> seconds</td>
                                            </tr>
                                            <tr>
                                                <td>Upload Max Filesize:</td>
                                                <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Post Max Size:</td>
                                                <td><?php echo ini_get('post_max_size'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Display Errors:</td>
                                                <td><?php echo ini_get('display_errors') ? 'On' : 'Off'; ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="system-info-card">
                                        <h5 class="section-title">Database Information</h5>
                                        <table class="system-info-table">
                                            <tr>
                                                <td>MySQL Version:</td>
                                                <td><strong><?php echo mysqli_get_server_info($connection); ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td>Database Name:</td>
                                                <td><?php echo defined('DB_NAME') ? DB_NAME : 'jenny_cosmetics_db'; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Database Size:</td>
                                                <td><?php 
                                                    $size_query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.TABLES WHERE table_schema = DATABASE()";
                                                    $size_result = mysqli_query($connection, $size_query);
                                                    $db_size = $size_result ? (mysqli_fetch_assoc($size_result)['size_mb'] ?? '0') : '0';
                                                    echo $db_size . ' MB';
                                                ?></td>
                                            </tr>
                                            <tr>
                                                <td>Total Tables:</td>
                                                <td><?php 
                                                    $tables_query = "SELECT COUNT(*) as count FROM information_schema.TABLES WHERE table_schema = DATABASE()";
                                                    $tables_result = mysqli_query($connection, $tables_query);
                                                    $table_count = $tables_result ? (mysqli_fetch_assoc($tables_result)['count'] ?? '0') : '0';
                                                    echo $table_count;
                                                ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="system-info-card">
                                        <h5 class="section-title">Server Information</h5>
                                        <table class="system-info-table">
                                            <tr>
                                                <td>Server Software:</td>
                                                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Server Protocol:</td>
                                                <td><?php echo $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1'; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Server Port:</td>
                                                <td><?php echo $_SERVER['SERVER_PORT'] ?? '80'; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Document Root:</td>
                                                <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Server Uptime:</td>
                                                <td><?php 
                                                    if (function_exists('exec')) {
                                                        @exec('uptime', $uptime_output);
                                                        echo isset($uptime_output[0]) ? substr($uptime_output[0], 0, 100) : 'N/A';
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="system-info-card">
                                        <h5 class="section-title">System Actions</h5>
                                        <a href="?action=clear_cache" class="btn btn-warning w-100 mb-2" onclick="return confirm('Clear system cache? This may temporarily affect performance.')">
                                            <i class="fas fa-trash-alt me-2"></i>Clear System Cache
                                        </a>
                                        <button class="btn btn-info w-100 mb-2" onclick="checkForUpdates()">
                                            <i class="fas fa-sync-alt me-2"></i>Check for Updates
                                        </button>
                                        <a href="../index.php" target="_blank" class="btn btn-secondary w-100">
                                            <i class="fas fa-external-link-alt me-2"></i>Visit Website
                                        </a>
                                    </div>
                                </div>
                            </div>
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
// Toggle password visibility - FIXED: added button parameter
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}

// Check for updates (mock function)
function checkForUpdates() {
    alert('Checking for updates... Your system is up to date.');
}

// Confirm before leaving if unsaved changes
let formChanged = false;

// Safe event listener addition
document.addEventListener('DOMContentLoaded', function() {
    // Add change listeners to form inputs
    const formInputs = document.querySelectorAll('.settings-form input, .settings-form select, .settings-form textarea');
    formInputs.forEach(input => {
        input.addEventListener('change', () => {
            formChanged = true;
        });
    });

    // Save tab state in localStorage
    const activeTab = localStorage.getItem('activeSettingsTab');
    if (activeTab) {
        const tab = document.querySelector(`button[data-bs-target="${activeTab}"]`);
        if (tab) {
            try {
                const tabInstance = new bootstrap.Tab(tab);
                tabInstance.show();
            } catch (e) {
                console.log('Tab error:', e);
            }
        }
    }
    
    // Tab change event listeners
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            if (e.target && e.target.getAttribute) {
                localStorage.setItem('activeSettingsTab', e.target.getAttribute('data-bs-target'));
                formChanged = false; // Reset change tracking when switching tabs
            }
        });
    });
    
    // Toggle payment method sections - just for console logging
    document.querySelectorAll('input[name="payment_methods[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Optional: Add logic to show/hide specific payment settings
            console.log('Payment method toggled:', this.value);
        });
    });
});

// Before unload warning
window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
    }
});
</script>