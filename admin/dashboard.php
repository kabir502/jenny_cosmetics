<?php
// admin/dashboard.php - Admin dashboard - PROFESSIONAL CORPORATE THEME

// Include central session handler from root
require_once '../session_handler.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database
require_once '../config/database.php';

// Get statistics for dashboard
$stats = [];

// Total users
$users_query = "SELECT COUNT(*) as total FROM users";
$users_result = mysqli_query($connection, $users_query);
$stats['total_users'] = mysqli_fetch_assoc($users_result)['total'];

// Total products
$products_query = "SELECT COUNT(*) as total FROM products";
$products_result = mysqli_query($connection, $products_query);
$stats['total_products'] = mysqli_fetch_assoc($products_result)['total'];

// Total orders
$orders_query = "SELECT COUNT(*) as total FROM orders";
$orders_result = mysqli_query($connection, $orders_query);
$stats['total_orders'] = mysqli_fetch_assoc($orders_result)['total'];

// Total revenue
$revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE status IN ('Delivered', 'Shipped')";
$revenue_result = mysqli_query($connection, $revenue_query);
$stats['total_revenue'] = mysqli_fetch_assoc($revenue_result)['total'] ?? 0;

// Recent orders
$recent_orders_query = "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        ORDER BY o.order_date DESC 
                        LIMIT 5";
$recent_orders_result = mysqli_query($connection, $recent_orders_query);
$recent_orders = [];
while ($order = mysqli_fetch_assoc($recent_orders_result)) {
    $recent_orders[] = $order;
}

// Low stock products
$low_stock_query = "SELECT * FROM products 
                    WHERE quantity_in_stock <= 10 
                    AND quantity_in_stock > 0 
                    ORDER BY quantity_in_stock ASC 
                    LIMIT 5";
$low_stock_result = mysqli_query($connection, $low_stock_query);
$low_stock_products = [];
while ($product = mysqli_fetch_assoc($low_stock_result)) {
    $low_stock_products[] = $product;
}

// Recent users
$recent_users_query = "SELECT * FROM users 
                       ORDER BY registration_date DESC 
                       LIMIT 5";
$recent_users_result = mysqli_query($connection, $recent_users_query);
$recent_users = [];
while ($user = mysqli_fetch_assoc($recent_users_result)) {
    $recent_users[] = $user;
}

// Include admin header
include '../includes/admin_header.php';
?>

<style>
/* ===== PROFESSIONAL CORPORATE THEME ===== */
:root {
    /* Corporate Color Palette */
    --primary: #1e3a5f;       /* Deep navy - trust, authority */
    --primary-light: #2b4c7c;  /* Lighter navy */
    --secondary: #2c3e50;      /* Slate - professionalism */
    --accent: #0d6efd;         /* Blue - actions, links */
    --success: #198754;        /* Green - positive */
    --warning: #ffc107;        /* Amber - caution */
    --danger: #dc3545;         /* Red - alerts */
    --info: #0dcaf0;           /* Cyan - information */
    
    /* Neutral Colors */
    --white: #ffffff;
    --light: #f8fafc;          /* Page background */
    --light-gray: #e9ecef;      /* Card borders */
    --medium-gray: #ced4da;     /* Input borders */
    --dark-gray: #6c757d;       /* Secondary text */
    --dark: #212529;            /* Primary text */
    
    /* Spacing System - 8px base */
    --space-xs: 0.5rem;   /* 8px */
    --space-sm: 1rem;     /* 16px */
    --space-md: 1.5rem;   /* 24px */
    --space-lg: 2rem;     /* 32px */
    --space-xl: 3rem;     /* 48px */
    
    /* Typography */
    --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-mono: 'SF Mono', 'Menlo', 'Monaco', 'Cascadia Code', 'Consolas', monospace;
    
    /* Borders & Shadows */
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
    --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
    --shadow-focus: 0 0 0 3px rgba(13,110,253,0.25);
}

/* Base Styles */
body {
    font-family: var(--font-primary);
    background: var(--light);
    color: var(--dark);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.container-fluid {
    padding: var(--space-lg);
    max-width: 1600px;
    margin: 0 auto;
}

/* Welcome Card - Professional Header */
.bg-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
    border: none !important;
    border-radius: var(--radius-lg) !important;
    box-shadow: var(--shadow-lg);
    margin-bottom: var(--space-xl);
    position: relative;
    overflow: hidden;
}

.bg-primary::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}

.bg-primary .card-body {
    padding: var(--space-xl) !important;
    position: relative;
    z-index: 1;
}

.bg-primary h2 {
    font-weight: 600;
    font-size: 2.2rem;
    margin-bottom: var(--space-xs);
    letter-spacing: -0.02em;
}

.bg-primary strong {
    font-weight: 700;
    background: rgba(255,255,255,0.2);
    padding: 0.2rem 0.6rem;
    border-radius: var(--radius-sm);
}

.bg-primary small {
    font-size: 0.9rem;
    opacity: 0.9;
    display: block;
    margin-top: var(--space-xs);
}

/* Statistics Cards - Professional Metrics */
.card.border-left-primary,
.card.border-left-success,
.card.border-left-info,
.card.border-left-warning {
    border: 1px solid var(--light-gray) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease;
    background: var(--white);
}

.card.border-left-primary:hover,
.card.border-left-success:hover,
.card.border-left-info:hover,
.card.border-left-warning:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: transparent !important;
}

.card.border-left-primary {
    border-left: 4px solid var(--primary) !important;
}

.card.border-left-success {
    border-left: 4px solid var(--success) !important;
}

.card.border-left-info {
    border-left: 4px solid var(--info) !important;
}

.card.border-left-warning {
    border-left: 4px solid var(--warning) !important;
}

.card .card-body {
    padding: var(--space-md);
}

.card .text-xs {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--dark-gray);
    margin-bottom: var(--space-xs);
}

.card .h5 {
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--dark);
    margin: var(--space-xs) 0;
    line-height: 1.2;
    font-family: var(--font-mono);
}

.card .col-auto i {
    font-size: 2.5rem;
    color: var(--primary);
    opacity: 0.8;
}

/* Card Headers - Professional */
.card-header {
    background: var(--white) !important;
    border-bottom: 2px solid var(--light-gray) !important;
    padding: var(--space-md) var(--space-lg) !important;
    border-radius: var(--radius-md) var(--radius-md) 0 0 !important;
}

.card-header h6 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--dark);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card-header h6 i {
    color: var(--primary);
    margin-right: var(--space-sm);
}

/* Professional Buttons */
.btn-sm {
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid var(--light-gray);
    background: var(--white);
    color: var(--dark);
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border-radius: var(--radius-sm);
    cursor: pointer;
}

.btn-sm:hover {
    background: var(--light);
    border-color: var(--medium-gray);
}

.btn-sm:focus {
    outline: none;
    box-shadow: var(--shadow-focus);
}

/* Action Button */
.btn-sm.action-btn {
    padding: 0.25rem 0.75rem;
    font-size: 0.8rem;
    background: var(--white);
    border: 1px solid var(--light-gray);
}

.btn-sm.action-btn i {
    font-size: 0.9rem;
    color: var(--primary);
}

/* Quick Action Buttons - Professional Grid */
.quick-action-btn {
    width: 100%;
    padding: 1rem 0.5rem;
    font-weight: 500;
    border: 1px solid var(--light-gray);
    background: var(--white);
    color: var(--dark);
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    text-decoration: none;
    font-size: 0.95rem;
    border-radius: var(--radius-sm);
}

.quick-action-btn:hover {
    background: var(--primary);
    color: var(--white);
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.quick-action-btn:hover i {
    color: var(--white);
}

.quick-action-btn i {
    color: var(--primary);
    font-size: 1.1rem;
    transition: color 0.2s;
}

/* Tables - Professional Data Display */
.table-responsive {
    border-radius: var(--radius-md);
    border: 1px solid var(--light-gray);
    background: var(--white);
}

.table {
    margin: 0;
    width: 100%;
}

.table thead th {
    background: var(--light);
    color: var(--dark);
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--light-gray);
    padding: 1rem;
    white-space: nowrap;
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--light-gray);
    color: var(--dark);
    font-size: 0.95rem;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table tbody tr:hover {
    background: var(--light);
}

/* Badges - Professional Status Indicators */
.badge {
    padding: 0.5rem 1rem;
    font-weight: 500;
    font-size: 0.75rem;
    border-radius: 20px;
    text-transform: capitalize;
    letter-spacing: 0.3px;
}

.badge.bg-warning {
    background: #fff3cd !important;
    color: #856404 !important;
    border: 1px solid #ffe69c;
}

.badge.bg-info {
    background: #cff4fc !important;
    color: #055160 !important;
    border: 1px solid #b6effb;
}

.badge.bg-primary {
    background: #cfe2ff !important;
    color: #084298 !important;
    border: 1px solid #9ec5fe;
}

.badge.bg-success {
    background: #d1e7dd !important;
    color: #0a3622 !important;
    border: 1px solid #a3cfbb;
}

.badge.bg-danger {
    background: #f8d7da !important;
    color: #842029 !important;
    border: 1px solid #f1aeb5;
}

.badge.bg-secondary {
    background: #e2e3e5 !important;
    color: #41464b !important;
    border: 1px solid #c4c8cb;
}

/* List Group - Professional */
.list-group-item {
    border: none;
    border-bottom: 1px solid var(--light-gray);
    padding: 1rem;
    background: transparent;
}

.list-group-item:last-child {
    border-bottom: none;
}

.list-group-item h6 {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.25rem;
}

.list-group-item .badge.bg-danger {
    background: #f8d7da !important;
    color: #842029 !important;
    border-color: #f1aeb5;
}

/* Alerts - Professional Notifications */
.alert-success {
    background: #d1e7dd;
    color: #0a3622;
    border: 1px solid #a3cfbb;
    border-left: 4px solid var(--success);
    border-radius: var(--radius-sm);
    padding: 1rem;
}

.alert-info {
    background: #cff4fc;
    color: #055160;
    border: 1px solid #b6effb;
    border-left: 4px solid var(--info);
    border-radius: var(--radius-sm);
    padding: 1rem;
}

/* System Info Card - Professional */
.card-body .mb-3 {
    background: var(--light);
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid var(--light-gray);
    border-radius: var(--radius-sm);
    transition: all 0.2s ease;
}

.card-body .mb-3:hover {
    background: var(--white);
    border-color: var(--primary);
}

.card-body .mb-3 strong {
    display: block;
    color: var(--dark-gray);
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card-body .mb-3 p {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
    font-family: var(--font-mono);
}

/* Card Shadows & Borders */
.card.shadow {
    border: 1px solid var(--light-gray) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: var(--shadow-sm) !important;
    background: var(--white);
    transition: all 0.2s ease;
}

.card.shadow:hover {
    box-shadow: var(--shadow-md) !important;
}

/* Links */
a {
    color: var(--accent);
    text-decoration: none;
    transition: color 0.2s;
}

a:hover {
    color: var(--primary);
}

/* Icons */
.fa-2x {
    color: var(--primary);
    opacity: 0.8;
    transition: all 0.2s;
}

.card:hover .fa-2x {
    opacity: 1;
    transform: scale(1.1);
}

/* Typography */
h1, h2, h3, h4, h5, h6 {
    font-weight: 600;
    color: var(--dark);
}

.text-muted {
    color: var(--dark-gray) !important;
    font-size: 0.875rem;
}

/* Grid System */
.row {
    margin: 0 -0.75rem;
}

[class*="col-"] {
    padding: 0 0.75rem;
}

/* Responsive Design */
@media (max-width: 1400px) {
    .container-fluid {
        padding: var(--space-md);
    }
    
    .card .h5 {
        font-size: 2rem;
    }
}

@media (max-width: 1200px) {
    .bg-primary h2 {
        font-size: 2rem;
    }
    
    .card .h5 {
        font-size: 1.8rem;
    }
}

@media (max-width: 992px) {
    .container-fluid {
        padding: var(--space-sm);
    }
    
    .bg-primary h2 {
        font-size: 1.8rem;
    }
    
    .card .h5 {
        font-size: 1.6rem;
    }
}

@media (max-width: 768px) {
    .container-fluid {
        padding: var(--space-xs);
    }
    
    .bg-primary h2 {
        font-size: 1.5rem;
    }
    
    .bg-primary .card-body {
        padding: var(--space-md) !important;
    }
    
    .card .h5 {
        font-size: 1.5rem;
    }
    
    .card-header {
        padding: var(--space-sm) var(--space-md) !important;
    }
    
    .card-header h6 {
        font-size: 0.9rem;
    }
    
    .table thead th {
        font-size: 0.7rem;
        padding: 0.75rem;
    }
    
    .table tbody td {
        padding: 0.75rem;
        font-size: 0.85rem;
    }
    
    .btn-sm {
        padding: 0.4rem 1rem;
        font-size: 0.8rem;
    }
    
    .quick-action-btn {
        padding: 0.8rem 0.4rem;
        font-size: 0.85rem;
    }
    
    .quick-action-btn i {
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    .bg-primary h2 {
        font-size: 1.25rem;
    }
    
    .bg-primary .text-end {
        margin-top: var(--space-sm);
        text-align: left !important;
    }
    
    .card .h5 {
        font-size: 1.3rem;
    }
    
    .card .col-auto i {
        font-size: 2rem;
    }
    
    .badge {
        padding: 0.4rem 0.6rem;
        font-size: 0.65rem;
    }
    
    .quick-action-btn {
        padding: 0.6rem 0.4rem;
        font-size: 0.8rem;
    }
    
    .quick-action-btn i {
        font-size: 0.9rem;
    }
}

/* Professional Elements */
hr {
    border: none;
    border-top: 2px solid var(--light-gray);
    margin: var(--space-lg) 0;
}

/* Focus States */
*:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
    border-radius: 2px;
}

/* Selection */
::selection {
    background: var(--primary);
    color: var(--white);
}

/* Scrollbar - Professional */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--light);
}

::-webkit-scrollbar-thumb {
    background: var(--dark-gray);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary);
}

/* Loading States */
.btn-sm.loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;
}

.btn-sm.loading::after {
    content: '';
    position: absolute;
    width: 1rem;
    height: 1rem;
    top: 50%;
    left: 50%;
    margin: -0.5rem 0 0 -0.5rem;
    border: 2px solid var(--light-gray);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spinner 0.6s linear infinite;
}

@keyframes spinner {
    to { transform: rotate(360deg); }
}

/* Print Styles */
@media print {
    .btn-sm,
    .quick-action-btn,
    .card-header .btn-sm {
        display: none !important;
    }
    
    .card {
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    
    .badge {
        border: 1px solid #999;
        color: #000 !important;
        background: transparent !important;
    }
}
</style>

<!-- Rest of your dashboard code remains exactly the same -->
<div class="container-fluid">
    <!-- Welcome Message -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h2 class="mb-1">Welcome, <?php echo $_SESSION['admin_name']; ?>!</h2>
                            <p class="mb-0">You are logged in as <strong><?php echo $_SESSION['admin_role']; ?></strong></p>
                        </div>
                        <div class="text-end">
                            <p class="mb-0">Today: <?php echo date('l, F j, Y'); ?></p>
                            <small>Last login: <?php echo date('M d, Y h:i A', $_SESSION['admin_last_login'] ?? time()); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_users']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Products
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_products']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Orders
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_orders']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Revenue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php echo number_format($stats['total_revenue'], 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-shopping-cart me-2"></i>Recent Orders
                    </h6>
                    <a href="orders.php" class="btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No orders found</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><?php echo $order['order_number']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $status_classes[$order['status']] ?? 'secondary'; 
                                        ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" 
                                           class="btn-sm action-btn"
                                           title="View Order Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Low Stock Products -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Products
                    </h6>
                    <a href="products.php" class="btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_products)): ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        All products have sufficient stock.
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($low_stock_products as $product): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                <small class="text-muted">SKU: <?php echo $product['sku']; ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger">
                                    <?php echo $product['quantity_in_stock']; ?> left
                                </span>
                                <div>
                                    <small class="text-muted">Min: <?php echo $product['min_stock_level']; ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="add_product.php" class="quick-action-btn">
                                <i class="fas fa-plus"></i>Add Product
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="add_category.php" class="quick-action-btn">
                                <i class="fas fa-folder-plus"></i>Add Category
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="reports.php" class="quick-action-btn">
                                <i class="fas fa-chart-bar"></i>Reports
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="users.php" class="quick-action-btn">
                                <i class="fas fa-users"></i>Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="row">
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-user-plus me-2"></i>Recent Users
                    </h6>
                    <a href="users.php" class="btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_users)): ?>
                    <div class="alert alert-info mb-0">No users found</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['registration_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo $user['total_orders']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- System Info -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-info-circle me-2"></i>System Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>PHP Version</strong>
                                <p class="mb-0"><?php echo phpversion(); ?></p>
                            </div>
                            <div class="mb-3">
                                <strong>MySQL Version</strong>
                                <p class="mb-0">
                                    <?php echo mysqli_get_server_info($connection); ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <strong>Server Time</strong>
                                <p class="mb-0"><?php echo date('Y-m-d H:i:s'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>Memory Usage</strong>
                                <p class="mb-0">
                                    <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB
                                </p>
                            </div>
                            <div class="mb-3">
                                <strong>Database Size</strong>
                                <?php
                                $size_query = "SELECT 
                                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
                                    FROM information_schema.TABLES 
                                    WHERE table_schema = DATABASE()";
                                $size_result = mysqli_query($connection, $size_query);
                                $db_size = mysqli_fetch_assoc($size_result)['size_mb'];
                                ?>
                                <p class="mb-0"><?php echo $db_size; ?> MB</p>
                            </div>
                            <div class="mb-3">
                                <strong>Server Uptime</strong>
                                <p class="mb-0">
                                    <?php
                                    $uptime_query = "SHOW STATUS LIKE 'Uptime'";
                                    $uptime_result = mysqli_query($connection, $uptime_query);
                                    $uptime = mysqli_fetch_assoc($uptime_result)['Value'];
                                    $hours = floor($uptime / 3600);
                                    $minutes = floor(($uptime % 3600) / 60);
                                    echo sprintf("%dh %dm", $hours, $minutes);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include admin footer
include '../includes/admin_footer.php';
?>