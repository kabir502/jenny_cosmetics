<?php
// admin/analytics.php - Advanced Analytics Dashboard

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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-90 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$compare_with = isset($_GET['compare_with']) ? $_GET['compare_with'] : 'previous_period';
$metric = isset($_GET['metric']) ? $_GET['metric'] : 'revenue';

// Validate dates
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Calculate previous period dates
$days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
$prev_start = date('Y-m-d', strtotime($start_date . ' -' . $days_diff . ' days'));
$prev_end = date('Y-m-d', strtotime($start_date . ' -1 day'));

// =============================================================================
// KEY METRICS WITH COMPARISON
// =============================================================================

// Current period metrics
$current_metrics_query = "SELECT 
                            COUNT(DISTINCT o.order_id) as orders,
                            COALESCE(SUM(o.total_amount), 0) as revenue,
                            COALESCE(AVG(o.total_amount), 0) as avg_order_value,
                            COUNT(DISTINCT o.user_id) as customers,
                            COALESCE(SUM(oi.quantity), 0) as items_sold,
                            COUNT(DISTINCT p.product_id) as products_sold
                        FROM orders o
                        LEFT JOIN order_items oi ON o.order_id = oi.order_id
                        LEFT JOIN products p ON oi.product_id = p.product_id
                        WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                        AND o.status IN ('Delivered', 'Shipped')";

$current_result = mysqli_query($connection, $current_metrics_query);
$current = mysqli_fetch_assoc($current_result);

// Previous period metrics
$prev_metrics_query = "SELECT 
                            COUNT(DISTINCT o.order_id) as orders,
                            COALESCE(SUM(o.total_amount), 0) as revenue,
                            COALESCE(AVG(o.total_amount), 0) as avg_order_value,
                            COUNT(DISTINCT o.user_id) as customers
                        FROM orders o
                        WHERE DATE(o.order_date) BETWEEN '$prev_start' AND '$prev_end'
                        AND o.status IN ('Delivered', 'Shipped')";

$prev_result = mysqli_query($connection, $prev_metrics_query);
$prev = mysqli_fetch_assoc($prev_result);

// Calculate growth percentages
$growth = [
    'orders' => $prev['orders'] > 0 ? round((($current['orders'] - $prev['orders']) / $prev['orders']) * 100, 1) : 0,
    'revenue' => $prev['revenue'] > 0 ? round((($current['revenue'] - $prev['revenue']) / $prev['revenue']) * 100, 1) : 0,
    'avg_order' => $prev['avg_order_value'] > 0 ? round((($current['avg_order_value'] - $prev['avg_order_value']) / $prev['avg_order_value']) * 100, 1) : 0,
    'customers' => $prev['customers'] > 0 ? round((($current['customers'] - $prev['customers']) / $prev['customers']) * 100, 1) : 0
];

// =============================================================================
// CUSTOMER ANALYTICS
// =============================================================================

// New vs Returning customers
$customer_type_query = "SELECT 
                            CASE 
                                WHEN u.registration_date >= '$start_date' THEN 'New'
                                ELSE 'Returning'
                            END as customer_type,
                            COUNT(DISTINCT o.user_id) as customer_count,
                            COUNT(DISTINCT o.order_id) as order_count,
                            COALESCE(SUM(o.total_amount), 0) as revenue
                        FROM orders o
                        JOIN users u ON o.user_id = u.user_id
                        WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                        AND o.status IN ('Delivered', 'Shipped')
                        GROUP BY customer_type";

$customer_type_result = mysqli_query($connection, $customer_type_query);

// Customer lifetime value
$clv_query = "SELECT 
                    u.user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                    COUNT(o.order_id) as order_count,
                    COALESCE(SUM(o.total_amount), 0) as total_spent,
                    DATEDIFF(MAX(o.order_date), MIN(o.order_date)) as customer_lifetime_days,
                    COALESCE(AVG(o.total_amount), 0) as avg_order_value
                FROM users u
                JOIN orders o ON u.user_id = o.user_id
                WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                AND o.status IN ('Delivered', 'Shipped')
                GROUP BY u.user_id, u.first_name, u.last_name
                HAVING total_spent > 0
                ORDER BY total_spent DESC
                LIMIT 10";

$clv_result = mysqli_query($connection, $clv_query);

// Customer acquisition by month
$acquisition_query = "SELECT 
                        DATE_FORMAT(registration_date, '%Y-%m') as month,
                        DATE_FORMAT(registration_date, '%M %Y') as month_name,
                        COUNT(*) as new_customers,
                        COALESCE(SUM(total_spent), 0) as lifetime_value
                    FROM users
                    WHERE registration_date BETWEEN '$start_date' AND '$end_date'
                    GROUP BY DATE_FORMAT(registration_date, '%Y-%m')
                    ORDER BY month DESC";

$acquisition_result = mysqli_query($connection, $acquisition_query);

// =============================================================================
// PRODUCT ANALYTICS
// =============================================================================

// Product performance by category
$category_performance_query = "SELECT 
                                    c.category_name,
                                    COUNT(DISTINCT p.product_id) as products_in_category,
                                    COUNT(DISTINCT oi.order_item_id) as times_sold,
                                    COALESCE(SUM(oi.quantity), 0) as quantity_sold,
                                    COALESCE(SUM(oi.total_price), 0) as revenue,
                                    COALESCE(AVG(p.unit_price), 0) as avg_price
                                FROM categories c
                                LEFT JOIN products p ON c.category_id = p.category_id
                                LEFT JOIN order_items oi ON p.product_id = oi.product_id
                                LEFT JOIN orders o ON oi.order_id = o.order_id 
                                    AND DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                                    AND o.status IN ('Delivered', 'Shipped')
                                GROUP BY c.category_id, c.category_name
                                HAVING products_in_category > 0
                                ORDER BY revenue DESC";

$category_performance_result = mysqli_query($connection, $category_performance_query);

// Inventory turnover rate
$turnover_query = "SELECT 
                        p.product_id,
                        p.product_name,
                        p.sku,
                        p.quantity_in_stock,
                        COALESCE(SUM(oi.quantity), 0) as sold_in_period,
                        CASE 
                            WHEN p.quantity_in_stock > 0 
                            THEN ROUND(COALESCE(SUM(oi.quantity), 0) / p.quantity_in_stock, 2)
                            ELSE 0 
                        END as turnover_rate
                    FROM products p
                    LEFT JOIN order_items oi ON p.product_id = oi.product_id
                    LEFT JOIN orders o ON oi.order_id = o.order_id 
                        AND DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                        AND o.status IN ('Delivered', 'Shipped')
                    GROUP BY p.product_id, p.product_name, p.sku, p.quantity_in_stock
                    HAVING sold_in_period > 0
                    ORDER BY turnover_rate DESC
                    LIMIT 15";

$turnover_result = mysqli_query($connection, $turnover_query);

// Price range analysis
$price_ranges = [
    '0-25' => [0, 25],
    '25-50' => [25, 50],
    '50-100' => [50, 100],
    '100-200' => [100, 200],
    '200+' => [200, 999999]
];

$price_range_data = [];
foreach ($price_ranges as $range_name => $range) {
    $query = "SELECT 
                    COUNT(DISTINCT oi.order_item_id) as sales_count,
                    COALESCE(SUM(oi.quantity), 0) as quantity_sold,
                    COALESCE(SUM(oi.total_price), 0) as revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE p.unit_price BETWEEN {$range[0]} AND {$range[1]}
                AND DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                AND o.status IN ('Delivered', 'Shipped')";
    
    $result = mysqli_query($connection, $query);
    $price_range_data[$range_name] = mysqli_fetch_assoc($result);
}

// =============================================================================
// TIME-BASED ANALYTICS
// =============================================================================

// Daily sales trend
$daily_trend_query = "SELECT 
                        DATE(o.order_date) as date,
                        COUNT(DISTINCT o.order_id) as orders,
                        COALESCE(SUM(o.total_amount), 0) as revenue,
                        COUNT(DISTINCT o.user_id) as unique_customers
                    FROM orders o
                    WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                    AND o.status IN ('Delivered', 'Shipped')
                    GROUP BY DATE(o.order_date)
                    ORDER BY date";

$daily_trend_result = mysqli_query($connection, $daily_trend_query);

// Hourly distribution
$hourly_distribution_query = "SELECT 
                                HOUR(o.order_date) as hour,
                                COUNT(*) as order_count,
                                COALESCE(SUM(o.total_amount), 0) as revenue,
                                COALESCE(AVG(o.total_amount), 0) as avg_order
                            FROM orders o
                            WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                            AND o.status IN ('Delivered', 'Shipped')
                            GROUP BY HOUR(o.order_date)
                            ORDER BY hour";

$hourly_distribution_result = mysqli_query($connection, $hourly_distribution_query);

// Day of week analysis
$dow_query = "SELECT 
                DAYOFWEEK(o.order_date) as day_num,
                DAYNAME(o.order_date) as day_name,
                COUNT(*) as order_count,
                COALESCE(SUM(o.total_amount), 0) as revenue,
                COALESCE(AVG(o.total_amount), 0) as avg_order
            FROM orders o
            WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
            AND o.status IN ('Delivered', 'Shipped')
            GROUP BY DAYOFWEEK(o.order_date), DAYNAME(o.order_date)
            ORDER BY day_num";

$dow_result = mysqli_query($connection, $dow_query);

// Monthly trends (for longer periods)
$monthly_trend_query = "SELECT 
                            DATE_FORMAT(o.order_date, '%Y-%m') as month,
                            DATE_FORMAT(o.order_date, '%M %Y') as month_name,
                            COUNT(DISTINCT o.order_id) as orders,
                            COALESCE(SUM(o.total_amount), 0) as revenue,
                            COUNT(DISTINCT o.user_id) as customers
                        FROM orders o
                        WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                        AND o.status IN ('Delivered', 'Shipped')
                        GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
                        ORDER BY month";

$monthly_trend_result = mysqli_query($connection, $monthly_trend_query);

// =============================================================================
// FINANCIAL ANALYTICS
// =============================================================================

// Payment method breakdown
$payment_breakdown_query = "SELECT 
                                o.payment_method,
                                COUNT(*) as transaction_count,
                                COALESCE(SUM(o.total_amount), 0) as total_amount,
                                COALESCE(AVG(o.total_amount), 0) as avg_amount,
                                (COALESCE(SUM(o.total_amount), 0) / {$current['revenue']}) * 100 as percentage
                            FROM orders o
                            WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                            AND o.status IN ('Delivered', 'Shipped')
                            GROUP BY o.payment_method
                            ORDER BY total_amount DESC";

$payment_breakdown_result = mysqli_query($connection, $payment_breakdown_query);

// Revenue by shipping method
$shipping_analysis_query = "SELECT 
                                o.shipping_method,
                                COUNT(*) as order_count,
                                COALESCE(SUM(o.shipping_amount), 0) as shipping_revenue,
                                COALESCE(AVG(o.shipping_amount), 0) as avg_shipping
                            FROM orders o
                            WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                            AND o.status IN ('Delivered', 'Shipped')
                            GROUP BY o.shipping_method
                            ORDER BY shipping_revenue DESC";

$shipping_analysis_result = mysqli_query($connection, $shipping_analysis_query);

// Tax collected
$tax_query = "SELECT 
                COALESCE(SUM(o.tax_amount), 0) as total_tax,
                COALESCE(AVG(o.tax_amount), 0) as avg_tax,
                (COALESCE(SUM(o.tax_amount), 0) / {$current['revenue']}) * 100 as tax_percentage
            FROM orders o
            WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
            AND o.status IN ('Delivered', 'Shipped')";

$tax_result = mysqli_query($connection, $tax_query);
$tax_data = mysqli_fetch_assoc($tax_result);

// =============================================================================
// ADVANCED METRICS
// =============================================================================

// Conversion rate (orders vs unique visitors - approximated)
$conversion_query = "SELECT 
                        COUNT(DISTINCT o.user_id) as buying_customers,
                        (SELECT COUNT(*) FROM users WHERE registration_date <= '$end_date') as total_customers,
                        COUNT(DISTINCT o.order_id) as total_orders
                    FROM orders o
                    WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                    AND o.status IN ('Delivered', 'Shipped')";

$conversion_result = mysqli_query($connection, $conversion_query);
$conversion_data = mysqli_fetch_assoc($conversion_result);
$conversion_rate = $conversion_data['total_customers'] > 0 
    ? round(($conversion_data['buying_customers'] / $conversion_data['total_customers']) * 100, 2) 
    : 0;

// Repeat purchase rate
$repeat_query = "SELECT 
                    COUNT(DISTINCT user_id) as customers_with_orders,
                    SUM(CASE WHEN customer_orders.order_count > 1 THEN 1 ELSE 0 END) as repeat_customers
                FROM (
                    SELECT 
                        o.user_id,
                        COUNT(*) as order_count
                    FROM orders o
                    WHERE DATE(order_date) BETWEEN '$start_date' AND '$end_date'
                    AND status IN ('Delivered', 'Shipped')
                    GROUP BY o.user_id
                ) as customer_orders";

$repeat_result = mysqli_query($connection, $repeat_query);
$repeat_data = mysqli_fetch_assoc($repeat_result);
$repeat_rate = $repeat_data['customers_with_orders'] > 0 
    ? round(($repeat_data['repeat_customers'] / $repeat_data['customers_with_orders']) * 100, 2) 
    : 0;

// Average order size (items per order)
$avg_items_query = "SELECT 
                        AVG(item_count) as avg_items_per_order
                    FROM (
                        SELECT 
                            o.order_id,
                            COALESCE(SUM(oi.quantity), 0) as item_count
                        FROM orders o
                        LEFT JOIN order_items oi ON o.order_id = oi.order_id
                        WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                        AND o.status IN ('Delivered', 'Shipped')
                        GROUP BY o.order_id
                    ) as order_items";

$avg_items_result = mysqli_query($connection, $avg_items_query);
$avg_items_data = mysqli_fetch_assoc($avg_items_result);

// =============================================================================
// GEOGRAPHIC ANALYTICS (if address data is available)
// =============================================================================

$top_cities_query = "SELECT 
                        u.address_city as city,
                        u.address_country as country,
                        COUNT(DISTINCT o.order_id) as order_count,
                        COUNT(DISTINCT u.user_id) as customer_count,
                        COALESCE(SUM(o.total_amount), 0) as revenue
                    FROM users u
                    JOIN orders o ON u.user_id = o.user_id
                    WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
                    AND o.status IN ('Delivered', 'Shipped')
                    AND u.address_city IS NOT NULL 
                    AND u.address_city != ''
                    GROUP BY u.address_city, u.address_country
                    ORDER BY revenue DESC
                    LIMIT 10";

$top_cities_result = mysqli_query($connection, $top_cities_query);

// =============================================================================
// PREPARE CHART DATA
// =============================================================================

// Daily trend data for charts
$daily_labels = [];
$daily_orders = [];
$daily_revenue = [];

if ($daily_trend_result) {
    while ($row = mysqli_fetch_assoc($daily_trend_result)) {
        $daily_labels[] = date('M d', strtotime($row['date']));
        $daily_orders[] = $row['orders'];
        $daily_revenue[] = $row['revenue'];
    }
}

// Hourly distribution data
$hourly_labels = [];
$hourly_orders = [];
$hourly_revenue = [];

for ($h = 0; $h < 24; $h++) {
    $hourly_labels[] = sprintf('%02d:00', $h);
    $hourly_orders[$h] = 0;
    $hourly_revenue[$h] = 0;
}

if ($hourly_distribution_result) {
    mysqli_data_seek($hourly_distribution_result, 0);
    while ($row = mysqli_fetch_assoc($hourly_distribution_result)) {
        $hour = (int)$row['hour'];
        $hourly_orders[$hour] = (int)$row['order_count'];
        $hourly_revenue[$hour] = (float)$row['revenue'];
    }
}

// Day of week data
$dow_labels = [];
$dow_orders = [];
$dow_revenue = [];
$dow_order = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

if ($dow_result) {
    mysqli_data_seek($dow_result, 0);
    while ($row = mysqli_fetch_assoc($dow_result)) {
        $dow_labels[] = substr($row['day_name'], 0, 3);
        $dow_orders[] = $row['order_count'];
        $dow_revenue[] = $row['revenue'];
    }
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
                            <i class="fas fa-chart-pie me-2"></i>
                            Advanced Analytics
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="sales_report.php">Sales Report</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Analytics</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="exportAnalytics()">
                            <i class="fas fa-download me-2"></i>Export Analytics
                        </button>
                        <button class="btn btn-success" onclick="refreshData()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
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
                <form method="GET" action="" class="filters-form" id="analyticsForm">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Compare With</label>
                            <select class="form-select" name="compare_with">
                                <option value="previous_period" <?php echo $compare_with == 'previous_period' ? 'selected' : ''; ?>>Previous Period</option>
                                <option value="previous_year" <?php echo $compare_with == 'previous_year' ? 'selected' : ''; ?>>Previous Year</option>
                                <option value="none" <?php echo $compare_with == 'none' ? 'selected' : ''; ?>>No Comparison</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-3 col-12">
                            <label class="form-label">&nbsp;</label>
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-chart-line me-2"></i>Analyze
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- KPI Cards with Growth Indicators -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="kpi-card">
                <div class="kpi-icon revenue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Total Revenue</span>
                    <span class="kpi-value">$<?php echo number_format($current['revenue'], 2); ?></span>
                    <span class="kpi-trend <?php echo $growth['revenue'] >= 0 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="fas fa-<?php echo $growth['revenue'] >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo abs($growth['revenue']); ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="kpi-card">
                <div class="kpi-icon orders">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Total Orders</span>
                    <span class="kpi-value"><?php echo number_format($current['orders']); ?></span>
                    <span class="kpi-trend <?php echo $growth['orders'] >= 0 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="fas fa-<?php echo $growth['orders'] >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo abs($growth['orders']); ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="kpi-card">
                <div class="kpi-icon customers">
                    <i class="fas fa-users"></i>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Customers</span>
                    <span class="kpi-value"><?php echo number_format($current['customers']); ?></span>
                    <span class="kpi-trend <?php echo $growth['customers'] >= 0 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="fas fa-<?php echo $growth['customers'] >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo abs($growth['customers']); ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="kpi-card">
                <div class="kpi-icon avg-order">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="kpi-content">
                    <span class="kpi-label">Avg Order Value</span>
                    <span class="kpi-value">$<?php echo number_format($current['avg_order_value'], 2); ?></span>
                    <span class="kpi-trend <?php echo $growth['avg_order'] >= 0 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="fas fa-<?php echo $growth['avg_order'] >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo abs($growth['avg_order']); ?>%
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="chart-card">
                <div class="chart-header">
                    <h6><i class="fas fa-chart-line me-2"></i>Daily Sales Trend</h6>
                    <div class="chart-actions">
                        <button class="btn btn-sm btn-light" onclick="toggleDailyChart()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-body">
                    <canvas id="dailyTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-3">
            <div class="chart-card">
                <div class="chart-header">
                    <h6><i class="fas fa-chart-pie me-2"></i>New vs Returning</h6>
                </div>
                <div class="chart-body">
                    <canvas id="customerTypeChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Analysis Row -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-3">
            <div class="chart-card">
                <div class="chart-header">
                    <h6><i class="fas fa-clock me-2"></i>Hourly Distribution</h6>
                </div>
                <div class="chart-body">
                    <canvas id="hourlyChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-3">
            <div class="chart-card">
                <div class="chart-header">
                    <h6><i class="fas fa-calendar-alt me-2"></i>Day of Week Analysis</h6>
                </div>
                <div class="chart-body">
                    <canvas id="dowChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Analytics Row -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-3">
            <div class="analytics-card">
                <div class="analytics-header">
                    <h6><i class="fas fa-crown me-2" style="color: #ffc107;"></i>Top Customers by LTV</h6>
                </div>
                <div class="analytics-body">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Orders</th>
                                <th>Avg Order</th>
                                <th>Total Spent</th>
                                <th>LTV</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($clv_result && mysqli_num_rows($clv_result) > 0): ?>
                                <?php while ($customer = mysqli_fetch_assoc($clv_result)): 
                                    $ltv_per_day = $customer['customer_lifetime_days'] > 0 
                                        ? $customer['total_spent'] / $customer['customer_lifetime_days'] 
                                        : $customer['total_spent'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                    <td class="text-center"><?php echo $customer['order_count']; ?></td>
                                    <td>$<?php echo number_format($customer['avg_order_value'], 2); ?></td>
                                    <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                    <td>$<?php echo number_format($ltv_per_day, 2); ?>/day</td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No customer data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-3">
            <div class="analytics-card">
                <div class="analytics-header">
                    <h6><i class="fas fa-calendar-check me-2" style="color: var(--info);"></i>Customer Acquisition</h6>
                </div>
                <div class="analytics-body">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>New Customers</th>
                                <th>Lifetime Value</th>
                                <th>Avg per Customer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($acquisition_result && mysqli_num_rows($acquisition_result) > 0): ?>
                                <?php while ($acq = mysqli_fetch_assoc($acquisition_result)): 
                                    $avg_per_customer = $acq['new_customers'] > 0 
                                        ? $acq['lifetime_value'] / $acq['new_customers'] 
                                        : 0;
                                ?>
                                <tr>
                                    <td><?php echo $acq['month_name']; ?></td>
                                    <td class="text-center"><?php echo $acq['new_customers']; ?></td>
                                    <td>$<?php echo number_format($acq['lifetime_value'], 2); ?></td>
                                    <td>$<?php echo number_format($avg_per_customer, 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">No acquisition data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Analytics Row -->
    <div class="row mb-4">
        <div class="col-lg-7 mb-3">
            <div class="analytics-card">
                <div class="analytics-header">
                    <h6><i class="fas fa-box me-2" style="color: var(--success);"></i>Category Performance</h6>
                </div>
                <div class="analytics-body">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Products</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_revenue = $current['revenue'];
                            if ($category_performance_result && mysqli_num_rows($category_performance_result) > 0): 
                                while ($cat = mysqli_fetch_assoc($category_performance_result)):
                                    $percentage = $total_revenue > 0 ? round(($cat['revenue'] / $total_revenue) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                <td class="text-center"><?php echo $cat['products_in_category']; ?></td>
                                <td class="text-center"><?php echo $cat['quantity_sold']; ?></td>
                                <td>$<?php echo number_format($cat['revenue'], 2); ?></td>
                                <td>
                                    <div class="percentage-bar">
                                        <span class="percentage-value"><?php echo $percentage; ?>%</span>
                                        <div class="progress" style="height: 6px; width: 60px;">
                                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-3">No category data</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5 mb-3">
            <div class="analytics-card">
                <div class="analytics-header">
                    <h6><i class="fas fa-tachometer-alt me-2" style="color: var(--warning);"></i>Inventory Turnover</h6>
                </div>
                <div class="analytics-body">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Sold</th>
                                <th>In Stock</th>
                                <th>Turnover</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($turnover_result && mysqli_num_rows($turnover_result) > 0): ?>
                                <?php while ($item = mysqli_fetch_assoc($turnover_result)): ?>
                                <tr>
                                    <td>
                                        <div class="product-info-sm">
                                            <div class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                            <div class="product-sku"><?php echo $item['sku']; ?></div>
                                        </div>
                                    </td>
                                    <td class="text-center"><?php echo $item['sold_in_period']; ?></td>
                                    <td class="text-center"><?php echo $item['quantity_in_stock']; ?></td>
                                    <td class="text-center">
                                        <span class="turnover-badge rate-<?php 
                                            echo $item['turnover_rate'] >= 2 ? 'high' : ($item['turnover_rate'] >= 1 ? 'medium' : 'low'); 
                                        ?>">
                                            <?php echo $item['turnover_rate']; ?>x
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">No turnover data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Analytics Row -->
    <div class="row mb-4">
        <div class="col-lg-4 mb-3">
            <div class="metrics-card">
                <div class="metrics-header">
                    <h6><i class="fas fa-credit-card me-2"></i>Payment Methods</h6>
                </div>
                <div class="metrics-body">
                    <?php if ($payment_breakdown_result && mysqli_num_rows($payment_breakdown_result) > 0): ?>
                        <?php while ($payment = mysqli_fetch_assoc($payment_breakdown_result)): ?>
                        <div class="metric-item">
                            <div class="metric-label">
                                <span><?php echo $payment['payment_method'] ?? 'N/A'; ?></span>
                                <span class="metric-value">$<?php echo number_format($payment['total_amount'], 2); ?></span>
                            </div>
                            <div class="metric-bar">
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo $payment['percentage']; ?>%;"></div>
                                </div>
                                <span class="metric-percentage"><?php echo round($payment['percentage'], 1); ?>%</span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No payment data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-3">
            <div class="metrics-card">
                <div class="metrics-header">
                    <h6><i class="fas fa-chart-bar me-2"></i>Price Range Analysis</h6>
                </div>
                <div class="metrics-body">
                    <?php foreach ($price_range_data as $range => $data): ?>
                        <?php if ($data): ?>
                        <div class="metric-item">
                            <div class="metric-label">
                                <span>$<?php echo $range; ?></span>
                                <span class="metric-value"><?php echo $data['quantity_sold'] ?? 0; ?> units</span>
                            </div>
                            <div class="metric-bar">
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo ($data['revenue'] / $total_revenue) * 100; ?>%;"></div>
                                </div>
                                <span class="metric-percentage">$<?php echo number_format($data['revenue'] ?? 0, 0); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-3">
            <div class="metrics-card">
                <div class="metrics-header">
                    <h6><i class="fas fa-globe me-2"></i>Top Cities</h6>
                </div>
                <div class="metrics-body">
                    <?php if ($top_cities_result && mysqli_num_rows($top_cities_result) > 0): ?>
                        <?php while ($city = mysqli_fetch_assoc($top_cities_result)): ?>
                        <div class="metric-item">
                            <div class="metric-label">
                                <span><?php echo htmlspecialchars($city['city']); ?>, <?php echo $city['country']; ?></span>
                                <span class="metric-value"><?php echo $city['order_count']; ?> orders</span>
                            </div>
                            <div class="metric-bar">
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo ($city['revenue'] / $total_revenue) * 100; ?>%;"></div>
                                </div>
                                <span class="metric-percentage">$<?php echo number_format($city['revenue'], 0); ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No geographic data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Metrics Row -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="advanced-metric-card">
                <div class="metric-icon conversion">
                    <i class="fas fa-percent"></i>
                </div>
                <div class="metric-details">
                    <span class="metric-title">Conversion Rate</span>
                    <span class="metric-number"><?php echo $conversion_rate; ?>%</span>
                    <span class="metric-sub"><?php echo $conversion_data['buying_customers']; ?> of <?php echo $conversion_data['total_customers']; ?> customers</span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="advanced-metric-card">
                <div class="metric-icon repeat">
                    <i class="fas fa-redo-alt"></i>
                </div>
                <div class="metric-details">
                    <span class="metric-title">Repeat Purchase Rate</span>
                    <span class="metric-number"><?php echo $repeat_rate; ?>%</span>
                    <span class="metric-sub"><?php echo $repeat_data['repeat_customers']; ?> returning customers</span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="advanced-metric-card">
                <div class="metric-icon items">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="metric-details">
                    <span class="metric-title">Avg Items/Order</span>
                    <span class="metric-number"><?php echo round($avg_items_data['avg_items_per_order'] ?? 0, 1); ?></span>
                    <span class="metric-sub"><?php echo $current['items_sold']; ?> total items</span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="advanced-metric-card">
                <div class="metric-icon tax">
                    <i class="fas fa-percent"></i>
                </div>
                <div class="metric-details">
                    <span class="metric-title">Tax Collected</span>
                    <span class="metric-number">$<?php echo number_format($tax_data['total_tax'] ?? 0, 2); ?></span>
                    <span class="metric-sub"><?php echo round($tax_data['tax_percentage'] ?? 0, 1); ?>% of revenue</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends (if range > 60 days) -->
    <?php if ($days_diff > 60): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-header">
                    <h6><i class="fas fa-chart-bar me-2"></i>Monthly Trends</h6>
                </div>
                <div class="chart-body">
                    <canvas id="monthlyTrendChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Prepare data for charts
const dailyLabels = <?php echo json_encode($daily_labels); ?>;
const dailyOrders = <?php echo json_encode($daily_orders); ?>;
const dailyRevenue = <?php echo json_encode($daily_revenue); ?>;

const hourlyLabels = <?php echo json_encode($hourly_labels); ?>;
const hourlyOrders = <?php echo json_encode(array_values($hourly_orders)); ?>;
const hourlyRevenue = <?php echo json_encode(array_values($hourly_revenue)); ?>;

const dowLabels = <?php echo json_encode($dow_labels); ?>;
const dowOrders = <?php echo json_encode($dow_orders); ?>;
const dowRevenue = <?php echo json_encode($dow_revenue); ?>;

// Customer type data
const customerTypeData = {
    labels: [],
    counts: [],
    revenue: []
};

<?php
if ($customer_type_result) {
    mysqli_data_seek($customer_type_result, 0);
    while ($row = mysqli_fetch_assoc($customer_type_result)) {
        echo "customerTypeData.labels.push('" . $row['customer_type'] . "');\n";
        echo "customerTypeData.counts.push(" . $row['customer_count'] . ");\n";
        echo "customerTypeData.revenue.push(" . $row['revenue'] . ");\n";
    }
}
?>

// Monthly trend data
<?php
$monthly_labels = [];
$monthly_orders = [];
$monthly_revenue = [];

if ($monthly_trend_result) {
    mysqli_data_seek($monthly_trend_result, 0);
    while ($row = mysqli_fetch_assoc($monthly_trend_result)) {
        $monthly_labels[] = $row['month_name'];
        $monthly_orders[] = $row['orders'];
        $monthly_revenue[] = $row['revenue'];
    }
}
?>

// Initialize Daily Trend Chart
const dailyCtx = document.getElementById('dailyTrendChart').getContext('2d');
let dailyChart = new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [
            {
                label: 'Revenue ($)',
                data: dailyRevenue,
                borderColor: '#1e3a5f',
                backgroundColor: 'rgba(30,58,95,0.1)',
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Orders',
                data: dailyOrders,
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

// Initialize Customer Type Chart
const customerCtx = document.getElementById('customerTypeChart').getContext('2d');
new Chart(customerCtx, {
    type: 'doughnut',
    data: {
        labels: customerTypeData.labels,
        datasets: [{
            data: customerTypeData.revenue,
            backgroundColor: ['#1e3a5f', '#28a745'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
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

// Initialize Hourly Chart
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: hourlyLabels,
        datasets: [
            {
                label: 'Orders',
                data: hourlyOrders,
                backgroundColor: '#1e3a5f',
                yAxisID: 'y'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Orders: ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Orders'
                }
            }
        }
    }
});

// Initialize Day of Week Chart
const dowCtx = document.getElementById('dowChart').getContext('2d');
new Chart(dowCtx, {
    type: 'bar',
    data: {
        labels: dowLabels,
        datasets: [
            {
                label: 'Revenue ($)',
                data: dowRevenue,
                backgroundColor: '#28a745',
                yAxisID: 'y'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Revenue: $' + context.parsed.y.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Revenue ($)'
                },
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            }
        }
    }
});

// Initialize Monthly Trend Chart if it exists
<?php if ($days_diff > 60): ?>
const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($monthly_labels); ?>,
        datasets: [
            {
                label: 'Revenue ($)',
                data: <?php echo json_encode($monthly_revenue); ?>,
                borderColor: '#1e3a5f',
                backgroundColor: 'rgba(30,58,95,0.1)',
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Orders',
                data: <?php echo json_encode($monthly_orders); ?>,
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
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
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
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
<?php endif; ?>

// Toggle daily chart type
function toggleDailyChart() {
    if (dailyChart.config.type === 'line') {
        dailyChart.destroy();
        dailyChart = new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: dailyLabels,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: dailyRevenue,
                        backgroundColor: '#1e3a5f',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: dailyOrders,
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
        dailyChart.destroy();
        dailyChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: dailyRevenue,
                        borderColor: '#1e3a5f',
                        backgroundColor: 'rgba(30,58,95,0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: dailyOrders,
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

// Export analytics function
function exportAnalytics() {
    // Collect data from the current view
    const data = [];
    
    // Add headers
    data.push(['Advanced Analytics Report', '']);
    data.push(['Date Range', '<?php echo $start_date; ?> to <?php echo $end_date; ?>']);
    data.push(['Generated', new Date().toLocaleString()]);
    data.push(['']);
    
    // Add summary metrics
    data.push(['KEY METRICS']);
    data.push(['Total Revenue', '$<?php echo number_format($current['revenue'], 2); ?>']);
    data.push(['Total Orders', '<?php echo $current['orders']; ?>']);
    data.push(['Unique Customers', '<?php echo $current['customers']; ?>']);
    data.push(['Avg Order Value', '$<?php echo number_format($current['avg_order_value'], 2); ?>']);
    data.push(['Items Sold', '<?php echo $current['items_sold']; ?>']);
    data.push(['']);
    
    // Add advanced metrics
    data.push(['ADVANCED METRICS']);
    data.push(['Conversion Rate', '<?php echo $conversion_rate; ?>%']);
    data.push(['Repeat Purchase Rate', '<?php echo $repeat_rate; ?>%']);
    data.push(['Avg Items per Order', '<?php echo round($avg_items_data['avg_items_per_order'] ?? 0, 1); ?>']);
    data.push(['Tax Collected', '$<?php echo number_format($tax_data['total_tax'] ?? 0, 2); ?>']);
    
    // Create CSV
    let csv = data.map(row => row.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'analytics_report_<?php echo $start_date; ?>_to_<?php echo $end_date; ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Analytics report exported successfully', 'success');
}

// Refresh data
function refreshData() {
    location.reload();
}
</script>

<style>
/* ===== ANALYTICS PAGE SPECIFIC STYLES ===== */

/* KPI Cards */
.kpi-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
    height: 100%;
    position: relative;
    overflow: hidden;
}

.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.kpi-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.kpi-icon.revenue {
    background: rgba(30,58,95,0.1);
    color: var(--primary);
}

.kpi-icon.orders {
    background: rgba(40,167,69,0.1);
    color: var(--success);
}

.kpi-icon.customers {
    background: rgba(255,193,7,0.1);
    color: var(--warning);
}

.kpi-icon.avg-order {
    background: rgba(13,202,240,0.1);
    color: var(--info);
}

.kpi-content {
    flex: 1;
}

.kpi-label {
    display: block;
    font-size: 0.85rem;
    color: var(--dark-gray);
    margin-bottom: 0.25rem;
}

.kpi-value {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.kpi-trend {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.kpi-trend.trend-up {
    background: #d1e7dd;
    color: #0a3622;
}

.kpi-trend.trend-down {
    background: #f8d7da;
    color: #842029;
}

/* Analytics Cards */
.analytics-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    height: 100%;
}

.analytics-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.analytics-header h6 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
}

.analytics-body {
    padding: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

/* Analytics Table */
.analytics-table {
    width: 100%;
    font-size: 0.9rem;
}

.analytics-table thead th {
    background: transparent;
    color: var(--dark-gray);
    font-weight: 600;
    font-size: 0.8rem;
    border-bottom: 1px solid var(--border);
    padding: 0.5rem;
    text-align: left;
}

.analytics-table tbody td {
    padding: 0.5rem;
    border-bottom: 1px solid var(--border);
}

.analytics-table tbody tr:last-child td {
    border-bottom: none;
}

.analytics-table tbody tr:hover {
    background: var(--light);
}

/* Metrics Cards */
.metrics-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    height: 100%;
}

.metrics-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.metrics-header h6 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
}

.metrics-body {
    padding: 1.5rem;
}

.metric-item {
    margin-bottom: 1rem;
}

.metric-item:last-child {
    margin-bottom: 0;
}

.metric-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.metric-value {
    font-weight: 600;
    color: var(--primary);
}

.metric-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.metric-bar .progress {
    flex: 1;
    height: 8px;
    background: var(--light);
    border-radius: 4px;
    overflow: hidden;
}

.metric-bar .progress-bar {
    height: 100%;
    background: var(--primary);
    border-radius: 4px;
}

.metric-percentage {
    min-width: 50px;
    font-size: 0.85rem;
    color: var(--dark-gray);
    text-align: right;
}

/* Advanced Metric Cards */
.advanced-metric-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
    height: 100%;
}

.advanced-metric-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.metric-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.metric-icon.conversion {
    background: rgba(111,66,193,0.1);
    color: #6f42c1;
}

.metric-icon.repeat {
    background: rgba(255,193,7,0.1);
    color: var(--warning);
}

.metric-icon.items {
    background: rgba(40,167,69,0.1);
    color: var(--success);
}

.metric-icon.tax {
    background: rgba(220,53,69,0.1);
    color: var(--danger);
}

.metric-details {
    flex: 1;
}

.metric-title {
    display: block;
    font-size: 0.85rem;
    color: var(--dark-gray);
    margin-bottom: 0.25rem;
}

.metric-number {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.metric-sub {
    font-size: 0.8rem;
    color: var(--dark-gray);
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

/* Turnover Badge */
.turnover-badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.turnover-badge.rate-high {
    background: #d1e7dd;
    color: #0a3622;
}

.turnover-badge.rate-medium {
    background: #fff3cd;
    color: #856404;
}

.turnover-badge.rate-low {
    background: #f8d7da;
    color: #842029;
}

/* Percentage Bar */
.percentage-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.percentage-value {
    font-size: 0.8rem;
    color: var(--dark-gray);
    min-width: 35px;
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

.chart-actions {
    display: flex;
    gap: 0.5rem;
}

.chart-body {
    padding: 1.5rem;
    height: 300px;
}

/* Responsive */
@media (max-width: 768px) {
    .kpi-card {
        padding: 1rem;
    }
    
    .kpi-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .kpi-value {
        font-size: 1.4rem;
    }
    
    .chart-body {
        height: 250px;
    }
    
    .advanced-metric-card {
        padding: 1rem;
    }
    
    .metric-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .metric-number {
        font-size: 1.4rem;
    }
}

@media (max-width: 576px) {
    .kpi-card {
        flex-direction: column;
        text-align: center;
    }
    
    .metric-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .metric-bar {
        width: 100%;
    }
    
    .percentage-bar {
        flex-direction: column;
        align-items: flex-start;
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
    
    .kpi-card,
    .advanced-metric-card {
        border: 1px solid #000;
        box-shadow: none;
        page-break-inside: avoid;
    }
    
    .chart-card,
    .analytics-card,
    .metrics-card {
        break-inside: avoid;
        border: 1px solid #000;
        box-shadow: none;
    }
}
</style>