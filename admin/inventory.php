<?php
// admin/inventory.php - Inventory Management Page

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

// Handle Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $new_quantity = (int)$_POST['quantity'];
    $adjustment_reason = mysqli_real_escape_string($connection, trim($_POST['adjustment_reason'] ?? ''));
    
    // Get current quantity
    $current_query = "SELECT quantity_in_stock, product_name FROM products WHERE product_id = ?";
    $current_stmt = mysqli_prepare($connection, $current_query);
    mysqli_stmt_bind_param($current_stmt, "i", $product_id);
    mysqli_stmt_execute($current_stmt);
    $current_result = mysqli_stmt_get_result($current_stmt);
    $current = mysqli_fetch_assoc($current_result);
    $old_quantity = $current['quantity_in_stock'];
    
    // Update stock
    $update_query = "UPDATE products SET quantity_in_stock = ? WHERE product_id = ?";
    $update_stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $product_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $message = "Stock updated for '{$current['product_name']}' from $old_quantity to $new_quantity.";
        $message_type = 'success';
        
        // Log the action
        error_log("Admin {$_SESSION['admin_name']} updated stock for product ID: $product_id - Old: $old_quantity, New: $new_quantity, Reason: $adjustment_reason");
    } else {
        $message = "Failed to update stock.";
        $message_type = 'danger';
    }
}

// Handle Bulk Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_stock_update'])) {
    $updates = $_POST['updates'] ?? [];
    $success_count = 0;
    $fail_count = 0;
    
    foreach ($updates as $update) {
        $product_id = (int)$update['product_id'];
        $new_quantity = (int)$update['quantity'];
        
        $update_query = "UPDATE products SET quantity_in_stock = ? WHERE product_id = ?";
        $update_stmt = mysqli_prepare($connection, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $product_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success_count++;
        } else {
            $fail_count++;
        }
    }
    
    if ($success_count > 0) {
        $message = "Bulk stock update completed. $success_count products updated successfully.";
        if ($fail_count > 0) {
            $message .= " $fail_count products failed.";
        }
        $message_type = $fail_count > 0 ? 'warning' : 'success';
        
        error_log("Admin {$_SESSION['admin_name']} performed bulk stock update: $success_count success, $fail_count failed");
    }
}

// Handle Stock Alert Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_alert_settings'])) {
    $product_id = (int)$_POST['product_id'];
    $min_stock_level = (int)$_POST['min_stock_level'];
    $max_stock_level = (int)$_POST['max_stock_level'];
    
    $update_query = "UPDATE products SET min_stock_level = ?, max_stock_level = ? WHERE product_id = ?";
    $update_stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($update_stmt, "iii", $min_stock_level, $max_stock_level, $product_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $message = "Stock alert settings updated successfully.";
        $message_type = 'success';
    } else {
        $message = "Failed to update alert settings.";
        $message_type = 'danger';
    }
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
$category_filter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$stock_filter = isset($_GET['stock_status']) ? mysqli_real_escape_string($connection, $_GET['stock_status']) : '';
$sort_by = isset($_GET['sort']) ? mysqli_real_escape_string($connection, $_GET['sort']) : 'product_name';
$sort_order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC';

// Build query conditions
$where_conditions = [];

if (!empty($search)) {
    $where_conditions[] = "(p.product_name LIKE '%$search%' OR p.sku LIKE '%$search%' OR p.description LIKE '%$search%')";
}

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = $category_filter";
}

if ($stock_filter == 'low') {
    $where_conditions[] = "p.quantity_in_stock <= p.min_stock_level AND p.quantity_in_stock > 0";
} elseif ($stock_filter == 'out') {
    $where_conditions[] = "p.quantity_in_stock = 0";
} elseif ($stock_filter == 'overstock') {
    $where_conditions[] = "p.quantity_in_stock >= p.max_stock_level";
} elseif ($stock_filter == 'normal') {
    $where_conditions[] = "p.quantity_in_stock > p.min_stock_level AND p.quantity_in_stock < p.max_stock_level";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Validate sort column
$allowed_sort = ['product_name', 'sku', 'quantity_in_stock', 'min_stock_level', 'max_stock_level', 'total_sold', 'category_name'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'product_name';
}

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                $where_clause";
$count_result = mysqli_query($connection, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get products with inventory data
$products_query = "SELECT 
                    p.*,
                    c.category_name,
                    (p.quantity_in_stock <= p.min_stock_level AND p.quantity_in_stock > 0) as is_low_stock,
                    (p.quantity_in_stock = 0) as is_out_of_stock,
                    (p.quantity_in_stock >= p.max_stock_level) as is_overstock,
                    (p.quantity_in_stock * p.cost_price) as inventory_value,
                    (p.quantity_in_stock * p.unit_price) as retail_value
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                $where_clause
                ORDER BY 
                    CASE 
                        WHEN p.quantity_in_stock <= p.min_stock_level AND p.quantity_in_stock > 0 THEN 1
                        WHEN p.quantity_in_stock = 0 THEN 2
                        ELSE 3
                    END,
                    $sort_by $sort_order
                LIMIT $offset, $records_per_page";

$products_result = mysqli_query($connection, $products_query);

// Get categories for filter
$categories_query = "SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name";
$categories_result = mysqli_query($connection, $categories_query);

// Get inventory statistics - FIXED WITH COALESCE TO HANDLE NULL VALUES
$stats_query = "SELECT 
                    COUNT(*) as total_products,
                    COALESCE(SUM(quantity_in_stock), 0) as total_items,
                    COALESCE(SUM(quantity_in_stock * cost_price), 0) as total_inventory_value,
                    COALESCE(SUM(quantity_in_stock * unit_price), 0) as total_retail_value,
                    COALESCE(SUM(CASE WHEN quantity_in_stock <= min_stock_level AND quantity_in_stock > 0 THEN 1 ELSE 0 END), 0) as low_stock_count,
                    COALESCE(SUM(CASE WHEN quantity_in_stock = 0 THEN 1 ELSE 0 END), 0) as out_of_stock_count,
                    COALESCE(SUM(CASE WHEN quantity_in_stock >= max_stock_level THEN 1 ELSE 0 END), 0) as overstock_count,
                    COALESCE(AVG(quantity_in_stock), 0) as avg_stock,
                    COALESCE(SUM(total_sold), 0) as total_sold
                FROM products";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Ensure all stats have default values
$stats['total_products'] = $stats['total_products'] ?? 0;
$stats['total_items'] = $stats['total_items'] ?? 0;
$stats['total_inventory_value'] = $stats['total_inventory_value'] ?? 0;
$stats['total_retail_value'] = $stats['total_retail_value'] ?? 0;
$stats['low_stock_count'] = $stats['low_stock_count'] ?? 0;
$stats['out_of_stock_count'] = $stats['out_of_stock_count'] ?? 0;
$stats['overstock_count'] = $stats['overstock_count'] ?? 0;
$stats['avg_stock'] = $stats['avg_stock'] ?? 0;
$stats['total_sold'] = $stats['total_sold'] ?? 0;

// Get low stock alerts
$low_stock_query = "SELECT 
                        product_id, 
                        product_name, 
                        sku, 
                        quantity_in_stock, 
                        min_stock_level,
                        category_id
                    FROM products 
                    WHERE quantity_in_stock <= min_stock_level AND quantity_in_stock > 0
                    ORDER BY (min_stock_level - quantity_in_stock) DESC
                    LIMIT 10";
$low_stock_result = mysqli_query($connection, $low_stock_query);

// Get out of stock items
$out_stock_query = "SELECT 
                        product_id, 
                        product_name, 
                        sku, 
                        COALESCE(total_sold, 0) as total_sold
                    FROM products 
                    WHERE quantity_in_stock = 0
                    ORDER BY total_sold DESC
                    LIMIT 10";
$out_stock_result = mysqli_query($connection, $out_stock_query);

// Get top selling products
$top_selling_query = "SELECT 
                        product_id, 
                        product_name, 
                        sku, 
                        COALESCE(total_sold, 0) as total_sold,
                        quantity_in_stock
                    FROM products 
                    WHERE total_sold > 0
                    ORDER BY total_sold DESC
                    LIMIT 10";
$top_selling_result = mysqli_query($connection, $top_selling_query);

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
                            <i class="fas fa-warehouse me-2"></i>
                            Inventory Management
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Inventory</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" onclick="exportInventoryReport()">
                            <i class="fas fa-download me-2"></i>Export Report
                        </button>
                        <button class="btn btn-success" onclick="showBulkUpdateModal()">
                            <i class="fas fa-edit me-2"></i>Bulk Update
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

    <!-- Inventory Statistics - FIXED WITH NULL CHECKS -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(30,58,95,0.1); color: var(--primary);">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Products</span>
                    <span class="stat-value"><?php echo number_format((float)($stats['total_products'] ?? 0)); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(25,135,84,0.1); color: var(--success);">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Items</span>
                    <span class="stat-value"><?php echo number_format((float)($stats['total_items'] ?? 0)); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255,193,7,0.1); color: var(--warning);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Low Stock</span>
                    <span class="stat-value"><?php echo number_format((float)($stats['low_stock_count'] ?? 0)); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(220,53,69,0.1); color: var(--danger);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Out of Stock</span>
                    <span class="stat-value"><?php echo number_format((float)($stats['out_of_stock_count'] ?? 0)); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(13,202,240,0.1); color: var(--info);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Sold</span>
                    <span class="stat-value"><?php echo number_format((float)($stats['total_sold'] ?? 0)); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(111,66,193,0.1); color: #6f42c1;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Inventory Value</span>
                    <span class="stat-value">$<?php echo number_format((float)($stats['total_inventory_value'] ?? 0), 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Alerts Row -->
    <div class="row mb-4">
        <div class="col-lg-4 mb-3">
            <div class="alert-card alert-low-stock">
                <div class="alert-card-header">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <h6>Low Stock Alerts</h6>
                    <span class="badge"><?php echo number_format((float)($stats['low_stock_count'] ?? 0)); ?></span>
                </div>
                <div class="alert-card-body">
                    <?php if (mysqli_num_rows($low_stock_result) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($low_stock_result)): ?>
                        <div class="alert-item" onclick="editStock(<?php echo $item['product_id']; ?>)">
                            <div class="alert-item-info">
                                <div class="alert-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="alert-item-sku">SKU: <?php echo $item['sku']; ?></div>
                            </div>
                            <div class="alert-item-stock">
                                <span class="stock-current"><?php echo (int)$item['quantity_in_stock']; ?></span>
                                <span class="stock-min">/ <?php echo (int)$item['min_stock_level']; ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No low stock items</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-3">
            <div class="alert-card alert-out-stock">
                <div class="alert-card-header">
                    <i class="fas fa-times-circle me-2"></i>
                    <h6>Out of Stock</h6>
                    <span class="badge"><?php echo number_format((float)($stats['out_of_stock_count'] ?? 0)); ?></span>
                </div>
                <div class="alert-card-body">
                    <?php if (mysqli_num_rows($out_stock_result) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($out_stock_result)): ?>
                        <div class="alert-item" onclick="editStock(<?php echo $item['product_id']; ?>)">
                            <div class="alert-item-info">
                                <div class="alert-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="alert-item-sku">SKU: <?php echo $item['sku']; ?></div>
                            </div>
                            <div class="alert-item-stock">
                                <span class="stock-sold">Sold: <?php echo (int)($item['total_sold'] ?? 0); ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No out of stock items</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-3">
            <div class="alert-card alert-top-selling">
                <div class="alert-card-header">
                    <i class="fas fa-chart-line me-2"></i>
                    <h6>Top Selling</h6>
                    <span class="badge"><?php echo mysqli_num_rows($top_selling_result); ?></span>
                </div>
                <div class="alert-card-body">
                    <?php if (mysqli_num_rows($top_selling_result) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($top_selling_result)): ?>
                        <div class="alert-item" onclick="viewProduct(<?php echo $item['product_id']; ?>)">
                            <div class="alert-item-info">
                                <div class="alert-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="alert-item-sku">SKU: <?php echo $item['sku']; ?></div>
                            </div>
                            <div class="alert-item-stock">
                                <span class="stock-sold">Sold: <?php echo (int)($item['total_sold'] ?? 0); ?></span>
                                <span class="stock-current">Stock: <?php echo (int)($item['quantity_in_stock'] ?? 0); ?></span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No sales data</p>
                    <?php endif; ?>
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
                        <div class="col-lg-4 col-md-12">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search products by name, SKU, description..." 
                                       value="<?php echo htmlspecialchars($search ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
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
                            <select class="form-select" name="stock_status">
                                <option value="">All Stock</option>
                                <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="out" <?php echo $stock_filter == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                                <option value="normal" <?php echo $stock_filter == 'normal' ? 'selected' : ''; ?>>Normal Stock</option>
                                <option value="overstock" <?php echo $stock_filter == 'overstock' ? 'selected' : ''; ?>>Overstock</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <select class="form-select" name="sort">
                                <option value="product_name" <?php echo $sort_by == 'product_name' ? 'selected' : ''; ?>>Sort by Name</option>
                                <option value="quantity_in_stock" <?php echo $sort_by == 'quantity_in_stock' ? 'selected' : ''; ?>>Sort by Stock</option>
                                <option value="total_sold" <?php echo $sort_by == 'total_sold' ? 'selected' : ''; ?>>Sort by Sold</option>
                                <option value="min_stock_level" <?php echo $sort_by == 'min_stock_level' ? 'selected' : ''; ?>>Sort by Min Stock</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-12">
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Apply
                                </button>
                                <a href="inventory.php" class="btn btn-light w-100 mt-2">
                                    <i class="fas fa-times me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <h5><i class="fas fa-list me-2"></i>Inventory List</h5>
                        <span class="records-count">Showing <?php echo min($offset + 1, $total_records); ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records</span>
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-light" onclick="exportToCSV()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table inventory-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Min Level</th>
                                <th>Max Level</th>
                                <th>Status</th>
                                <th>Sold</th>
                                <th>Value</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products_result && mysqli_num_rows($products_result) > 0): ?>
                                <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                                <?php 
                                    $stock_status = '';
                                    $status_class = '';
                                    
                                    if ($product['is_out_of_stock']) {
                                        $stock_status = 'Out of Stock';
                                        $status_class = 'status-out';
                                    } elseif ($product['is_low_stock']) {
                                        $stock_status = 'Low Stock';
                                        $status_class = 'status-low';
                                    } elseif ($product['is_overstock']) {
                                        $stock_status = 'Overstock';
                                        $status_class = 'status-over';
                                    } else {
                                        $stock_status = 'Normal';
                                        $status_class = 'status-normal';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <span class="product-id">#<?php echo $product['product_id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-image-sm">
                                                <?php if (!empty($product['image_url'])): ?>
                                                    <img src="../<?php echo $product['image_url']; ?>" 
                                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                                <?php else: ?>
                                                    <div class="no-image-sm">
                                                        <i class="fas fa-box"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-details">
                                                <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="product-sku"><?php echo htmlspecialchars($product['sku']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </td>
                                    <td>
                                        <div class="stock-quantity <?php echo $status_class; ?>">
                                            <?php echo (int)($product['quantity_in_stock'] ?? 0); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="stock-level"><?php echo (int)($product['min_stock_level'] ?? 10); ?></div>
                                    </td>
                                    <td>
                                        <div class="stock-level"><?php echo (int)($product['max_stock_level'] ?? 100); ?></div>
                                    </td>
                                    <td>
                                        <span class="stock-status <?php echo $status_class; ?>">
                                            <?php echo $stock_status; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="sold-count"><?php echo (int)($product['total_sold'] ?? 0); ?></div>
                                    </td>
                                    <td>
                                        <div class="inventory-value">
                                            $<?php echo number_format((float)($product['inventory_value'] ?? 0), 2); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action edit" title="Update Stock" 
                                                    onclick="editStock(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>', <?php echo (int)($product['quantity_in_stock'] ?? 0); ?>)">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="btn-action settings" title="Alert Settings" 
                                                    onclick="editAlertSettings(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>', <?php echo (int)($product['min_stock_level'] ?? 10); ?>, <?php echo (int)($product['max_stock_level'] ?? 100); ?>)">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <button class="btn-action view" title="View Product" 
                                                    onclick="viewProduct(<?php echo $product['product_id']; ?>)">
                                                <i class="fas fa-external-link-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5">
                                        <i class="fas fa-box-open" style="font-size: 3rem; color: var(--light-gray);"></i>
                                        <p class="mt-3 mb-0">No products found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="table-footer">
                    <nav aria-label="Inventory pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search ?? ''); ?>&category_id=<?php echo $category_filter; ?>&stock_status=<?php echo urlencode($stock_filter ?? ''); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo $sort_order == 'ASC' ? 'asc' : 'desc'; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&category_id=<?php echo $category_filter; ?>&stock_status=<?php echo urlencode($stock_filter ?? ''); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo $sort_order == 'ASC' ? 'asc' : 'desc'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search ?? ''); ?>&category_id=<?php echo $category_filter; ?>&stock_status=<?php echo urlencode($stock_filter ?? ''); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo $sort_order == 'ASC' ? 'asc' : 'desc'; ?>">
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

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1" aria-labelledby="updateStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStockModalLabel">
                    <i class="fas fa-pen me-2" style="color: var(--primary);"></i>
                    Update Stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="updateStockForm">
                <input type="hidden" name="product_id" id="stock_product_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <input type="text" class="form-control" id="stock_product_name" readonly disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="current_stock" class="form-label">Current Stock</label>
                        <input type="text" class="form-control" id="current_stock" readonly disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label">New Stock Quantity *</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adjustment_reason" class="form-label">Adjustment Reason</label>
                        <textarea class="form-control" id="adjustment_reason" name="adjustment_reason" rows="2" placeholder="e.g., Stock count, New shipment, Return, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_stock" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Alert Settings Modal -->
<div class="modal fade" id="alertSettingsModal" tabindex="-1" aria-labelledby="alertSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertSettingsModalLabel">
                    <i class="fas fa-cog me-2" style="color: var(--primary);"></i>
                    Stock Alert Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="alertSettingsForm">
                <input type="hidden" name="product_id" id="alert_product_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <input type="text" class="form-control" id="alert_product_name" readonly disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="min_stock_level" class="form-label">Minimum Stock Level</label>
                        <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" min="0" required>
                        <small class="text-muted">Alert when stock falls below this level</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="max_stock_level" class="form-label">Maximum Stock Level</label>
                        <input type="number" class="form-control" id="max_stock_level" name="max_stock_level" min="0" required>
                        <small class="text-muted">Alert when stock exceeds this level (overstock)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_alert_settings" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Update Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUpdateModalLabel">
                    <i class="fas fa-edit me-2" style="color: var(--primary);"></i>
                    Bulk Stock Update
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="bulkUpdateForm">
                <div class="modal-body">
                    <p class="text-muted mb-3">Enter new stock quantities for multiple products. Leave blank to keep current value.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-sm bulk-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Current Stock</th>
                                    <th>New Stock</th>
                                </tr>
                            </thead>
                            <tbody id="bulkProductsList">
                                <!-- Will be populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="bulk_stock_update" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update All
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Page-specific scripts -->
<script>
// Edit stock function - UPDATED with parameters
function editStock(productId, productName, currentStock) {
    document.getElementById('stock_product_id').value = productId;
    document.getElementById('stock_product_name').value = productName;
    document.getElementById('current_stock').value = currentStock;
    document.getElementById('quantity').value = currentStock;
    document.getElementById('adjustment_reason').value = '';
    
    new bootstrap.Modal(document.getElementById('updateStockModal')).show();
}

// Edit alert settings
function editAlertSettings(productId, productName, minStock, maxStock) {
    document.getElementById('alert_product_id').value = productId;
    document.getElementById('alert_product_name').value = productName;
    document.getElementById('min_stock_level').value = minStock;
    document.getElementById('max_stock_level').value = maxStock;
    
    new bootstrap.Modal(document.getElementById('alertSettingsModal')).show();
}

// View product
function viewProduct(productId) {
    window.open('product-detail.php?id=' + productId, '_blank');
}

// Show bulk update modal
function showBulkUpdateModal() {
    const tbody = document.getElementById('bulkProductsList');
    const rows = document.querySelectorAll('.inventory-table tbody tr');
    
    let html = '';
    rows.forEach(row => {
        if (row.cells.length > 1) {
            const productId = row.cells[0].textContent.replace('#', '');
            const productName = row.cells[1].querySelector('.product-name')?.textContent || '';
            const sku = row.cells[2].textContent || '';
            const currentStock = row.cells[4].querySelector('.stock-quantity')?.textContent || '0';
            
            html += `
                <tr>
                    <td>${productId}</td>
                    <td>${productName}</td>
                    <td>${sku}</td>
                    <td>${currentStock}</td>
                    <td>
                        <input type="hidden" name="updates[${productId}][product_id]" value="${productId}">
                        <input type="number" class="form-control form-control-sm" 
                               name="updates[${productId}][quantity]" 
                               placeholder="New quantity" min="0">
                    </td>
                </tr>
            `;
        }
    });
    
    tbody.innerHTML = html;
    new bootstrap.Modal(document.getElementById('bulkUpdateModal')).show();
}

// Export inventory report
function exportInventoryReport() {
    const data = [];
    const headers = ['ID', 'Product', 'SKU', 'Category', 'Current Stock', 'Min Level', 'Max Level', 'Status', 'Sold', 'Value'];
    data.push(headers);
    
    const rows = document.querySelectorAll('.inventory-table tbody tr');
    rows.forEach(row => {
        if (row.cells.length > 1) {
            const rowData = [
                row.cells[0]?.textContent.replace('#', '') || '',
                row.cells[1]?.querySelector('.product-name')?.textContent || '',
                row.cells[2]?.textContent || '',
                row.cells[3]?.textContent || '',
                row.cells[4]?.querySelector('.stock-quantity')?.textContent || '0',
                row.cells[5]?.textContent || '0',
                row.cells[6]?.textContent || '0',
                row.cells[7]?.querySelector('.stock-status')?.textContent || '',
                row.cells[8]?.textContent || '0',
                row.cells[9]?.textContent || '$0.00'
            ];
            data.push(rowData);
        }
    });
    
    // Create CSV
    let csv = data.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'inventory_report_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Inventory report exported successfully', 'success');
}

// Export to CSV (for the export button)
function exportToCSV() {
    exportInventoryReport();
}

// Override the old editStock function that might be called from alerts
window.editStock = function(productId) {
    // Get product details from the first matching row
    const rows = document.querySelectorAll('.inventory-table tbody tr');
    for (let row of rows) {
        if (row.cells.length > 1 && row.cells[0]?.textContent.includes(productId)) {
            const productName = row.cells[1]?.querySelector('.product-name')?.textContent || '';
            const currentStock = row.cells[4]?.querySelector('.stock-quantity')?.textContent || '0';
            editStock(productId, productName, parseInt(currentStock));
            break;
        }
    }
};
</script>