<?php
// admin/products.php - Products Management Page

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

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query conditions based on filter
$where_conditions = [];
if ($status_filter === 'active') {
    $where_conditions[] = "p.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "p.is_active = 0";
}
// If 'all', no condition added

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total products count based on filter
$count_query = "SELECT COUNT(*) as total FROM products p $where_clause";
$count_result = mysqli_query($connection, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get products with categories
$query = "SELECT p.*, c.category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          $where_clause
          ORDER BY 
            CASE 
                WHEN p.is_active = 0 THEN 1 
                ELSE 2 
            END,
            p.created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($connection, $query);

// Get counts for stats - MATCHING YOUR EXISTING SMALL CARDS STYLE
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
                FROM products";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

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
                            <i class="fas fa-box me-2"></i>
                            Products Management
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Products</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <a href="add_product.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Product
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Summary Cards - MATCHING YOUR EXISTING SMALL CARD STYLE -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="status-card all">
                <div class="d-flex align-items-center">
                    <div class="status-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="status-details ms-2">
                        <span class="status-label">Total Products</span>
                        <span class="status-count"><?php echo $stats['total']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="status-card active">
                <div class="d-flex align-items-center">
                    <div class="status-icon" style="background: rgba(25,135,84,0.1); color: var(--success);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="status-details ms-2">
                        <span class="status-label">Active</span>
                        <span class="status-count"><?php echo $stats['active']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="status-card inactive">
                <div class="d-flex align-items-center">
                    <div class="status-icon" style="background: rgba(220,53,69,0.1); color: var(--danger);">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="status-details ms-2">
                        <span class="status-label">Inactive</span>
                        <span class="status-count"><?php echo $stats['inactive']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs - SMALLER LIKE YOUR EXISTING DESIGN -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="filter-tabs">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter == 'all' ? 'active' : ''; ?>" 
                           href="?status=all">All (<?php echo $stats['total']; ?>)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter == 'active' ? 'active' : ''; ?>" 
                           href="?status=active">Active (<?php echo $stats['active']; ?>)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $status_filter == 'inactive' ? 'active' : ''; ?>" 
                           href="?status=inactive">Inactive (<?php echo $stats['inactive']; ?>)</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Products Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <h5><i class="fas fa-list me-2"></i>Products List</h5>
                        <span class="records-count">Showing <?php echo min($offset + 1, $total_rows); ?> - <?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?> records</span>
                    </div>
                    <div class="table-actions">
                        <input type="text" id="productSearch" class="form-control form-control-sm" placeholder="Search products..." style="width: 200px;">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table products-table" id="productsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($product = mysqli_fetch_assoc($result)): ?>
                                <tr class="<?php echo $product['is_active'] ? '' : 'table-inactive'; ?>">
                                    <td>
                                        <span class="product-id">#<?php echo $product['product_id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="product-image-sm">
                                            <?php if (!empty($product['image_url'])): ?>
                                                <img src="../<?php echo $product['image_url']; ?>" 
                                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                     onerror="this.src='../assets/images/no-image.jpg'">
                                            <?php else: ?>
                                                <div class="no-image-sm">
                                                    <i class="fas fa-box"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="product-sku"><?php echo $product['sku']; ?></span>
                                    </td>
                                    <td><?php echo $product['category_name'] ?? 'Uncategorized'; ?></td>
                                    <td>
                                        <span class="product-price">$<?php echo number_format($product['unit_price'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="stock-badge <?php echo $product['quantity_in_stock'] > 10 ? 'stock-good' : ($product['quantity_in_stock'] > 0 ? 'stock-low' : 'stock-out'); ?>">
                                            <?php echo $product['quantity_in_stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                            <i class="fas fa-<?php echo $product['is_active'] ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" 
                                               class="btn-action edit" title="Edit Product">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="toggle_product.php?id=<?php echo $product['product_id']; ?>&status=<?php echo $product['is_active'] ? '0' : '1'; ?>" 
                                               class="btn-action <?php echo $product['is_active'] ? 'warning' : 'success'; ?>" 
                                               title="<?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                               onclick="return confirm('<?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; ?> this product?')">
                                                <i class="fas fa-<?php echo $product['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </a>
                                            <a href="delete_product.php?id=<?php echo $product['product_id']; ?>" 
                                               class="btn-action delete" title="Delete Product" 
                                               onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-box-open" style="font-size: 3rem; color: var(--light-gray);"></i>
                                        <p class="mt-3 mb-0">No products found</p>
                                        <p class="small text-muted">Click "Add New Product" to create your first product</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="table-footer">
                    <nav aria-label="Products pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>">
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

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Page-specific scripts -->
<script>
// Live search functionality
document.getElementById('productSearch')?.addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#productsTable tbody tr');
    
    tableRows.forEach(row => {
        const productName = row.querySelector('.product-name')?.textContent.toLowerCase() || '';
        const productSku = row.querySelector('.product-sku')?.textContent.toLowerCase() || '';
        const category = row.cells[4]?.textContent.toLowerCase() || '';
        
        if (productName.includes(searchValue) || productSku.includes(searchValue) || category.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<style>
/* ===== PRODUCTS PAGE SPECIFIC STYLES ===== */

/* Status Cards - MATCHING YOUR EXISTING SMALL CARD STYLE */
.status-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1rem 0.75rem;
    transition: var(--transition);
    height: 100%;
    box-shadow: var(--shadow-sm);
}

.status-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.status-card.all {
    border-left: 4px solid var(--primary);
}

.status-card.active {
    border-left: 4px solid var(--success);
}

.status-card.inactive {
    border-left: 4px solid var(--danger);
}

.status-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    background: var(--light);
}

.status-card.all .status-icon {
    background: rgba(30,58,95,0.1);
    color: var(--primary);
}

.status-details {
    flex: 1;
}

.status-label {
    display: block;
    font-size: 0.75rem;
    color: var(--dark-gray);
    margin-bottom: 0.1rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.status-count {
    display: block;
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.2;
}

/* Filter Tabs - SMALLER LIKE YOUR EXISTING DESIGN */
.filter-tabs {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    box-shadow: var(--shadow-sm);
}

.nav-pills {
    gap: 0.5rem;
}

.nav-pills .nav-link {
    color: var(--dark);
    border-radius: 20px;
    padding: 0.4rem 1rem;
    font-size: 0.9rem;
    font-weight: 500;
    transition: var(--transition);
}

.nav-pills .nav-link:hover {
    background: var(--light);
    color: var(--primary);
}

.nav-pills .nav-link.active {
    background: var(--primary);
    color: white;
}

/* Table row for inactive products */
.table-inactive {
    background-color: rgba(0,0,0,0.02);
    opacity: 0.9;
}

.table-inactive:hover {
    background-color: rgba(0,0,0,0.04) !important;
}

.table-inactive .product-name,
.table-inactive .product-sku,
.table-inactive .product-price {
    opacity: 0.8;
}

/* Products Table */
.products-table {
    margin: 0;
}

.products-table thead th {
    background: var(--light);
    color: var(--dark);
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
    padding: 1rem;
    white-space: nowrap;
}

.products-table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--border);
}

.products-table tbody tr:hover {
    background: var(--light);
}

/* Product elements - keep your existing styles */
.product-id {
    font-weight: 600;
    color: var(--primary);
    font-family: var(--font-mono);
}

.product-image-sm {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: var(--light);
}

.product-image-sm img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image-sm {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--light);
    color: var(--dark-gray);
    font-size: 1.5rem;
}

.product-info {
    line-height: 1.4;
}

.product-name {
    font-weight: 600;
    color: var(--dark);
}

.product-sku {
    font-family: var(--font-mono);
    color: var(--dark-gray);
    font-size: 0.85rem;
}

.product-price {
    font-weight: 600;
    color: var(--success);
    font-family: var(--font-mono);
}

.stock-badge {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.stock-good {
    background: #d1e7dd;
    color: #0a3622;
}

.stock-low {
    background: #fff3cd;
    color: #856404;
}

.stock-out {
    background: #f8d7da;
    color: #842029;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-active {
    background: #d1e7dd;
    color: #0a3622;
}

.status-inactive {
    background: #f8d7da;
    color: #842029;
}

.status-active i {
    color: var(--success);
}

.status-inactive i {
    color: var(--danger);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: white;
    color: var(--dark-gray);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
}

.btn-action:hover {
    background: var(--light);
    transform: translateY(-2px);
}

.btn-action.edit:hover {
    color: var(--warning);
    border-color: var(--warning);
}

.btn-action.success:hover {
    color: var(--success);
    border-color: var(--success);
}

.btn-action.warning:hover {
    color: var(--danger);
    border-color: var(--danger);
}

.btn-action.delete:hover {
    color: var(--danger);
    border-color: var(--danger);
}

/* Table Header */
.table-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.table-title h5 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
    font-size: 1rem;
}

.records-count {
    font-size: 0.8rem;
    color: var(--dark-gray);
    margin-left: 0.5rem;
}

/* Table Footer */
.table-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border);
    background: var(--light);
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

.breadcrumb-item.active {
    color: var(--dark-gray);
}

/* Responsive */
@media (max-width: 768px) {
    .status-card {
        padding: 0.75rem;
    }
    
    .status-icon {
        width: 35px;
        height: 35px;
        font-size: 1.1rem;
    }
    
    .status-count {
        font-size: 1.1rem;
    }
    
    .nav-pills .nav-link {
        padding: 0.3rem 0.7rem;
        font-size: 0.8rem;
    }
    
    .product-image-sm {
        width: 40px;
        height: 40px;
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
    .page-title {
        font-size: 1.5rem;
    }
    
    .table-header {
        padding: 0.75rem 1rem;
    }
    
    .table-actions input {
        width: 100% !important;
    }
}
</style>