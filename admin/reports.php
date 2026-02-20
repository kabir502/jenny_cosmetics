<?php
// admin/reports.php - Sales Reports Page

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
$error = '';
$message = '';

// Get date range from GET or default to last 30 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validate dates
if (strtotime($start_date) > strtotime($end_date)) {
    $error = "Start date cannot be after end date.";
    // Swap dates
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Top selling products report with error handling
$top_products = [];
$top_products_query = "
    SELECT p.product_id, p.product_name, p.sku, 
           COALESCE(SUM(oi.quantity), 0) as total_quantity, 
           COALESCE(SUM(oi.total_price), 0) as total_revenue
    FROM products p
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id 
        AND DATE(o.order_date) BETWEEN ? AND ?
        AND o.status IN ('Delivered', 'Shipped')
    GROUP BY p.product_id, p.product_name, p.sku
    HAVING total_quantity > 0
    ORDER BY total_quantity DESC
    LIMIT 10";

$top_products_stmt = mysqli_prepare($connection, $top_products_query);
mysqli_stmt_bind_param($top_products_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($top_products_stmt);
$top_products_result = mysqli_stmt_get_result($top_products_stmt);

if ($top_products_result && mysqli_num_rows($top_products_result) > 0) {
    while ($row = mysqli_fetch_assoc($top_products_result)) {
        $top_products[] = $row;
    }
}

// Top customers report with error handling
$top_customers = [];
$top_customers_query = "
    SELECT u.user_id, 
           CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as customer_name,
           u.email, 
           COUNT(DISTINCT o.order_id) as total_orders,
           COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id 
        AND DATE(o.order_date) BETWEEN ? AND ?
        AND o.status IN ('Delivered', 'Shipped')
    GROUP BY u.user_id, u.first_name, u.last_name, u.email
    HAVING total_orders > 0
    ORDER BY total_spent DESC
    LIMIT 10";

$top_customers_stmt = mysqli_prepare($connection, $top_customers_query);
mysqli_stmt_bind_param($top_customers_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($top_customers_stmt);
$top_customers_result = mysqli_stmt_get_result($top_customers_stmt);

if ($top_customers_result && mysqli_num_rows($top_customers_result) > 0) {
    while ($row = mysqli_fetch_assoc($top_customers_result)) {
        $top_customers[] = $row;
    }
}

// Sales summary with error handling
$sales_summary = [
    'total_orders' => 0,
    'total_revenue' => 0,
    'avg_order_value' => 0,
    'first_order' => null,
    'last_order' => null
];

$sales_summary_query = "
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(total_amount), 0) as avg_order_value,
        MIN(order_date) as first_order,
        MAX(order_date) as last_order
    FROM orders
    WHERE DATE(order_date) BETWEEN ? AND ?
      AND status IN ('Delivered', 'Shipped')";

$sales_summary_stmt = mysqli_prepare($connection, $sales_summary_query);
mysqli_stmt_bind_param($sales_summary_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($sales_summary_stmt);
$sales_summary_result = mysqli_stmt_get_result($sales_summary_stmt);

if ($sales_summary_result && mysqli_num_rows($sales_summary_result) > 0) {
    $sales_summary = mysqli_fetch_assoc($sales_summary_result);
}

// Get daily sales for chart
$daily_sales_query = "
    SELECT 
        DATE(order_date) as sale_date,
        COUNT(*) as order_count,
        COALESCE(SUM(total_amount), 0) as daily_revenue
    FROM orders
    WHERE DATE(order_date) BETWEEN ? AND ?
      AND status IN ('Delivered', 'Shipped')
    GROUP BY DATE(order_date)
    ORDER BY sale_date";

$daily_sales_stmt = mysqli_prepare($connection, $daily_sales_query);
mysqli_stmt_bind_param($daily_sales_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($daily_sales_stmt);
$daily_sales_result = mysqli_stmt_get_result($daily_sales_stmt);

$daily_sales = [];
while ($row = mysqli_fetch_assoc($daily_sales_result)) {
    $daily_sales[] = $row;
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
                            <i class="fas fa-chart-bar me-2"></i>
                            Sales Reports
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Reports</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-success" onclick="exportToCSV()">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Filter Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="filters-card">
                <form method="GET" action="" class="filters-form">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label fw-bold">Start Date</label>
                            <input type="date" class="form-control" id="start_date" 
                                   name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label fw-bold">End Date</label>
                            <input type="date" class="form-control" id="end_date" 
                                   name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-filter me-2"></i>Apply Filter
                                </button>
                                <a href="reports.php" class="btn btn-light flex-fill">
                                    <i class="fas fa-redo-alt me-2"></i>Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sales Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="summary-card">
                <div class="summary-icon" style="background: rgba(30,58,95,0.1); color: var(--primary);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="summary-details">
                    <span class="summary-label">Total Orders</span>
                    <span class="summary-value"><?php echo number_format($sales_summary['total_orders'] ?? 0); ?></span>
                    <span class="summary-period"><?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="summary-card">
                <div class="summary-icon" style="background: rgba(40,167,69,0.1); color: var(--success);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="summary-details">
                    <span class="summary-label">Total Revenue</span>
                    <span class="summary-value">$<?php echo number_format($sales_summary['total_revenue'] ?? 0, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="summary-card">
                <div class="summary-icon" style="background: rgba(23,162,184,0.1); color: var(--info);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="summary-details">
                    <span class="summary-label">Avg Order Value</span>
                    <span class="summary-value">$<?php echo number_format($sales_summary['avg_order_value'] ?? 0, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="summary-card">
                <div class="summary-icon" style="background: rgba(255,193,7,0.1); color: var(--warning);">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="summary-details">
                    <span class="summary-label">Date Range</span>
                    <span class="summary-value small"><?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Sales Chart -->
    <?php if (!empty($daily_sales)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-header">
                    <h5><i class="fas fa-chart-line me-2"></i>Daily Sales Trend</h5>
                </div>
                <div class="chart-body">
                    <canvas id="dailySalesChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Products Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <h5><i class="fas fa-crown me-2" style="color: #ffc107;"></i>Top 10 Selling Products</h5>
                </div>
                <div class="table-responsive">
                    <table class="table report-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Quantity Sold</th>
                                <th>Revenue</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_products)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <p>No products sold in this period</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $total_revenue = $sales_summary['total_revenue'] ?? 1;
                                $rank = 1;
                                foreach ($top_products as $product): 
                                    $percentage = $total_revenue > 0 ? round(($product['total_revenue'] / $total_revenue) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge"><?php echo $rank++; ?></span>
                                    </td>
                                    <td>#<?php echo $product['product_id']; ?></td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><code><?php echo $product['sku']; ?></code></td>
                                    <td class="fw-bold"><?php echo number_format($product['total_quantity']); ?></td>
                                    <td class="text-primary fw-bold">$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    <td>
                                        <div class="percentage-bar">
                                            <span class="percentage-value"><?php echo $percentage; ?>%</span>
                                            <div class="progress" style="height: 6px; width: 80px;">
                                                <div class="progress-bar" style="width: <?php echo $percentage; ?>%;"></div>
                                            </div>
                                        </div>
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

    <!-- Top Customers Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <h5><i class="fas fa-users me-2" style="color: var(--primary);"></i>Top 10 Customers</h5>
                </div>
                <div class="table-responsive">
                    <table class="table report-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer ID</th>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Total Orders</th>
                                <th>Total Spent</th>
                                <th>Avg Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_customers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                        <p>No customer data in this period</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $rank = 1;
                                foreach ($top_customers as $customer): 
                                    $avg_order = $customer['total_orders'] > 0 ? $customer['total_spent'] / $customer['total_orders'] : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="rank-badge"><?php echo $rank++; ?></span>
                                    </td>
                                    <td>#<?php echo $customer['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['customer_name'] ?: 'N/A'); ?></td>
                                    <td><?php echo $customer['email'] ?: 'N/A'; ?></td>
                                    <td class="text-center"><?php echo $customer['total_orders']; ?></td>
                                    <td class="text-success fw-bold">$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                    <td>$<?php echo number_format($avg_order, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Info -->
    <div class="row">
        <div class="col-12">
            <div class="info-box">
                <i class="fas fa-info-circle me-2"></i>
                <span>Report generated on <?php echo date('F j, Y \a\t g:i A'); ?> for period <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Prepare data for chart
const dailySales = <?php echo json_encode($daily_sales); ?>;

if (dailySales.length > 0) {
    const labels = dailySales.map(item => {
        const date = new Date(item.sale_date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    
    const orders = dailySales.map(item => item.order_count);
    const revenue = dailySales.map(item => item.daily_revenue);
    
    // Create chart
    const ctx = document.getElementById('dailySalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenue ($)',
                    data: revenue,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40,167,69,0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Orders',
                    data: orders,
                    borderColor: '#1e3a5f',
                    backgroundColor: 'rgba(30,58,95,0.1)',
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

// Export to CSV function
function exportToCSV() {
    // Collect top products data
    let csv = "Top Products Report\n";
    csv += "Period,<?php echo $start_date; ?> to <?php echo $end_date; ?>\n\n";
    
    csv += "TOP PRODUCTS\n";
    csv += "Rank,Product ID,Product Name,SKU,Quantity Sold,Revenue\n";
    
    <?php 
    $rank = 1;
    foreach ($top_products as $product): 
    ?>
    csv += "<?php echo $rank++; ?>,#<?php echo $product['product_id']; ?>,<?php echo addslashes($product['product_name']); ?>,<?php echo $product['sku']; ?>,<?php echo $product['total_quantity']; ?>,$<?php echo number_format($product['total_revenue'], 2); ?>\n";
    <?php endforeach; ?>
    
    csv += "\nTOP CUSTOMERS\n";
    csv += "Rank,Customer ID,Customer Name,Email,Total Orders,Total Spent\n";
    
    <?php 
    $rank = 1;
    foreach ($top_customers as $customer): 
    ?>
    csv += "<?php echo $rank++; ?>,#<?php echo $customer['user_id']; ?>,<?php echo addslashes($customer['customer_name']); ?>,<?php echo $customer['email']; ?>,<?php echo $customer['total_orders']; ?>,$<?php echo number_format($customer['total_spent'], 2); ?>\n";
    <?php endforeach; ?>
    
    // Create download
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sales_report_<?php echo $start_date; ?>_to_<?php echo $end_date; ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    alert('Report exported successfully!');
}
</script>

<style>
/* Reports Page Specific Styles */

/* Summary Cards */
.summary-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
    height: 100%;
    box-shadow: var(--shadow-sm);
}

.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.summary-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    flex-shrink: 0;
}

.summary-details {
    flex: 1;
}

.summary-label {
    display: block;
    font-size: 0.85rem;
    color: var(--dark-gray);
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-value {
    display: block;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.summary-period {
    font-size: 0.8rem;
    color: var(--dark-gray);
}

/* Chart Card */
.chart-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.chart-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.chart-header h5 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
}

.chart-body {
    padding: 1.5rem;
    height: 300px;
}

/* Table Card */
.table-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.table-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.table-header h5 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
}

/* Report Table */
.report-table {
    margin: 0;
}

.report-table thead th {
    background: transparent;
    color: var(--dark-gray);
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
    padding: 1rem;
}

.report-table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--border);
}

.report-table tbody tr:hover {
    background: var(--light);
}

/* Rank Badge */
.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Percentage Bar */
.percentage-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.percentage-value {
    font-size: 0.85rem;
    color: var(--dark-gray);
    min-width: 40px;
}

.progress {
    background: var(--light);
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    background: var(--primary);
    border-radius: 10px;
}

/* Filters Card */
.filters-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}

/* Info Box */
.info-box {
    background: var(--light);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 1rem;
    color: var(--dark-gray);
    display: flex;
    align-items: center;
}

.info-box i {
    color: var(--primary);
}

/* Page Header */
.page-header-box {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border);
}

.page-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.breadcrumb {
    margin-bottom: 0;
    background: transparent;
    padding: 0;
}

.breadcrumb-item a {
    color: var(--primary);
    text-decoration: none;
}

/* Responsive */
@media (max-width: 768px) {
    .summary-card {
        padding: 1rem;
    }
    
    .summary-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .summary-value {
        font-size: 1.4rem;
    }
    
    .chart-body {
        height: 250px;
    }
    
    .report-table thead th {
        font-size: 0.75rem;
        padding: 0.75rem;
    }
    
    .report-table tbody td {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .summary-card {
        flex-direction: column;
        text-align: center;
    }
    
    .percentage-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .percentage-value {
        min-width: auto;
    }
}

/* Print Styles */
@media print {
    .btn,
    .filters-card,
    .page-actions,
    .info-box,
    .back-to-top {
        display: none !important;
    }
    
    .page-header-box {
        border: none;
        box-shadow: none;
    }
    
    .summary-card {
        border: 1px solid #000;
        box-shadow: none;
    }
    
    .table-card {
        border: 1px solid #000;
        box-shadow: none;
        break-inside: avoid;
    }
}
</style>