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

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total products count
$count_query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
$count_result = mysqli_query($connection, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get products with categories
$query = "SELECT p.*, c.category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE p.is_active = 1 
          ORDER BY p.created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($connection, $query);

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
                                <tr>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
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
/* Products Table Specific Styles */
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

/* Table Card */
.table-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.table-header {
    padding: 1.25rem 1.5rem;
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
}

.records-count {
    font-size: 0.85rem;
    color: var(--dark-gray);
    margin-left: 1rem;
}

.table-actions {
    display: flex;
    gap: 0.5rem;
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

.btn-action.delete:hover {
    color: var(--danger);
    border-color: var(--danger);
}

/* Pagination */
.pagination {
    margin: 0;
}

.page-link {
    border: 1px solid var(--border);
    color: var(--dark);
    padding: 0.5rem 1rem;
    margin: 0 0.2rem;
    border-radius: 8px;
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

.table-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border);
    background: var(--light);
}
</style>