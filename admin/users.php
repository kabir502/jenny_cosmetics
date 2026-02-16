<?php
// admin/users.php - Users Management Page

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

// Handle Add/Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add User
    if (isset($_POST['add_user'])) {
        $first_name = mysqli_real_escape_string($connection, trim($_POST['first_name']));
        $last_name = mysqli_real_escape_string($connection, trim($_POST['last_name']));
        $email = mysqli_real_escape_string($connection, trim($_POST['email']));
        $password = $_POST['password'];
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $phone_work = mysqli_real_escape_string($connection, trim($_POST['phone_work'] ?? ''));
        $phone_cell = mysqli_real_escape_string($connection, trim($_POST['phone_cell']));
        $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $address_street = mysqli_real_escape_string($connection, trim($_POST['address_street']));
        $address_city = mysqli_real_escape_string($connection, trim($_POST['address_city']));
        $address_state = mysqli_real_escape_string($connection, trim($_POST['address_state'] ?? ''));
        $address_zip = mysqli_real_escape_string($connection, trim($_POST['address_zip'] ?? ''));
        $address_country = mysqli_real_escape_string($connection, trim($_POST['address_country'] ?? 'USA'));
        $customer_category = mysqli_real_escape_string($connection, $_POST['customer_category'] ?? 'Regular');
        $remarks = mysqli_real_escape_string($connection, trim($_POST['remarks'] ?? ''));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if email already exists
        $check_email = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($connection, $check_email);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $message = "Email address already exists.";
            $message_type = 'danger';
        } else {
            $insert_query = "INSERT INTO users (
                first_name, last_name, email, password_hash, phone_work, phone_cell, 
                date_of_birth, address_street, address_city, address_state, address_zip, 
                address_country, customer_category, remarks, registration_date, is_active,
                total_orders, total_spent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 0, 0.00)";
            
            $stmt = mysqli_prepare($connection, $insert_query);
            mysqli_stmt_bind_param($stmt, "sssssssssssssii", 
                $first_name, $last_name, $email, $password_hash, 
                $phone_work, $phone_cell, $date_of_birth, $address_street, 
                $address_city, $address_state, $address_zip, $address_country,
                $customer_category, $remarks, $is_active
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($connection);
                $message = "User '$first_name $last_name' added successfully.";
                $message_type = 'success';
                
                // Log the action
                error_log("Admin {$_SESSION['admin_name']} added user ID: $user_id");
            } else {
                $message = "Failed to add user. " . mysqli_error($connection);
                $message_type = 'danger';
            }
        }
    }
    
    // Edit User
    if (isset($_POST['edit_user'])) {
        $user_id = (int)$_POST['user_id'];
        $first_name = mysqli_real_escape_string($connection, trim($_POST['first_name']));
        $last_name = mysqli_real_escape_string($connection, trim($_POST['last_name']));
        $email = mysqli_real_escape_string($connection, trim($_POST['email']));
        $phone_work = mysqli_real_escape_string($connection, trim($_POST['phone_work'] ?? ''));
        $phone_cell = mysqli_real_escape_string($connection, trim($_POST['phone_cell']));
        $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
        $address_street = mysqli_real_escape_string($connection, trim($_POST['address_street']));
        $address_city = mysqli_real_escape_string($connection, trim($_POST['address_city']));
        $address_state = mysqli_real_escape_string($connection, trim($_POST['address_state'] ?? ''));
        $address_zip = mysqli_real_escape_string($connection, trim($_POST['address_zip'] ?? ''));
        $address_country = mysqli_real_escape_string($connection, trim($_POST['address_country'] ?? 'USA'));
        $customer_category = mysqli_real_escape_string($connection, $_POST['customer_category'] ?? 'Regular');
        $remarks = mysqli_real_escape_string($connection, trim($_POST['remarks'] ?? ''));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if email exists for another user
        $check_email = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $check_stmt = mysqli_prepare($connection, $check_email);
        mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $message = "Email address already exists for another user.";
            $message_type = 'danger';
        } else {
            // Check if password update is requested
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $update_query = "UPDATE users SET 
                    first_name = ?, last_name = ?, email = ?, password_hash = ?,
                    phone_work = ?, phone_cell = ?, date_of_birth = ?,
                    address_street = ?, address_city = ?, address_state = ?, 
                    address_zip = ?, address_country = ?, customer_category = ?, 
                    remarks = ?, is_active = ?
                    WHERE user_id = ?";
                
                $stmt = mysqli_prepare($connection, $update_query);
                mysqli_stmt_bind_param($stmt, "sssssssssssssiii", 
                    $first_name, $last_name, $email, $password_hash,
                    $phone_work, $phone_cell, $date_of_birth,
                    $address_street, $address_city, $address_state, 
                    $address_zip, $address_country, $customer_category, 
                    $remarks, $is_active, $user_id
                );
            } else {
                $update_query = "UPDATE users SET 
                    first_name = ?, last_name = ?, email = ?,
                    phone_work = ?, phone_cell = ?, date_of_birth = ?,
                    address_street = ?, address_city = ?, address_state = ?, 
                    address_zip = ?, address_country = ?, customer_category = ?, 
                    remarks = ?, is_active = ?
                    WHERE user_id = ?";
                
                $stmt = mysqli_prepare($connection, $update_query);
                mysqli_stmt_bind_param($stmt, "sssssssssssssii", 
                    $first_name, $last_name, $email,
                    $phone_work, $phone_cell, $date_of_birth,
                    $address_street, $address_city, $address_state, 
                    $address_zip, $address_country, $customer_category, 
                    $remarks, $is_active, $user_id
                );
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "User '$first_name $last_name' updated successfully.";
                $message_type = 'success';
                
                // Log the action
                error_log("Admin {$_SESSION['admin_name']} updated user ID: $user_id");
            } else {
                $message = "Failed to update user. " . mysqli_error($connection);
                $message_type = 'danger';
            }
        }
    }
}

// Handle Delete User
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Check if user has orders
    $check_orders = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
    $check_stmt = mysqli_prepare($connection, $check_orders);
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $order_count = mysqli_fetch_assoc($check_result)['count'];
    
    if ($order_count > 0) {
        $message = "Cannot delete user. They have $order_count orders associated with their account.";
        $message_type = 'danger';
    } else {
        // Check if user has reviews
        $check_reviews = "SELECT COUNT(*) as count FROM product_reviews WHERE user_id = ?";
        $check_stmt = mysqli_prepare($connection, $check_reviews);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $review_count = mysqli_fetch_assoc($check_result)['count'];
        
        if ($review_count > 0) {
            $message = "Cannot delete user. They have $review_count product reviews.";
            $message_type = 'danger';
        } else {
            // Delete user
            $delete_query = "DELETE FROM users WHERE user_id = ?";
            $delete_stmt = mysqli_prepare($connection, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $message = "User deleted successfully.";
                $message_type = 'success';
                
                // Log the action
                error_log("Admin {$_SESSION['admin_name']} deleted user ID: $user_id");
            } else {
                $message = "Failed to delete user.";
                $message_type = 'danger';
            }
        }
    }
}

// Toggle User Status
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];
    
    $toggle_query = "UPDATE users SET is_active = NOT is_active WHERE user_id = ?";
    $toggle_stmt = mysqli_prepare($connection, $toggle_query);
    mysqli_stmt_bind_param($toggle_stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($toggle_stmt)) {
        $message = "User status updated successfully.";
        $message_type = 'success';
    } else {
        $message = "Failed to update user status.";
        $message_type = 'danger';
    }
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search and filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($connection, $_GET['category']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($connection, $_GET['status']) : '';

// Build query conditions
$where_conditions = [];
if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR 
                            email LIKE '%$search%' OR phone_cell LIKE '%$search%' OR 
                            CONCAT(first_name, ' ', last_name) LIKE '%$search%')";
}
if (!empty($category_filter)) {
    $where_conditions[] = "customer_category = '$category_filter'";
}
if ($status_filter !== '') {
    $where_conditions[] = "is_active = " . ($status_filter == 'active' ? 1 : 0);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_result = mysqli_query($connection, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get users with pagination
$users_query = "SELECT 
                    u.*,
                    (SELECT COUNT(*) FROM orders WHERE user_id = u.user_id) as order_count
                FROM users u
                $where_clause
                ORDER BY u.user_id DESC
                LIMIT $offset, $records_per_page";

$users_result = mysqli_query($connection, $users_query);

// Get user statistics
$stats_query = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users,
                    SUM(total_orders) as total_orders,
                    SUM(total_spent) as total_revenue,
                    AVG(total_spent) as avg_spent
                FROM users";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get category distribution
$category_stats_query = "SELECT 
                            customer_category,
                            COUNT(*) as count,
                            SUM(total_orders) as orders,
                            SUM(total_spent) as revenue
                        FROM users
                        GROUP BY customer_category
                        ORDER BY count DESC";
$category_stats_result = mysqli_query($connection, $category_stats_query);

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
                            <i class="fas fa-users me-2"></i>
                            Users Management
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Users</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus me-2"></i>Add New User
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

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(30,58,95,0.1); color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Users</span>
                    <span class="stat-value"><?php echo $stats['total_users']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(25,135,84,0.1); color: var(--success);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Active Users</span>
                    <span class="stat-value"><?php echo $stats['active_users']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(220,53,69,0.1); color: var(--danger);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Inactive Users</span>
                    <span class="stat-value"><?php echo $stats['inactive_users']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(13,202,240,0.1); color: var(--info);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Orders</span>
                    <span class="stat-value"><?php echo $stats['total_orders']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255,193,7,0.1); color: var(--warning);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Revenue</span>
                    <span class="stat-value">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(108,117,125,0.1); color: var(--dark-gray);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Avg. Spent</span>
                    <span class="stat-value">$<?php echo number_format($stats['avg_spent'] ?? 0, 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Distribution -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="category-distribution">
                <div class="distribution-header">
                    <h6><i class="fas fa-chart-pie me-2"></i>Customer Categories</h6>
                </div>
                <div class="distribution-body">
                    <div class="row">
                        <?php while ($cat = mysqli_fetch_assoc($category_stats_result)): ?>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="category-badge category-<?php echo strtolower($cat['customer_category']); ?>">
                                <div class="badge-label"><?php echo $cat['customer_category']; ?></div>
                                <div class="badge-count"><?php echo $cat['count']; ?> users</div>
                                <div class="badge-revenue">$<?php echo number_format($cat['revenue'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="filters-card">
                <form method="GET" action="" class="filters-form">
                    <div class="row g-3">
                        <div class="col-lg-5 col-md-12">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by name, email, phone..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <option value="Regular" <?php echo $category_filter == 'Regular' ? 'selected' : ''; ?>>Regular</option>
                                <option value="VIP" <?php echo $category_filter == 'VIP' ? 'selected' : ''; ?>>VIP</option>
                                <option value="Wholesale" <?php echo $category_filter == 'Wholesale' ? 'selected' : ''; ?>>Wholesale</option>
                                <option value="Friend_Family" <?php echo $category_filter == 'Friend_Family' ? 'selected' : ''; ?>>Friend & Family</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-12">
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                                <a href="users.php" class="btn btn-light w-100 mt-2 mt-lg-0">
                                    <i class="fas fa-times me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <h5><i class="fas fa-list me-2"></i>Users List</h5>
                        <span class="records-count">Showing <?php echo min($offset + 1, $total_records); ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records</span>
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-light" onclick="exportToCSV()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th>Orders</th>
                                <th>Spent</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($users_result) > 0): ?>
                                <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                <tr>
                                    <td>
                                        <span class="user-id">#<?php echo $user['user_id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </div>
                                            <div class="user-email small text-muted">
                                                <?php echo $user['email']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div><i class="fas fa-phone-alt me-1"></i> <?php echo $user['phone_cell'] ?: 'N/A'; ?></div>
                                            <?php if (!empty($user['phone_work'])): ?>
                                            <div class="small text-muted"><i class="fas fa-briefcase me-1"></i> <?php echo $user['phone_work']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="location-info">
                                            <div><?php echo htmlspecialchars($user['address_city']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($user['address_country']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge category-<?php echo strtolower($user['customer_category']); ?>">
                                            <?php echo $user['customer_category']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="order-count"><?php echo $user['order_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="spent-amount">$<?php echo number_format($user['total_spent'] ?? 0, 2); ?></span>
                                    </td>
                                    <td>
                                        <a href="?toggle_status=<?php echo $user['user_id']; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                           class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"
                                           onclick="return confirm('Toggle status for this user?')">
                                            <i class="fas fa-<?php echo $user['is_active'] ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="register-date">
                                            <?php echo date('M d, Y', strtotime($user['registration_date'])); ?>
                                        </div>
                                        <?php if ($user['last_login']): ?>
                                        <div class="last-login small text-muted">
                                            Last: <?php echo date('M d, H:i', strtotime($user['last_login'])); ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action view" title="View Details" 
                                                    onclick="viewUser(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action edit" title="Edit User" 
                                                    onclick="editUser(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action delete" title="Delete User" 
                                                    onclick="confirmDelete(<?php echo $user['user_id']; ?>)"
                                                    <?php echo $user['order_count'] > 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <i class="fas fa-users" style="font-size: 3rem; color: var(--light-gray);"></i>
                                        <p class="mt-3 mb-0">No users found</p>
                                        <p class="small text-muted">Click "Add New User" to create your first user</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="table-footer">
                    <nav aria-label="Users pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">
                    <i class="fas fa-user-plus me-2" style="color: var(--primary);"></i>
                    Add New User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row">
                        <h6 class="section-title">Personal Information</h6>
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone_cell" class="form-label">Mobile Phone *</label>
                            <input type="tel" class="form-control" id="phone_cell" name="phone_cell" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone_work" class="form-label">Work Phone</label>
                            <input type="tel" class="form-control" id="phone_work" name="phone_work">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="customer_category" class="form-label">Customer Category</label>
                            <select class="form-select" id="customer_category" name="customer_category">
                                <option value="Regular">Regular</option>
                                <option value="VIP">VIP</option>
                                <option value="Wholesale">Wholesale</option>
                                <option value="Friend_Family">Friend & Family</option>
                            </select>
                        </div>
                        
                        <h6 class="section-title mt-3">Address Information</h6>
                        
                        <div class="col-12 mb-3">
                            <label for="address_street" class="form-label">Street Address *</label>
                            <input type="text" class="form-control" id="address_street" name="address_street" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="address_city" class="form-label">City *</label>
                            <input type="text" class="form-control" id="address_city" name="address_city" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="address_state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="address_state" name="address_state">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="address_zip" class="form-label">ZIP/Postal Code</label>
                            <input type="text" class="form-control" id="address_zip" name="address_zip">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="address_country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="address_country" name="address_country" value="USA">
                        </div>
                        
                        <h6 class="section-title mt-3">Additional Information</h6>
                        
                        <div class="col-12 mb-3">
                            <label for="remarks" class="form-label">Remarks/Notes</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active Account
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="fas fa-user-edit me-2" style="color: var(--primary);"></i>
                    Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="row">
                        <h6 class="section-title">Personal Information</h6>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password" placeholder="Leave blank to keep current">
                            <small class="text-muted">Only fill if you want to change password</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_phone_cell" class="form-label">Mobile Phone *</label>
                            <input type="tel" class="form-control" id="edit_phone_cell" name="phone_cell" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_phone_work" class="form-label">Work Phone</label>
                            <input type="tel" class="form-control" id="edit_phone_work" name="phone_work">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_customer_category" class="form-label">Customer Category</label>
                            <select class="form-select" id="edit_customer_category" name="customer_category">
                                <option value="Regular">Regular</option>
                                <option value="VIP">VIP</option>
                                <option value="Wholesale">Wholesale</option>
                                <option value="Friend_Family">Friend & Family</option>
                            </select>
                        </div>
                        
                        <h6 class="section-title mt-3">Address Information</h6>
                        
                        <div class="col-12 mb-3">
                            <label for="edit_address_street" class="form-label">Street Address *</label>
                            <input type="text" class="form-control" id="edit_address_street" name="address_street" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_address_city" class="form-label">City *</label>
                            <input type="text" class="form-control" id="edit_address_city" name="address_city" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_address_state" class="form-label">State/Province</label>
                            <input type="text" class="form-control" id="edit_address_state" name="address_state">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_address_zip" class="form-label">ZIP/Postal Code</label>
                            <input type="text" class="form-control" id="edit_address_zip" name="address_zip">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_address_country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="edit_address_country" name="address_country">
                        </div>
                        
                        <h6 class="section-title mt-3">Additional Information</h6>
                        
                        <div class="col-12 mb-3">
                            <label for="edit_remarks" class="form-label">Remarks/Notes</label>
                            <textarea class="form-control" id="edit_remarks" name="remarks" rows="2"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Active Account
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="info-box">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Total Orders: <span id="edit_total_orders">0</span> | 
                                       Total Spent: $<span id="edit_total_spent">0.00</span> | 
                                       Registered: <span id="edit_registration_date"></span></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserModalLabel">
                    <i class="fas fa-user me-2" style="color: var(--primary);"></i>
                    User Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewUserContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editFromViewBtn">
                    <i class="fas fa-edit me-2"></i>Edit User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Page-specific scripts -->
<script>
// Edit user function
function editUser(userId) {
    // Fetch user details via AJAX
    fetch('get_user.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_user_id').value = data.user.user_id;
                document.getElementById('edit_first_name').value = data.user.first_name;
                document.getElementById('edit_last_name').value = data.user.last_name;
                document.getElementById('edit_email').value = data.user.email;
                document.getElementById('edit_phone_cell').value = data.user.phone_cell || '';
                document.getElementById('edit_phone_work').value = data.user.phone_work || '';
                document.getElementById('edit_date_of_birth').value = data.user.date_of_birth || '';
                document.getElementById('edit_address_street').value = data.user.address_street || '';
                document.getElementById('edit_address_city').value = data.user.address_city || '';
                document.getElementById('edit_address_state').value = data.user.address_state || '';
                document.getElementById('edit_address_zip').value = data.user.address_zip || '';
                document.getElementById('edit_address_country').value = data.user.address_country || 'USA';
                document.getElementById('edit_customer_category').value = data.user.customer_category || 'Regular';
                document.getElementById('edit_remarks').value = data.user.remarks || '';
                document.getElementById('edit_is_active').checked = data.user.is_active == 1;
                document.getElementById('edit_total_orders').textContent = data.user.total_orders || 0;
                document.getElementById('edit_total_spent').textContent = parseFloat(data.user.total_spent || 0).toFixed(2);
                document.getElementById('edit_registration_date').textContent = new Date(data.user.registration_date).toLocaleDateString();
                
                new bootstrap.Modal(document.getElementById('editUserModal')).show();
            } else {
                showNotification('Failed to load user details', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'danger');
        });
}

// View user function
function viewUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    const contentDiv = document.getElementById('viewUserContent');
    
    // Show loading
    contentDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // Fetch user details
    fetch('get_user.php?id=' + userId + '&details=full')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                const html = `
                    <div class="user-details-view">
                        <div class="text-center mb-4">
                            <div class="user-avatar-large">
                                ${user.first_name.charAt(0)}${user.last_name.charAt(0)}
                            </div>
                            <h4 class="mt-2">${user.first_name} ${user.last_name}</h4>
                            <span class="category-badge category-${user.customer_category.toLowerCase()}">${user.customer_category}</span>
                            <span class="status-badge status-${user.is_active ? 'active' : 'inactive'} ms-2">
                                <i class="fas fa-${user.is_active ? 'check-circle' : 'times-circle'} me-1"></i>
                                ${user.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6>Contact Information</h6>
                                    <p><i class="fas fa-envelope me-2"></i> ${user.email}</p>
                                    <p><i class="fas fa-phone-alt me-2"></i> ${user.phone_cell || 'N/A'}</p>
                                    ${user.phone_work ? `<p><i class="fas fa-briefcase me-2"></i> ${user.phone_work}</p>` : ''}
                                    ${user.date_of_birth ? `<p><i class="fas fa-birthday-cake me-2"></i> ${user.date_of_birth}</p>` : ''}
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6>Address Information</h6>
                                    <p><i class="fas fa-home me-2"></i> ${user.address_street || 'N/A'}</p>
                                    <p><i class="fas fa-city me-2"></i> ${user.address_city || 'N/A'}, ${user.address_state || ''} ${user.address_zip || ''}</p>
                                    <p><i class="fas fa-globe me-2"></i> ${user.address_country || 'N/A'}</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6>Account Statistics</h6>
                                    <p><i class="fas fa-shopping-cart me-2"></i> Total Orders: ${user.total_orders || 0}</p>
                                    <p><i class="fas fa-dollar-sign me-2"></i> Total Spent: $${parseFloat(user.total_spent || 0).toFixed(2)}</p>
                                    <p><i class="fas fa-calendar me-2"></i> Registered: ${new Date(user.registration_date).toLocaleDateString()}</p>
                                    ${user.last_login ? `<p><i class="fas fa-clock me-2"></i> Last Login: ${new Date(user.last_login).toLocaleString()}</p>` : ''}
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6>Additional Information</h6>
                                    <p><i class="fas fa-tag me-2"></i> Category: ${user.customer_category}</p>
                                    ${user.remarks ? `<p><i class="fas fa-comment me-2"></i> Remarks: ${user.remarks}</p>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                contentDiv.innerHTML = html;
                
                // Set up edit button
                document.getElementById('editFromViewBtn').onclick = function() {
                    modal.hide();
                    setTimeout(() => editUser(userId), 500);
                };
            } else {
                contentDiv.innerHTML = '<div class="alert alert-danger">Failed to load user details</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contentDiv.innerHTML = '<div class="alert alert-danger">An error occurred</div>';
        });
}

// Confirm delete
function confirmDelete(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        window.location.href = 'users.php?delete=' + userId + '&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>';
    }
}

// Export to CSV
function exportToCSV() {
    // Collect table data
    const rows = [];
    const table = document.querySelector('.users-table');
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    rows.push(headers);
    
    const dataRows = Array.from(table.querySelectorAll('tbody tr'));
    dataRows.forEach(row => {
        const rowData = [
            row.querySelector('.user-id')?.textContent.trim() || '',
            row.querySelector('.user-name')?.textContent.trim() || '',
            row.querySelector('.user-email')?.textContent.trim() || '',
            row.querySelector('.contact-info')?.textContent.trim().replace(/\s+/g, ' ') || '',
            row.querySelector('.location-info')?.textContent.trim().replace(/\s+/g, ' ') || '',
            row.querySelector('.category-badge')?.textContent.trim() || '',
            row.querySelector('.order-count')?.textContent.trim() || '',
            row.querySelector('.spent-amount')?.textContent.trim() || '',
            row.querySelector('.status-badge')?.textContent.trim() || '',
            row.querySelector('.register-date')?.textContent.trim() || ''
        ];
        rows.push(rowData);
    });
    
    // Create CSV
    let csv = rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'users_export_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Users exported successfully', 'success');
}
</script>

<style>
/* ===== USERS PAGE SPECIFIC STYLES ===== */

/* Stat Cards */
.stat-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.stat-details {
    flex: 1;
}

.stat-label {
    display: block;
    font-size: 0.8rem;
    color: var(--dark-gray);
    margin-bottom: 0.2rem;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.2;
}

/* Category Distribution */
.category-distribution {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.distribution-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.distribution-header h6 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
}

.distribution-body {
    padding: 1.5rem;
}

/* Category Badges */
.category-badge {
    display: inline-block;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.category-regular {
    background: #e2e3e5;
    color: #41464b;
}

.category-vip {
    background: #ffd700;
    color: #856404;
}

.category-wholesale {
    background: #cfe2ff;
    color: #084298;
}

.category-friend_family {
    background: #d1e7dd;
    color: #0a3622;
}

.badge-label {
    font-weight: 600;
    margin-bottom: 0.2rem;
}

.badge-count {
    font-size: 0.8rem;
    opacity: 0.9;
}

.badge-revenue {
    font-size: 0.8rem;
    font-weight: 600;
}

/* Users Table */
.users-table {
    margin: 0;
}

.users-table thead th {
    background: var(--light);
    color: var(--dark);
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
    padding: 1rem 0.75rem;
    white-space: nowrap;
}

.users-table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--border);
    font-size: 0.9rem;
}

.users-table tbody tr:hover {
    background: var(--light);
}

/* User ID */
.user-id {
    font-weight: 600;
    color: var(--primary);
    font-family: var(--font-mono);
    font-size: 0.85rem;
}

/* User Info */
.user-info {
    line-height: 1.4;
}

.user-name {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.2rem;
}

/* Contact Info */
.contact-info {
    line-height: 1.4;
}

.contact-info i {
    color: var(--primary);
    width: 16px;
}

/* Location Info */
.location-info {
    line-height: 1.4;
}

/* Order Count */
.order-count {
    display: inline-block;
    min-width: 30px;
    padding: 0.2rem 0.5rem;
    background: var(--light);
    border-radius: 12px;
    font-weight: 600;
    color: var(--dark);
}

/* Spent Amount */
.spent-amount {
    font-weight: 600;
    color: var(--primary);
    font-family: var(--font-mono);
}

/* Register Date */
.register-date {
    font-size: 0.85rem;
    color: var(--dark);
}

.last-login {
    font-size: 0.75rem;
}

/* Section Title */
.section-title {
    color: var(--primary);
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border);
}

/* Info Box */
.info-box {
    background: var(--light);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    color: var(--dark-gray);
}

/* User Details View */
.user-details-view {
    padding: 0.5rem;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 600;
    margin: 0 auto;
}

.detail-section {
    background: var(--light);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid var(--border);
}

.detail-section h6 {
    color: var(--primary);
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border);
}

.detail-section p {
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.detail-section i {
    color: var(--primary);
    width: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.5rem;
    }
    
    .stat-value {
        font-size: 1.2rem;
    }
    
    .users-table thead th {
        font-size: 0.7rem;
        padding: 0.75rem 0.5rem;
    }
    
    .users-table tbody td {
        padding: 0.75rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .action-buttons {
        gap: 0.3rem;
    }
    
    .btn-action {
        width: 28px;
        height: 28px;
    }
}

@media (max-width: 576px) {
    .category-badge {
        padding: 0.3rem 0.6rem;
        font-size: 0.75rem;
    }
    
    .status-badge {
        padding: 0.3rem 0.6rem;
        font-size: 0.7rem;
    }
}

/* Print Styles */
@media print {
    .btn,
    .btn-action,
    .status-badge,
    .filter-actions,
    .table-actions,
    .back-to-top {
        display: none !important;
    }
    
    .table-card {
        border: 1px solid #000;
        box-shadow: none;
    }
    
    .status-badge {
        border: 1px solid #000;
        background: transparent !important;
        color: #000 !important;
    }
}
</style>