<?php
// admin/dashboard.php - Admin dashboard - FIXED

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

<!-- Rest of your dashboard code remains the same -->
<!-- Just remove the session_start() from the top as shown above -->
<div class="container-fluid">
    <!-- Welcome Message -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
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
                            <i class="fas fa-users fa-2x text-primary"></i>
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
                            <i class="fas fa-box fa-2x text-success"></i>
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
                            <i class="fas fa-shopping-cart fa-2x text-info"></i>
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
                            <i class="fas fa-dollar-sign fa-2x text-warning"></i>
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
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Recent Orders
                    </h6>
                    <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
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
                                        <?php
                                        $status_classes = [
                                            'Pending' => 'warning',
                                            'Processing' => 'info',
                                            'Shipped' => 'primary',
                                            'Delivered' => 'success',
                                            'Cancelled' => 'danger',
                                            'Refunded' => 'secondary'
                                        ];
                                        $badge_class = $status_classes[$order['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" 
                                           class="btn btn-sm btn-primary">
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
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Products
                    </h6>
                    <a href="products.php" class="btn btn-sm btn-danger">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_products)): ?>
                    <div class="alert alert-success">
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
                                <span class="badge bg-danger rounded-pill">
                                    <?php echo $product['quantity_in_stock']; ?> left
                                </span>
                                <div>
                                    <small>Min: <?php echo $product['min_stock_level']; ?></small>
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
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="add_product.php" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-plus me-1"></i>Add Product
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="add_category.php" class="btn btn-info w-100 mb-2">
                                <i class="fas fa-folder-plus me-1"></i>Add Category
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="reports.php" class="btn btn-warning w-100 mb-2">
                                <i class="fas fa-chart-bar me-1"></i>Reports
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="users.php" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-users me-1"></i>Users
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
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-plus me-2"></i>Recent Users
                    </h6>
                    <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_users)): ?>
                    <div class="alert alert-info">No users found</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
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
                                        <span class="badge bg-primary">
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
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>System Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>PHP Version:</strong>
                                <p class="mb-0"><?php echo phpversion(); ?></p>
                            </div>
                            <div class="mb-3">
                                <strong>MySQL Version:</strong>
                                <p class="mb-0">
                                    <?php echo mysqli_get_server_info($connection); ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <strong>Server Time:</strong>
                                <p class="mb-0"><?php echo date('Y-m-d H:i:s'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>Memory Usage:</strong>
                                <p class="mb-0">
                                    <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB
                                </p>
                            </div>
                            <div class="mb-3">
                                <strong>Database Size:</strong>
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
                                <strong>Uptime:</strong>
                                <p class="mb-0">
                                    <?php
                                    $uptime_query = "SHOW STATUS LIKE 'Uptime'";
                                    $uptime_result = mysqli_query($connection, $uptime_query);
                                    $uptime = mysqli_fetch_assoc($uptime_result)['Value'];
                                    $hours = floor($uptime / 3600);
                                    $minutes = floor(($uptime % 3600) / 60);
                                    $seconds = $uptime % 60;
                                    echo sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
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