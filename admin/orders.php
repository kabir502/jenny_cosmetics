<?php
// admin/orders.php - Orders Management Page with Full Responsiveness

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

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = mysqli_real_escape_string($connection, $_POST['status']);
    
    $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Order #$order_id status updated to $new_status successfully.";
        $message_type = 'success';
        
        // Log the action
        error_log("Admin {$_SESSION['admin_name']} updated order #$order_id status to $new_status");
    } else {
        $message = "Failed to update order status.";
        $message_type = 'danger';
    }
}

// Handle order deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $order_id = (int)$_GET['delete'];
    
    // Start transaction
    mysqli_begin_transaction($connection);
    
    try {
        // Delete order items first (foreign key constraint)
        $delete_items = "DELETE FROM order_items WHERE order_id = ?";
        $stmt_items = mysqli_prepare($connection, $delete_items);
        mysqli_stmt_bind_param($stmt_items, "i", $order_id);
        mysqli_stmt_execute($stmt_items);
        
        // Delete order
        $delete_order = "DELETE FROM orders WHERE order_id = ?";
        $stmt_order = mysqli_prepare($connection, $delete_order);
        mysqli_stmt_bind_param($stmt_order, "i", $order_id);
        mysqli_stmt_execute($stmt_order);
        
        mysqli_commit($connection);
        $message = "Order #$order_id deleted successfully.";
        $message_type = 'success';
        
        error_log("Admin {$_SESSION['admin_name']} deleted order #$order_id");
    } catch (Exception $e) {
        mysqli_rollback($connection);
        $message = "Failed to delete order.";
        $message_type = 'danger';
        error_log("Error deleting order #$order_id: " . $e->getMessage());
    }
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($connection, $_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($connection, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($connection, $_GET['date_to']) : '';

// Build query conditions
$where_conditions = [];
if (!empty($search)) {
    $where_conditions[] = "(o.order_number LIKE '%$search%' OR 
                             CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%' OR 
                             u.email LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $where_conditions[] = "o.status = '$status_filter'";
}
if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.order_date) >= '$date_from'";
}
if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.order_date) <= '$date_to'";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM orders o 
                JOIN users u ON o.user_id = u.user_id 
                $where_clause";
$count_result = mysqli_query($connection, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get orders with pagination
$orders_query = "SELECT 
                    o.order_id,
                    o.order_number,
                    o.order_date,
                    o.total_amount,
                    o.status,
                    o.payment_method,
                    o.shipping_address,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                $where_clause
                ORDER BY o.order_date DESC
                LIMIT $offset, $records_per_page";

$orders_result = mysqli_query($connection, $orders_query);

// Get order status summary for dashboard
$status_summary_query = "SELECT 
                            status, 
                            COUNT(*) as count,
                            SUM(total_amount) as total
                        FROM orders 
                        GROUP BY status";
$status_summary_result = mysqli_query($connection, $status_summary_query);
$status_summary = [];
while ($row = mysqli_fetch_assoc($status_summary_result)) {
    $status_summary[$row['status']] = $row;
}

// Get recent activity (last 5 orders)
$recent_activity_query = "SELECT 
                            o.order_number,
                            o.total_amount,
                            o.status,
                            o.order_date,
                            CONCAT(u.first_name, ' ', u.last_name) as customer
                        FROM orders o
                        JOIN users u ON o.user_id = u.user_id
                        ORDER BY o.order_date DESC
                        LIMIT 5";
$recent_activity_result = mysqli_query($connection, $recent_activity_query);

// Include admin header
include '../includes/admin_header.php';
?>

<!-- Page Content -->
<div class="container-fluid px-3 px-md-4 px-lg-5">
    <!-- Page Header -->
    <div class="row mb-3 mb-md-4">
        <div class="col-12">
            <div class="page-header-box p-3 p-md-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h1 class="page-title h3 h2-md mb-1">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Orders Management
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb flex-wrap mb-0">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Orders</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary w-100 w-md-auto" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
    <div class="row mb-3 mb-md-4">
        <div class="col-12">
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status Summary Cards - Responsive Grid -->
    <div class="row g-2 g-md-3 g-lg-4 mb-3 mb-md-4">
        <div class="col-xl-2 col-lg-3 col-md-4 col-6 mb-2 mb-md-3">
            <div class="status-card all p-2 p-md-3">
                <div class="d-flex align-items-center gap-2 gap-md-3">
                    <div class="status-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="status-details">
                        <span class="status-label small text-uppercase">All Orders</span>
                        <span class="status-count h4 h3-md mb-0"><?php echo $total_records; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        $status_colors = [
            'Pending' => 'warning',
            'Processing' => 'info',
            'Shipped' => 'primary',
            'Delivered' => 'success',
            'Cancelled' => 'danger',
            'Refunded' => 'secondary'
        ];
        
        foreach ($status_summary as $status => $data):
            $color = $status_colors[$status] ?? 'secondary';
        ?>
        <div class="col-xl-2 col-lg-3 col-md-4 col-6 mb-2 mb-md-3">
            <div class="status-card <?php echo $color; ?> p-2 p-md-3">
                <div class="d-flex align-items-center gap-2 gap-md-3">
                    <div class="status-icon">
                        <i class="fas fa-<?php 
                            echo $status == 'Pending' ? 'clock' : 
                                ($status == 'Processing' ? 'cog' : 
                                ($status == 'Shipped' ? 'truck' : 
                                ($status == 'Delivered' ? 'check-circle' : 
                                ($status == 'Cancelled' ? 'times-circle' : 'undo-alt')))); 
                        ?>"></i>
                    </div>
                    <div class="status-details">
                        <span class="status-label small text-uppercase"><?php echo $status; ?></span>
                        <span class="status-count h4 h3-md mb-0 d-block"><?php echo $data['count']; ?></span>
                        <span class="status-total small d-none d-sm-block">$<?php echo number_format($data['total'] ?? 0, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters and Search - Responsive -->
    <div class="row mb-3 mb-md-4">
        <div class="col-12">
            <div class="filters-card p-3 p-md-4">
                <form method="GET" action="" class="filters-form">
                    <div class="row g-2 g-md-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search orders, customers, emails..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo $status_filter == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="Shipped" <?php echo $status_filter == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo $status_filter == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="Refunded" <?php echo $status_filter == 'Refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <input type="date" class="form-control" name="date_from" 
                                   placeholder="From Date" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <input type="date" class="form-control" name="date_to" 
                                   placeholder="To Date" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <div class="filter-actions d-flex flex-column flex-sm-row gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-filter me-2"></i>Apply
                                </button>
                                <a href="orders.php" class="btn btn-light flex-fill">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Orders Table - Fully Responsive -->
    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header p-3 p-md-4 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div class="table-title">
                        <h5 class="h5 mb-1"><i class="fas fa-list me-2"></i>Orders List</h5>
                        <span class="records-count small">Showing <?php echo min($offset + 1, $total_records); ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records</span>
                    </div>
                    <div class="table-actions d-flex gap-2 w-100 w-md-auto">
                        <button class="btn btn-light btn-sm flex-fill flex-md-grow-0" onclick="exportToCSV()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                        <button class="btn btn-light btn-sm flex-fill flex-md-grow-0" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table orders-table align-middle">
                        <thead>
                            <tr>
                                <th class="d-none d-sm-table-cell">Order #</th>
                                <th>Customer</th>
                                <th class="d-none d-md-table-cell">Date</th>
                                <th class="d-none d-sm-table-cell">Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="d-none d-lg-table-cell">Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($orders_result) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                                <tr>
                                    <td class="d-none d-sm-table-cell">
                                        <span class="order-number">#<?php echo $order['order_number']; ?></span>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-name fw-bold">
                                                <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                            </div>
                                            <div class="customer-email small text-muted d-none d-md-block">
                                                <?php echo $order['email']; ?>
                                            </div>
                                            <div class="order-number-mobile d-sm-none small text-primary">
                                                #<?php echo $order['order_number']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <div class="order-date">
                                            <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                        </div>
                                        <div class="order-time small text-muted">
                                            <?php echo date('h:i A', strtotime($order['order_date'])); ?>
                                        </div>
                                    </td>
                                    <td class="d-none d-sm-table-cell text-center">
                                        <span class="item-count"><?php echo $order['item_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="order-total fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                    </td>
                                    <td>
                                        <select class="status-select status-<?php echo strtolower($order['status']); ?> form-select form-select-sm" 
                                                onchange="updateOrderStatus(<?php echo $order['order_id']; ?>, this.value)"
                                                style="min-width: 100px;">
                                            <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Processing" <?php echo $order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Shipped" <?php echo $order['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="Delivered" <?php echo $order['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?php echo $order['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            <option value="Refunded" <?php echo $order['status'] == 'Refunded' ? 'selected' : ''; ?>>Refunded</option>
                                        </select>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <span class="payment-method"><?php echo $order['payment_method'] ?? 'N/A'; ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons d-flex gap-1 gap-md-2">
                                            <a href="order-detail.php?id=<?php echo $order['order_id']; ?>" 
                                               class="btn-action view" title="View Order">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn-action edit" title="Edit Order" 
                                                    onclick="editOrder(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action delete" title="Delete Order" 
                                                    onclick="confirmDelete(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Mobile Order Details Row (hidden on desktop) -->
                                <tr class="d-md-none">
                                    <td colspan="8" class="p-0 border-0">
                                        <div class="mobile-order-details p-2 mb-2 bg-light rounded small">
                                            <div class="d-flex justify-content-between">
                                                <span><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></span>
                                                <span><strong>Items:</strong> <?php echo $order['item_count']; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mt-1">
                                                <span><strong>Payment:</strong> <?php echo $order['payment_method'] ?? 'N/A'; ?></span>
                                                <span><strong>Email:</strong> <?php echo $order['email']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-shopping-cart" style="font-size: 3rem; color: var(--light-gray);"></i>
                                        <p class="mt-3 mb-0">No orders found</p>
                                        <p class="small text-muted">Try adjusting your filters</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination - Responsive -->
                <?php if ($total_pages > 1): ?>
                <div class="table-footer p-3 p-md-4">
                    <nav aria-label="Orders pagination">
                        <ul class="pagination justify-content-center flex-wrap mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
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

    <!-- Recent Activity - Responsive -->
    <div class="row mt-3 mt-md-4 g-3 g-md-4">
        <div class="col-md-6">
            <div class="activity-card h-100">
                <div class="activity-header p-3 p-md-4">
                    <h5 class="h5 mb-0"><i class="fas fa-history me-2"></i>Recent Orders</h5>
                </div>
                <div class="activity-body p-2 p-md-3">
                    <?php if (mysqli_num_rows($recent_activity_result) > 0): ?>
                        <?php while ($activity = mysqli_fetch_assoc($recent_activity_result)): ?>
                        <div class="activity-item p-2 p-md-3">
                            <div class="d-flex align-items-start gap-2 gap-md-3">
                                <div class="activity-icon <?php echo strtolower($activity['status']); ?>">
                                    <i class="fas fa-<?php 
                                        echo $activity['status'] == 'Pending' ? 'clock' : 
                                            ($activity['status'] == 'Processing' ? 'cog' : 
                                            ($activity['status'] == 'Shipped' ? 'truck' : 
                                            ($activity['status'] == 'Delivered' ? 'check-circle' : 
                                            ($activity['status'] == 'Cancelled' ? 'times-circle' : 'undo-alt')))); 
                                    ?>"></i>
                                </div>
                                <div class="activity-details flex-grow-1">
                                    <div class="activity-title d-flex flex-wrap justify-content-between align-items-center">
                                        <strong>#<?php echo $activity['order_number']; ?></strong>
                                        <span class="badge status-<?php echo strtolower($activity['status']); ?>">
                                            <?php echo $activity['status']; ?>
                                        </span>
                                    </div>
                                    <div class="activity-meta small text-muted d-flex flex-wrap gap-2 mt-1">
                                        <span><i class="far fa-user me-1"></i><?php echo htmlspecialchars($activity['customer']); ?></span>
                                        <span><i class="far fa-clock me-1"></i><?php echo date('M d, h:i A', strtotime($activity['order_date'])); ?></span>
                                        <span class="fw-bold text-primary">$<?php echo number_format($activity['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No recent activity</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="stats-card h-100">
                <div class="stats-header p-3 p-md-4">
                    <h5 class="h5 mb-0"><i class="fas fa-chart-pie me-2"></i>Order Statistics</h5>
                </div>
                <div class="stats-body p-3 p-md-4">
                    <canvas id="orderStatsChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for status updates -->
<form id="statusUpdateForm" method="POST" style="display: none;">
    <input type="hidden" name="update_status" value="1">
    <input type="hidden" name="order_id" id="status_order_id">
    <input type="hidden" name="status" id="status_new_status">
</form>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Page-specific scripts -->
<script>
// Update order status
function updateOrderStatus(orderId, status) {
    if (confirm('Are you sure you want to update this order status?')) {
        document.getElementById('status_order_id').value = orderId;
        document.getElementById('status_new_status').value = status;
        document.getElementById('statusUpdateForm').submit();
    }
}

// Edit order
function editOrder(orderId) {
    window.location.href = 'order-edit.php?id=' + orderId;
}

// Confirm delete
function confirmDelete(orderId) {
    if (confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
        window.location.href = 'orders.php?delete=' + orderId;
    }
}

// Export to CSV
function exportToCSV() {
    // Collect table data
    const rows = [];
    const headers = ['Order #', 'Customer', 'Email', 'Date', 'Items', 'Total', 'Status', 'Payment'];
    rows.push(headers);
    
    const tableRows = document.querySelectorAll('.orders-table tbody tr:not(.d-md-none)');
    tableRows.forEach(row => {
        if (row.classList.contains('d-md-none')) return;
        
        const rowData = [
            row.querySelector('.order-number')?.textContent.replace('#', '') || '',
            row.querySelector('.customer-name')?.textContent.trim() || '',
            row.querySelector('.customer-email')?.textContent.trim() || '',
            row.querySelector('.order-date')?.textContent.trim() || '',
            row.querySelector('.item-count')?.textContent.trim() || '0',
            row.querySelector('.order-total')?.textContent.replace('$', '') || '0',
            row.querySelector('.status-select')?.value || '',
            row.querySelector('.payment-method')?.textContent.trim() || ''
        ];
        rows.push(rowData);
    });
    
    // Create CSV
    let csv = rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'orders_export_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Orders exported successfully', 'success');
}

// Chart.js initialization
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('orderStatsChart').getContext('2d');
    
    // Get status counts from PHP
    const statusData = <?php 
        $chart_data = [];
        foreach ($status_summary as $status => $data) {
            $chart_data['labels'][] = $status;
            $chart_data['counts'][] = $data['count'];
            $chart_data['totals'][] = $data['total'] ?? 0;
        }
        echo json_encode($chart_data);
    ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: statusData.labels || [],
            datasets: [{
                data: statusData.counts || [],
                backgroundColor: [
                    '#ffc107', // Pending - warning
                    '#0dcaf0', // Processing - info
                    '#0d6efd', // Shipped - primary
                    '#198754', // Delivered - success
                    '#dc3545', // Cancelled - danger
                    '#6c757d'  // Refunded - secondary
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
                        padding: 15,
                        font: {
                            size: window.innerWidth < 768 ? 10 : 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} orders (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
});

// Responsive chart update on window resize
window.addEventListener('resize', function() {
    // Chart.js handles responsiveness automatically
    console.log('Window resized');
});
</script>

<style>
/* ===== FULLY RESPONSIVE ORDERS PAGE STYLES ===== */

/* Page Header */
.page-header-box {
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border);
}

.page-title {
    font-weight: 600;
    color: var(--dark);
}

.breadcrumb {
    background: transparent;
    padding: 0;
}

.breadcrumb-item a {
    color: var(--primary);
    text-decoration: none;
}

.breadcrumb-item.active {
    color: var(--dark-gray);
}

/* Status Cards - Fully Responsive */
.status-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    height: 100%;
}

.status-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.status-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
}

.status-card.all::before { background: var(--primary); }
.status-card.warning::before { background: var(--warning); }
.status-card.info::before { background: var(--info); }
.status-card.primary::before { background: var(--primary); }
.status-card.success::before { background: var(--success); }
.status-card.danger::before { background: var(--danger); }
.status-card.secondary::before { background: var(--secondary); }

.status-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

@media (min-width: 768px) {
    .status-icon {
        width: 48px;
        height: 48px;
        font-size: 1.5rem;
    }
}

.status-card.all .status-icon {
    background: rgba(30,58,95,0.1);
    color: var(--primary);
}

.status-card.warning .status-icon {
    background: rgba(255,193,7,0.1);
    color: var(--warning);
}

.status-card.info .status-icon {
    background: rgba(13,202,240,0.1);
    color: var(--info);
}

.status-card.primary .status-icon {
    background: rgba(13,110,253,0.1);
    color: var(--primary);
}

.status-card.success .status-icon {
    background: rgba(25,135,84,0.1);
    color: var(--success);
}

.status-card.danger .status-icon {
    background: rgba(220,53,69,0.1);
    color: var(--danger);
}

.status-card.secondary .status-icon {
    background: rgba(108,117,125,0.1);
    color: var(--secondary);
}

.status-details {
    flex: 1;
    min-width: 0; /* Prevent text overflow */
}

.status-label {
    display: block;
    font-size: 0.7rem;
    color: var(--dark-gray);
    margin-bottom: 0.1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (min-width: 768px) {
    .status-label {
        font-size: 0.85rem;
    }
}

.status-count {
    font-weight: 700;
    color: var(--dark);
    line-height: 1.2;
}

.status-total {
    display: none;
    color: var(--dark-gray);
}

@media (min-width: 576px) {
    .status-total {
        display: block;
        font-size: 0.75rem;
    }
}

@media (min-width: 768px) {
    .status-total {
        font-size: 0.85rem;
    }
}

/* Filters Card */
.filters-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
}

.filters-form .search-box {
    position: relative;
}

.filters-form .search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--dark-gray);
    z-index: 10;
}

.filters-form .search-box input {
    padding-left: 2.5rem;
    border: 1px solid var(--border);
    border-radius: 8px;
    height: 42px;
}

.filters-form .form-select,
.filters-form .form-control {
    border: 1px solid var(--border);
    border-radius: 8px;
    height: 42px;
}

/* Table Card */
.table-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.table-header {
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.table-title h5 {
    color: var(--dark);
    font-weight: 600;
}

.records-count {
    color: var(--dark-gray);
}

.table-actions .btn-light {
    background: white;
    border: 1px solid var(--border);
    color: var(--dark);
    transition: var(--transition);
}

.table-actions .btn-light:hover {
    background: var(--light);
    border-color: var(--dark-gray);
}

/* Orders Table - Fully Responsive */
.orders-table {
    margin: 0;
    width: 100%;
}

.orders-table thead th {
    background: var(--light);
    color: var(--dark);
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
    padding: 0.75rem;
    white-space: nowrap;
}

@media (min-width: 768px) {
    .orders-table thead th {
        font-size: 0.85rem;
        padding: 1rem;
    }
}

.orders-table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--border);
    font-size: 0.9rem;
}

@media (min-width: 768px) {
    .orders-table tbody td {
        padding: 1rem;
    }
}

.orders-table tbody tr:hover {
    background: var(--light);
}

/* Order Number */
.order-number {
    font-weight: 600;
    color: var(--primary);
    font-family: var(--font-mono);
    font-size: 0.9rem;
}

.order-number-mobile {
    color: var(--primary);
    margin-top: 0.25rem;
}

/* Customer Info */
.customer-info {
    line-height: 1.3;
}

.customer-name {
    color: var(--dark);
    font-size: 0.9rem;
}

@media (min-width: 768px) {
    .customer-name {
        font-size: 1rem;
    }
}

/* Order Date */
.order-date {
    font-weight: 500;
    color: var(--dark);
    font-size: 0.9rem;
}

.order-time {
    font-size: 0.8rem;
}

/* Item Count */
.item-count {
    display: inline-block;
    min-width: 28px;
    height: 28px;
    background: var(--light);
    border-radius: 50%;
    text-align: center;
    line-height: 28px;
    font-weight: 600;
    color: var(--dark);
    font-size: 0.85rem;
}

@media (min-width: 768px) {
    .item-count {
        min-width: 30px;
        height: 30px;
        line-height: 30px;
        font-size: 0.9rem;
    }
}

/* Order Total */
.order-total {
    font-weight: 700;
    color: var(--primary);
    font-family: var(--font-mono);
    font-size: 0.95rem;
}

/* Status Select */
.status-select {
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 0.3rem 0.6rem;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    min-width: 90px;
}

@media (min-width: 768px) {
    .status-select {
        padding: 0.4rem 1rem;
        font-size: 0.85rem;
        min-width: 100px;
    }
}

.status-select.status-pending {
    background: #fff3cd;
    color: #856404;
    border-color: #ffe69c;
}

.status-select.status-processing {
    background: #cff4fc;
    color: #055160;
    border-color: #b6effb;
}

.status-select.status-shipped {
    background: #cfe2ff;
    color: #084298;
    border-color: #9ec5fe;
}

.status-select.status-delivered {
    background: #d1e7dd;
    color: #0a3622;
    border-color: #a3cfbb;
}

.status-select.status-cancelled {
    background: #f8d7da;
    color: #842029;
    border-color: #f1aeb5;
}

.status-select.status-refunded {
    background: #e2e3e5;
    color: #41464b;
    border-color: #c4c8cb;
}

/* Payment Method */
.payment-method {
    background: var(--light);
    padding: 0.25rem 0.6rem;
    border-radius: 20px;
    font-size: 0.75rem;
    color: var(--dark);
    white-space: nowrap;
    display: inline-block;
}

@media (min-width: 768px) {
    .payment-method {
        padding: 0.3rem 0.8rem;
        font-size: 0.8rem;
    }
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.25rem;
}

@media (min-width: 768px) {
    .action-buttons {
        gap: 0.5rem;
    }
}

.btn-action {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: white;
    color: var(--dark-gray);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    font-size: 0.85rem;
}

@media (min-width: 768px) {
    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        font-size: 0.9rem;
    }
}

.btn-action:hover {
    background: var(--light);
    transform: translateY(-2px);
}

.btn-action.view:hover {
    color: var(--primary);
    border-color: var(--primary);
}

.btn-action.edit:hover {
    color: var(--warning);
    border-color: var(--warning);
}

.btn-action.delete:hover {
    color: var(--danger);
    border-color: var(--danger);
}

/* Mobile Order Details */
.mobile-order-details {
    background: var(--light);
    border-radius: 8px;
    margin: 0 0.5rem 0.5rem 0.5rem;
    font-size: 0.8rem;
}

/* Activity Card */
.activity-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.activity-header {
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.activity-header h5 {
    color: var(--dark);
    font-weight: 600;
}

.activity-item {
    border-bottom: 1px solid var(--border);
    transition: var(--transition);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: var(--light);
}

.activity-icon {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

@media (min-width: 768px) {
    .activity-icon {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
}

.activity-icon.pending {
    background: rgba(255,193,7,0.1);
    color: var(--warning);
}

.activity-icon.processing {
    background: rgba(13,202,240,0.1);
    color: var(--info);
}

.activity-icon.shipped {
    background: rgba(13,110,253,0.1);
    color: var(--primary);
}

.activity-icon.delivered {
    background: rgba(25,135,84,0.1);
    color: var(--success);
}

.activity-icon.cancelled {
    background: rgba(220,53,69,0.1);
    color: var(--danger);
}

.activity-icon.refunded {
    background: rgba(108,117,125,0.1);
    color: var(--secondary);
}

.activity-meta {
    color: var(--dark-gray);
}

/* Stats Card */
.stats-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.stats-header {
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.stats-header h5 {
    color: var(--dark);
    font-weight: 600;
}

.stats-body {
    min-height: 250px;
}

/* Pagination */
.pagination {
    gap: 0.25rem;
}

.page-link {
    border: 1px solid var(--border);
    color: var(--dark);
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    transition: var(--transition);
    font-size: 0.85rem;
}

@media (min-width: 768px) {
    .page-link {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.9rem;
    }
}

.page-link:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.page-item.active .page-link {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.page-item.disabled .page-link {
    color: var(--dark-gray);
    pointer-events: none;
    background: var(--light);
}

/* Badge Styles */
.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
}

@media (min-width: 768px) {
    .badge {
        padding: 0.3rem 0.8rem;
        font-size: 0.75rem;
    }
}

.badge.status-pending {
    background: #fff3cd;
    color: #856404;
}

.badge.status-processing {
    background: #cff4fc;
    color: #055160;
}

.badge.status-shipped {
    background: #cfe2ff;
    color: #084298;
}

.badge.status-delivered {
    background: #d1e7dd;
    color: #0a3622;
}

.badge.status-cancelled {
    background: #f8d7da;
    color: #842029;
}

.badge.status-refunded {
    background: #e2e3e5;
    color: #41464b;
}

/* Print Styles */
@media print {
    .btn,
    .filter-actions,
    .action-buttons,
    .status-select,
    .back-to-top,
    .stats-card {
        display: none !important;
    }
    
    .table-card {
        border: 1px solid #000;
        box-shadow: none;
    }
    
    .orders-table thead th {
        background: #f0f0f0 !important;
        color: #000 !important;
    }
    
    .status-select {
        border: none;
        background: transparent !important;
        color: #000 !important;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
}

/* Touch-friendly adjustments */
@media (hover: none) and (pointer: coarse) {
    .btn-action,
    .page-link,
    .status-select {
        padding: 0.5rem;
    }
    
    .btn-action {
        width: 36px;
        height: 36px;
    }
}
</style>