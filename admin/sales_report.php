<?php
// admin/sales_report.php - Sales Report Page

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

// Date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily';
$category_filter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$payment_filter = isset($_GET['payment_method']) ? mysqli_real_escape_string($connection, $_GET['payment_method']) : '';

// Validate dates
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Get sales summary
$summary_query = "SELECT 
                    COUNT(DISTINCT o.order_id) as total_orders,
                    COALESCE(SUM(o.total_amount), 0) as total_revenue,
                    COALESCE(AVG(o.total_amount), 0) as avg_order_value,
                    COALESCE(SUM(oi.quantity), 0) as total_items_sold,
                    COUNT(DISTINCT o.user_id) as unique_customers,
                    COALESCE(SUM(o.shipping_amount), 0) as total_shipping,
                    COALESCE(SUM(o.tax_amount), 0) as total_tax
                FROM orders o
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                AND o.status IN ('Delivered', 'Shipped')";

$summary_result = mysqli_query($connection, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

// Ensure all values are set
$summary['total_orders'] = $summary['total_orders'] ?? 0;
$summary['total_revenue'] = $summary['total_revenue'] ?? 0;
$summary['avg_order_value'] = $summary['avg_order_value'] ?? 0;
$summary['total_items_sold'] = $summary['total_items_sold'] ?? 0;
$summary['unique_customers'] = $summary['unique_customers'] ?? 0;
$summary['total_shipping'] = $summary['total_shipping'] ?? 0;
$summary['total_tax'] = $summary['total_tax'] ?? 0;

// Get daily sales data
if ($report_type == 'daily') {
    $sales_query = "SELECT 
                        DATE(o.order_date) as date,
                        COUNT(DISTINCT o.order_id) as order_count,
                        COALESCE(SUM(o.total_amount), 0) as revenue,
                        COALESCE(AVG(o.total_amount), 0) as avg_order,
                        COALESCE(SUM(oi.quantity), 0) as items_sold
                    FROM orders o
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                    AND o.status IN ('Delivered', 'Shipped')
                    GROUP BY DATE(o.order_date)
                    ORDER BY date DESC";
} 
// Get monthly sales data
elseif ($report_type == 'monthly') {
    $sales_query = "SELECT 
                        DATE_FORMAT(o.order_date, '%Y-%m') as month,
                        DATE_FORMAT(o.order_date, '%M %Y') as month_name,
                        COUNT(DISTINCT o.order_id) as order_count,
                        COALESCE(SUM(o.total_amount), 0) as revenue,
                        COALESCE(AVG(o.total_amount), 0) as avg_order,
                        COALESCE(SUM(oi.quantity), 0) as items_sold
                    FROM orders o
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                    AND o.status IN ('Delivered', 'Shipped')
                    GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
                    ORDER BY month DESC";
} 
// Get yearly sales data
elseif ($report_type == 'yearly') {
    $sales_query = "SELECT 
                        YEAR(o.order_date) as year,
                        COUNT(DISTINCT o.order_id) as order_count,
                        COALESCE(SUM(o.total_amount), 0) as revenue,
                        COALESCE(AVG(o.total_amount), 0) as avg_order,
                        COALESCE(SUM(oi.quantity), 0) as items_sold
                    FROM orders o
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                    AND o.status IN ('Delivered', 'Shipped')
                    GROUP BY YEAR(o.order_date)
                    ORDER BY year DESC";
}

$sales_result = mysqli_query($connection, $sales_query);

// Get sales by category
$category_sales_query = "SELECT 
                            c.category_name,
                            COUNT(DISTINCT o.order_id) as order_count,
                            COALESCE(SUM(oi.quantity), 0) as quantity_sold,
                            COALESCE(SUM(oi.total_price), 0) as revenue
                        FROM orders o
                        JOIN order_items oi ON o.order_id = oi.order_id
                        JOIN products p ON oi.product_id = p.product_id
                        JOIN categories c ON p.category_id = c.category_id
                        WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                        AND o.status IN ('Delivered', 'Shipped')
                        GROUP BY c.category_id, c.category_name
                        ORDER BY revenue DESC";

$category_sales_result = mysqli_query($connection, $category_sales_query);

// Get sales by payment method
$payment_query = "SELECT 
                    o.payment_method,
                    COUNT(*) as order_count,
                    COALESCE(SUM(o.total_amount), 0) as revenue
                FROM orders o
                WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                AND o.status IN ('Delivered', 'Shipped')
                GROUP BY o.payment_method
                ORDER BY revenue DESC";

$payment_result = mysqli_query($connection, $payment_query);

// Get top products
$top_products_query = "SELECT 
                        p.product_id,
                        p.product_name,
                        p.sku,
                        c.category_name,
                        COALESCE(SUM(oi.quantity), 0) as quantity_sold,
                        COALESCE(SUM(oi.total_price), 0) as revenue,
                        COUNT(DISTINCT o.order_id) as order_count
                    FROM products p
                    JOIN order_items oi ON p.product_id = oi.product_id
                    JOIN orders o ON oi.order_id = o.order_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                    AND o.status IN ('Delivered', 'Shipped')
                    GROUP BY p.product_id, p.product_name, p.sku, c.category_name
                    ORDER BY revenue DESC
                    LIMIT 10";

$top_products_result = mysqli_query($connection, $top_products_query);

// Get top customers
$top_customers_query = "SELECT 
                            u.user_id,
                            CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                            u.email,
                            COUNT(DISTINCT o.order_id) as order_count,
                            COALESCE(SUM(o.total_amount), 0) as total_spent
                        FROM users u
                        JOIN orders o ON u.user_id = o.user_id
                        WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                        AND o.status IN ('Delivered', 'Shipped')
                        GROUP BY u.user_id, u.first_name, u.last_name, u.email
                        ORDER BY total_spent DESC
                        LIMIT 10";

$top_customers_result = mysqli_query($connection, $top_customers_query);

// Get hourly sales data (for heatmap)
$hourly_query = "SELECT 
                    HOUR(o.order_date) as hour,
                    COUNT(*) as order_count,
                    COALESCE(SUM(o.total_amount), 0) as revenue
                FROM orders o
                WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                AND o.status IN ('Delivered', 'Shipped')
                GROUP BY HOUR(o.order_date)
                ORDER BY hour";

$hourly_result = mysqli_query($connection, $hourly_query);
$hourly_data = [];
while ($row = mysqli_fetch_assoc($hourly_result)) {
    $hourly_data[$row['hour']] = $row;
}

// Get weekday sales data
$weekday_query = "SELECT 
                    DAYOFWEEK(o.order_date) as weekday_num,
                    DAYNAME(o.order_date) as weekday,
                    COUNT(*) as order_count,
                    COALESCE(SUM(o.total_amount), 0) as revenue
                FROM orders o
                WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                AND o.status IN ('Delivered', 'Shipped')
                GROUP BY DAYOFWEEK(o.order_date), DAYNAME(o.order_date)
                ORDER BY weekday_num";

$weekday_result = mysqli_query($connection, $weekday_query);

// Get previous period data for comparison
$prev_start = date('Y-m-d', strtotime($start_date . ' -' . (strtotime($end_date) - strtotime($start_date)) . ' seconds'));
$prev_end = date('Y-m-d', strtotime($start_date . ' -1 day'));

$comparison_query = "SELECT 
                        COUNT(DISTINCT o.order_id) as total_orders,
                        COALESCE(SUM(o.total_amount), 0) as total_revenue
                    FROM orders o
                    WHERE DATE(o.order_date) BETWEEN '$prev_start' AND '$prev_end'
                    AND o.status IN ('Delivered', 'Shipped')";

$comparison_result = mysqli_query($connection, $comparison_query);
$comparison = mysqli_fetch_assoc($comparison_result);

$prev_orders = $comparison['total_orders'] ?? 0;
$prev_revenue = $comparison['total_revenue'] ?? 0;

// Calculate growth percentages
$order_growth = $prev_orders > 0 ? round((($summary['total_orders'] - $prev_orders) / $prev_orders) * 100, 1) : 0;
$revenue_growth = $prev_revenue > 0 ? round((($summary['total_revenue'] - $prev_revenue) / $prev_revenue) * 100, 1) : 0;

// Get categories for filter
$categories_query = "SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name";
$categories_result = mysqli_query($connection, $categories_query);

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
                            <i class="fas fa-chart-line me-2"></i>
                            Sales Report
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Sales Report</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="exportReport()">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                        <button class="btn btn-success" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="filters-card">
                <form method="GET" action="" class="filters-form" id="reportForm">
                    <div class="row g-3">
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Report Type</label>
                            <select class="form-select" name="report_type">
                                <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="yearly" <?php echo $report_type == 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id">
                                <option value="0">All Categories</option>
                                <?php if ($categories_result): ?>
                                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                        <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method">
                                <option value="">All Methods</option>
                                <option value="Credit Card" <?php echo $payment_filter == 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
                                <option value="PayPal" <?php echo $payment_filter == 'PayPal' ? 'selected' : ''; ?>>PayPal</option>
                                <option value="Bank Transfer" <?php echo $payment_filter == 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="Cash on Delivery" <?php echo $payment_filter == 'Cash on Delivery' ? 'selected' : ''; ?>>Cash on Delivery</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-12">
                            <label class="form-label">&nbsp;</label>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Generate
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(30,58,95,0.1); color: var(--primary);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Orders</span>
                    <span class="stat-value"><?php echo number_format($summary['total_orders']); ?></span>
                    <?php if ($order_growth != 0): ?>
                        <span class="stat-growth <?php echo $order_growth >= 0 ? 'positive' : 'negative'; ?>">
                            <i class="fas fa-<?php echo $order_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                            <?php echo abs($order_growth); ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(25,135,84,0.1); color: var(--success);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Revenue</span>
                    <span class="stat-value">$<?php echo number_format($summary['total_revenue'], 2); ?></span>
                    <?php if ($revenue_growth != 0): ?>
                        <span class="stat-growth <?php echo $revenue_growth >= 0 ? 'positive' : 'negative'; ?>">
                            <i class="fas fa-<?php echo $revenue_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                            <?php echo abs($revenue_growth); ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(13,202,240,0.1); color: var(--info);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Avg Order Value</span>
                    <span class="stat-value">$<?php echo number_format($summary['avg_order_value'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255,193,7,0.1); color: var(--warning);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Unique Customers</span>
                    <span class="stat-value"><?php echo number_format($summary['unique_customers']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(111,66,193,0.1); color: #6f42c1;">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Items Sold</span>
                    <span class="stat-value"><?php echo number_format($summary['total_items_sold']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(108,117,125,0.1); color: var(--dark-gray);">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Shipping</span>
                    <span class="stat-value">$<?php echo number_format($summary['total_shipping'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(220,53,69,0.1); color: var(--danger);">
                    <i class="fas fa-percent"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Tax Collected</span>
                    <span class="stat-value">$<?php echo number_format($summary['total_tax'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(32,201,151,0.1); color: #20c997;">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Conversion</span>
                    <span class="stat-value">-</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="chart-card">
                <div class="chart-header">
                    <h6><i class="fas fa-chart-bar me-2"></i>Sales Overview</h6>
                    <div class="chart-actions">
                        <button class="btn btn-sm btn-light" onclick="toggleChartType()">
                            <i class="fas fa-chart-line"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-3">
            <div class="chart-card">
                <div class="chart-header">
                    <h6><i class="fas fa-chart-pie me-2"></i>Sales by Category</h6>
                </div>
                <div class="chart-body">
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Data Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <h5><i class="fas fa-list me-2"></i>
                            <?php 
                            if ($report_type == 'daily') echo 'Daily Sales';
                            elseif ($report_type == 'monthly') echo 'Monthly Sales';
                            else echo 'Yearly Sales';
                            ?>
                        </h5>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table sales-table">
                        <thead>
                            <tr>
                                <th><?php echo $report_type == 'daily' ? 'Date' : ($report_type == 'monthly' ? 'Month' : 'Year'); ?></th>
                                <th>Orders</th>
                                <th>Items Sold</th>
                                <th>Revenue</th>
                                <th>Avg Order Value</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_revenue = $summary['total_revenue'];
                            if ($sales_result && mysqli_num_rows($sales_result) > 0): 
                                while ($row = mysqli_fetch_assoc($sales_result)):
                                    $percentage = $total_revenue > 0 ? round(($row['revenue'] / $total_revenue) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="report-period">
                                        <?php 
                                        if ($report_type == 'daily') {
                                            echo date('M d, Y', strtotime($row['date']));
                                        } elseif ($report_type == 'monthly') {
                                            echo $row['month_name'];
                                        } else {
                                            echo $row['year'];
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="order-count"><?php echo number_format($row['order_count']); ?></span>
                                </td>
                                <td>
                                    <span class="items-count"><?php echo number_format($row['items_sold']); ?></span>
                                </td>
                                <td>
                                    <span class="revenue-amount">$<?php echo number_format($row['revenue'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="avg-order">$<?php echo number_format($row['avg_order'], 2); ?></span>
                                </td>
                                <td>
                                    <div class="percentage-bar">
                                        <span class="percentage-value"><?php echo $percentage; ?>%</span>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%; background: var(--primary);"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-chart-line" style="font-size: 2rem; color: var(--light-gray);"></i>
                                    <p class="mt-2 mb-0">No sales data for selected period</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Reports Row -->
    <div class="row">
        <!-- Top Products -->
        <div class="col-lg-6 mb-4">
            <div class="report-card">
                <div class="report-card-header">
                    <h6><i class="fas fa-crown me-2" style="color: #ffc107;"></i>Top Selling Products</h6>
                </div>
                <div class="report-card-body">
                    <?php if ($top_products_result && mysqli_num_rows($top_products_result) > 0): ?>
                        <table class="table report-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = mysqli_fetch_assoc($top_products_result)): ?>
                                <tr>
                                    <td>
                                        <div class="product-info-sm">
                                            <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                            <div class="product-sku"><?php echo $product['sku']; ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                    <td class="text-center"><?php echo number_format($product['quantity_sold']); ?></td>
                                    <td>$<?php echo number_format($product['revenue'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No product sales data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Top Customers -->
        <div class="col-lg-6 mb-4">
            <div class="report-card">
                <div class="report-card-header">
                    <h6><i class="fas fa-users me-2" style="color: var(--primary);"></i>Top Customers</h6>
                </div>
                <div class="report-card-body">
                    <?php if ($top_customers_result && mysqli_num_rows($top_customers_result) > 0): ?>
                        <table class="table report-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Orders</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($customer = mysqli_fetch_assoc($top_customers_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                    <td><?php echo $customer['email']; ?></td>
                                    <td class="text-center"><?php echo number_format($customer['order_count']); ?></td>
                                    <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No customer data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Payment Methods -->
        <div class="col-lg-4 mb-4">
            <div class="report-card">
                <div class="report-card-header">
                    <h6><i class="fas fa-credit-card me-2" style="color: var(--info);"></i>Payment Methods</h6>
                </div>
                <div class="report-card-body">
                    <?php if ($payment_result && mysqli_num_rows($payment_result) > 0): ?>
                        <table class="table report-table">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payment = mysqli_fetch_assoc($payment_result)): ?>
                                <tr>
                                    <td><?php echo $payment['payment_method'] ?? 'N/A'; ?></td>
                                    <td class="text-center"><?php echo number_format($payment['order_count']); ?></td>
                                    <td>$<?php echo number_format($payment['revenue'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No payment data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sales by Day of Week -->
        <div class="col-lg-4 mb-4">
            <div class="report-card">
                <div class="report-card-header">
                    <h6><i class="fas fa-calendar-alt me-2" style="color: var(--warning);"></i>Sales by Weekday</h6>
                </div>
                <div class="report-card-body">
                    <?php if ($weekday_result && mysqli_num_rows($weekday_result) > 0): ?>
                        <table class="table report-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($day = mysqli_fetch_assoc($weekday_result)): ?>
                                <tr>
                                    <td><?php echo $day['weekday']; ?></td>
                                    <td class="text-center"><?php echo number_format($day['order_count']); ?></td>
                                    <td>$<?php echo number_format($day['revenue'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No weekday data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sales by Hour -->
        <div class="col-lg-4 mb-4">
            <div class="report-card">
                <div class="report-card-header">
                    <h6><i class="fas fa-clock me-2" style="color: var(--success);"></i>Peak Hours</h6>
                </div>
                <div class="report-card-body">
                    <div class="hourly-grid">
                        <?php for ($h = 0; $h < 24; $h++): 
                            $data = $hourly_data[$h] ?? null;
                            $count = $data ? $data['order_count'] : 0;
                            $max_count = !empty($hourly_data) ? max(array_column($hourly_data, 'order_count')) : 1;
                            $height = $max_count > 0 ? ($count / $max_count) * 100 : 0;
                        ?>
                        <div class="hour-bar" title="<?php echo sprintf('%02d:00', $h); ?> - <?php echo $count; ?> orders">
                            <div class="bar" style="height: <?php echo $height; ?>%;"></div>
                            <div class="hour-label"><?php echo $h; ?></div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Prepare data for charts
<?php
// Sales chart data
$sales_labels = [];
$sales_revenue = [];
$sales_orders = [];

if ($sales_result) {
    mysqli_data_seek($sales_result, 0);
    while ($row = mysqli_fetch_assoc($sales_result)) {
        if ($report_type == 'daily') {
            $sales_labels[] = date('M d', strtotime($row['date']));
        } elseif ($report_type == 'monthly') {
            $sales_labels[] = date('M Y', strtotime($row['month'] . '-01'));
        } else {
            $sales_labels[] = $row['year'];
        }
        $sales_revenue[] = $row['revenue'];
        $sales_orders[] = $row['order_count'];
    }
}

// Category chart data
$category_labels = [];
$category_revenue = [];

if ($category_sales_result) {
    mysqli_data_seek($category_sales_result, 0);
    while ($row = mysqli_fetch_assoc($category_sales_result)) {
        $category_labels[] = $row['category_name'];
        $category_revenue[] = $row['revenue'];
    }
}
?>

// Initialize sales chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
let salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($sales_labels); ?>,
        datasets: [
            {
                label: 'Revenue ($)',
                data: <?php echo json_encode($sales_revenue); ?>,
                borderColor: '#1e3a5f',
                backgroundColor: 'rgba(30,58,95,0.1)',
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Orders',
                data: <?php echo json_encode($sales_orders); ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40,167,69,0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.dataset.label.includes('Revenue')) {
                            label += '$' + context.parsed.y.toFixed(2);
                        } else {
                            label += context.parsed.y;
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue ($)'
                },
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Orders'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});

// Initialize category chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
let categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($category_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($category_revenue); ?>,
            backgroundColor: [
                '#1e3a5f',
                '#2b4c7c',
                '#3d5a80',
                '#4e6a8f',
                '#5f7b9e',
                '#708bad',
                '#819bbd',
                '#92accc'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 15
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                    }
                }
            }
        },
        cutout: '60%'
    }
});

// Toggle chart type
function toggleChartType() {
    if (salesChart.config.type === 'line') {
        salesChart.destroy();
        salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($sales_labels); ?>,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: <?php echo json_encode($sales_revenue); ?>,
                        backgroundColor: '#1e3a5f',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: <?php echo json_encode($sales_orders); ?>,
                        backgroundColor: '#28a745',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label.includes('Revenue')) {
                                    label += '$' + context.parsed.y.toFixed(2);
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    } else {
        salesChart.destroy();
        salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($sales_labels); ?>,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: <?php echo json_encode($sales_revenue); ?>,
                        borderColor: '#1e3a5f',
                        backgroundColor: 'rgba(30,58,95,0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: <?php echo json_encode($sales_orders); ?>,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40,167,69,0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label.includes('Revenue')) {
                                    label += '$' + context.parsed.y.toFixed(2);
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
}

// Export report function
function exportReport() {
    // Collect data from the current view
    const data = [];
    
    // Add summary headers
    data.push(['Sales Report', '']);
    data.push(['Date Range', '<?php echo $start_date; ?> to <?php echo $end_date; ?>']);
    data.push(['Report Type', '<?php echo ucfirst($report_type); ?>']);
    data.push(['']);
    
    // Add summary statistics
    data.push(['SUMMARY STATISTICS']);
    data.push(['Total Orders', '<?php echo $summary['total_orders']; ?>']);
    data.push(['Total Revenue', '$<?php echo number_format($summary['total_revenue'], 2); ?>']);
    data.push(['Average Order Value', '$<?php echo number_format($summary['avg_order_value'], 2); ?>']);
    data.push(['Unique Customers', '<?php echo $summary['unique_customers']; ?>']);
    data.push(['Items Sold', '<?php echo $summary['total_items_sold']; ?>']);
    data.push(['']);
    
    // Add sales data
    data.push(['SALES DATA']);
    <?php if ($sales_result): mysqli_data_seek($sales_result, 0); ?>
    data.push(['Period', 'Orders', 'Items Sold', 'Revenue', 'Avg Order']);
    <?php while ($row = mysqli_fetch_assoc($sales_result)): ?>
    data.push([
        '<?php echo $report_type == 'daily' ? $row['date'] : ($report_type == 'monthly' ? $row['month'] : $row['year']); ?>',
        '<?php echo $row['order_count']; ?>',
        '<?php echo $row['items_sold']; ?>',
        '$<?php echo number_format($row['revenue'], 2); ?>',
        '$<?php echo number_format($row['avg_order'], 2); ?>'
    ]);
    <?php endwhile; endif; ?>
    
    // Create CSV
    let csv = data.map(row => row.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sales_report_<?php echo $start_date; ?>_to_<?php echo $end_date; ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Sales report exported successfully', 'success');
}
</script>

<style>
/* ===== SALES REPORT PAGE SPECIFIC STYLES ===== */

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
    position: relative;
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

.stat-growth {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
}

.stat-growth.positive {
    background: #d1e7dd;
    color: #0a3622;
}

.stat-growth.negative {
    background: #f8d7da;
    color: #842029;
}

/* Chart Cards */
.chart-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    height: 100%;
}

.chart-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chart-header h6 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
}

.chart-body {
    padding: 1.5rem;
    height: 300px;
}

/* Report Cards */
.report-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    height: 100%;
}

.report-card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.report-card-header h6 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
}

.report-card-body {
    padding: 1rem;
    max-height: 350px;
    overflow-y: auto;
}

/* Sales Table */
.sales-table {
    margin: 0;
}

.sales-table thead th {
    background: var(--light);
    color: var(--dark);
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
    padding: 1rem 0.75rem;
    white-space: nowrap;
}

.sales-table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--border);
    font-size: 0.95rem;
}

.sales-table tbody tr:hover {
    background: var(--light);
}

.report-period {
    font-weight: 500;
    color: var(--dark);
}

.order-count {
    font-weight: 600;
    color: var(--primary);
    display: inline-block;
    text-align: center;
}

.items-count {
    font-weight: 600;
    color: var(--info);
}

.revenue-amount {
    font-weight: 600;
    color: var(--success);
    font-family: var(--font-mono);
}

.avg-order {
    font-weight: 500;
    color: var(--dark-gray);
}

.percentage-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.percentage-value {
    min-width: 40px;
    font-size: 0.85rem;
    color: var(--dark-gray);
}

.progress {
    flex: 1;
    background: var(--light);
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    border-radius: 10px;
}

/* Report Table */
.report-table {
    margin: 0;
    font-size: 0.9rem;
}

.report-table thead th {
    background: transparent;
    color: var(--dark-gray);
    font-weight: 600;
    font-size: 0.8rem;
    border-bottom: 1px solid var(--border);
    padding: 0.5rem;
}

.report-table tbody td {
    padding: 0.5rem;
    border-bottom: 1px solid var(--border);
}

.report-table tbody tr:last-child td {
    border-bottom: none;
}

/* Product Info Small */
.product-info-sm {
    line-height: 1.3;
}

.product-name {
    font-weight: 500;
    color: var(--dark);
}

.product-sku {
    font-size: 0.75rem;
    color: var(--dark-gray);
}

/* Hourly Grid */
.hourly-grid {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    height: 200px;
    gap: 2px;
}

.hour-bar {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
    cursor: pointer;
}

.hour-bar .bar {
    width: 100%;
    background: var(--primary);
    border-radius: 3px 3px 0 0;
    transition: all 0.2s;
    min-height: 2px;
}

.hour-bar:hover .bar {
    background: var(--primary-light);
}

.hour-label {
    font-size: 0.65rem;
    color: var(--dark-gray);
    margin-top: 0.25rem;
    transform: rotate(-45deg);
    white-space: nowrap;
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
    
    .chart-body {
        height: 250px;
    }
    
    .hourly-grid {
        height: 150px;
    }
}

@media (max-width: 576px) {
    .percentage-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .percentage-value {
        min-width: auto;
    }
    
    .hour-label {
        font-size: 0.5rem;
    }
}

/* Print Styles */
@media print {
    .btn,
    .filter-actions,
    .chart-actions,
    .page-actions,
    .back-to-top {
        display: none !important;
    }
    
    .page-header-box {
        border: none;
        box-shadow: none;
    }
    
    .stat-card {
        border: 1px solid #000;
        box-shadow: none;
    }
    
    .chart-card,
    .report-card {
        break-inside: avoid;
        border: 1px solid #000;
        box-shadow: none;
    }
}
</style>