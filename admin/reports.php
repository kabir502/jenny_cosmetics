<?php
// admin/reports.php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
requireAdminLogin();

// Get date range from GET or default to last 30 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Top selling products report
$top_products_query = "
    SELECT p.product_id, p.product_name, p.sku, 
           SUM(oi.quantity) as total_quantity, 
           SUM(oi.total_price) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date 23:59:59'
      AND o.status IN ('Delivered', 'Shipped')
    GROUP BY p.product_id
    ORDER BY total_quantity DESC
    LIMIT 10
";

$top_products_result = mysqli_query($connection, $top_products_query);

// Top customers report
$top_customers_query = "
    SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) as customer_name,
           u.email, COUNT(o.order_id) as total_orders,
           SUM(o.total_amount) as total_spent
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date 23:59:59'
      AND o.status IN ('Delivered', 'Shipped')
    GROUP BY u.user_id
    ORDER BY total_spent DESC
    LIMIT 10
";

$top_customers_result = mysqli_query($connection, $top_customers_query);

// Sales summary
$sales_summary_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value,
        MIN(order_date) as first_order,
        MAX(order_date) as last_order
    FROM orders
    WHERE order_date BETWEEN '$start_date' AND '$end_date 23:59:59'
      AND status IN ('Delivered', 'Shipped')
";

$sales_summary_result = mysqli_query($connection, $sales_summary_query);
$sales_summary = mysqli_fetch_assoc($sales_summary_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'includes/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h4>Sales Reports</h4>
                    </div>
                    <div class="card-body">
                        <!-- Date Filter Form -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" 
                                       name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" 
                                       name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="reports.php" class="btn btn-secondary ms-2">Reset</a>
                            </div>
                        </form>
                        
                        <!-- Sales Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card text-white bg-primary">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Orders</h5>
                                        <h2><?php echo $sales_summary['total_orders']; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Revenue</h5>
                                        <h2>$<?php echo number_format($sales_summary['total_revenue'], 2); ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <h5 class="card-title">Avg Order Value</h5>
                                        <h2>$<?php echo number_format($sales_summary['avg_order_value'], 2); ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-body">
                                        <h5 class="card-title">Period</h5>
                                        <h6><?php echo $sales_summary['first_order']; ?> to <?php echo $sales_summary['last_order']; ?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Products Table -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Top 10 Selling Products</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Product ID</th>
                                                <th>Product Name</th>
                                                <th>SKU</th>
                                                <th>Quantity Sold</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($product = mysqli_fetch_assoc($top_products_result)): ?>
                                                <tr>
                                                    <td><?php echo $product['product_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                    <td><?php echo $product['sku']; ?></td>
                                                    <td><?php echo $product['total_quantity']; ?></td>
                                                    <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Customers Table -->
                        <div class="card">
                            <div class="card-header">
                                <h5>Top 10 Customers</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Customer ID</th>
                                                <th>Customer Name</th>
                                                <th>Email</th>
                                                <th>Total Orders</th>
                                                <th>Total Spent</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($customer = mysqli_fetch_assoc($top_customers_result)): ?>
                                                <tr>
                                                    <td><?php echo $customer['user_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                                    <td><?php echo $customer['email']; ?></td>
                                                    <td><?php echo $customer['total_orders']; ?></td>
                                                    <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>