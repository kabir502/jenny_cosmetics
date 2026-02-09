<?php
// products.php - Products listing page

// Include session handler
require_once 'session_handler.php';

// Include database
require_once 'config/database.php';

// Initialize variables
$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // Products per page
$offset = ($page - 1) * $limit;

// Get categories for sidebar
$categories = [];
$categories_query = "SELECT c.category_id, c.category_name, COUNT(p.product_id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1
                     WHERE c.is_active = 1 
                     GROUP BY c.category_id 
                     ORDER BY c.category_name";
$categories_result = mysqli_query($connection, $categories_query);
if ($categories_result) {
    while ($cat = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $cat;
    }
}

// Build search query
$where_conditions = ["p.is_active = 1"];
$params = [];

if (!empty($search)) {
    $search_term = mysqli_real_escape_string($connection, $search);
    $where_conditions[] = "(p.product_name LIKE '%$search_term%' OR 
                           p.description LIKE '%$search_term%' OR 
                           p.short_description LIKE '%$search_term%')";
}

if ($category_id > 0) {
    $category_id = (int)$category_id;
    $where_conditions[] = "p.category_id = $category_id";
}

// Get category name if filtering by category
$category_name = '';
if ($category_id > 0) {
    $cat_query = "SELECT category_name FROM categories WHERE category_id = $category_id";
    $cat_result = mysqli_query($connection, $cat_query);
    if ($cat_result && mysqli_num_rows($cat_result) > 0) {
        $category_name = mysqli_fetch_assoc($cat_result)['category_name'];
    }
}

// Get products count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE " . implode(' AND ', $where_conditions);
$count_result = mysqli_query($connection, $count_query);
$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$products_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE " . implode(' AND ', $where_conditions) . "
                   ORDER BY p.product_name 
                   LIMIT $limit OFFSET $offset";
$products_result = mysqli_query($connection, $products_query);

$products = [];
if ($products_result) {
    while ($product = mysqli_fetch_assoc($products_result)) {
        $products[] = $product;
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row">
    <!-- Sidebar with Categories -->
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Categories</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item <?php echo $category_id == 0 ? 'active' : ''; ?>">
                        <a href="products.php" class="text-decoration-none d-block">
                            <i class="fas fa-th-large me-2"></i>All Products
                            <span class="badge bg-primary float-end"><?php echo $total_products; ?></span>
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                    <li class="list-group-item <?php echo $cat['category_id'] == $category_id ? 'active' : ''; ?>">
                        <a href="products.php?category=<?php echo $cat['category_id']; ?>" 
                           class="text-decoration-none d-block">
                            <i class="fas fa-folder me-2"></i><?php echo htmlspecialchars($cat['category_name']); ?>
                            <span class="badge bg-primary float-end"><?php echo $cat['product_count']; ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <!-- Search Filter -->
        <div class="card mt-3">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Products</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="products.php" class="row g-2">
                    <?php if ($category_id > 0): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Sort by</label>
                        <select name="sort" class="form-select">
                            <option value="name">Name (A-Z)</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="newest">Newest First</option>
                            <option value="popular">Most Popular</option>
                        </select>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="col-md-9">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <?php 
                            if ($category_name) {
                                echo htmlspecialchars($category_name) . " Products";
                            } elseif ($search) {
                                echo "Search Results for: \"" . htmlspecialchars($search) . "\"";
                            } else {
                                echo "All Products";
                            }
                            ?>
                        </h2>
                        <p class="text-muted mb-0">
                            <?php echo $total_products; ?> product<?php echo $total_products != 1 ? 's' : ''; ?> found
                        </p>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <?php if (empty($products)): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php if ($search): ?>
                        No products found matching your search. <a href="products.php">View all products</a>
                    <?php elseif ($category_id > 0): ?>
                        No products found in this category. <a href="products.php">Browse all categories</a>
                    <?php else: ?>
                        No products available at the moment. Please check back later.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100 product-card shadow-sm">
                    <!-- Product Image -->
                    <div class="position-relative">
                        <?php if ($product['image_url']): ?>
                        <img src="<?php echo $product['image_url']; ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                             style="height: 200px;">
                            <i class="fas fa-image fa-3x text-secondary"></i>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Stock Status Badge -->
                        <?php if ($product['quantity_in_stock'] <= 0): ?>
                        <span class="position-absolute top-0 start-0 badge bg-danger m-2">
                            Out of Stock
                        </span>
                        <?php elseif ($product['quantity_in_stock'] < 10): ?>
                        <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2">
                            Low Stock
                        </span>
                        <?php endif; ?>
                        
                        <!-- Featured Badge -->
                        <?php if ($product['is_featured']): ?>
                        <span class="position-absolute top-0 end-0 badge bg-success m-2">
                            <i class="fas fa-star me-1"></i>Featured
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <!-- Category -->
                        <p class="card-text mb-1">
                            <small class="text-muted">
                                <i class="fas fa-tag me-1"></i>
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </small>
                        </p>
                        
                        <!-- Product Name -->
                        <h6 class="card-title">
                            <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" 
                               class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </a>
                        </h6>
                        
                        <!-- Short Description -->
                        <?php if ($product['short_description']): ?>
                        <p class="card-text small text-muted">
                            <?php echo substr(htmlspecialchars($product['short_description']), 0, 60); ?>
                            <?php if (strlen($product['short_description']) > 60): ?>...<?php endif; ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Price -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="h5 text-primary mb-0">
                                    $<?php echo number_format($product['unit_price'], 2); ?>
                                </span>
                                <?php if ($product['cost_price'] && $product['cost_price'] > 0): ?>
                                <br>
                                <small class="text-muted">
                                    Cost: $<?php echo number_format($product['cost_price'], 2); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($product['rating'] > 0): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-star me-1"></i><?php echo number_format($product['rating'], 1); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer with Actions -->
                    <div class="card-footer bg-white border-top-0 pt-0">
                        <div class="d-grid gap-2">
                            <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                            
                            <?php if ($product['quantity_in_stock'] > 0): ?>
                            <form action="cart.php" method="GET" class="d-grid">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="id" value="<?php echo $product['product_id']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                </button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled>
                                <i class="fas fa-cart-plus me-1"></i>Out of Stock
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Button -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="products.php?<?php 
                                    echo buildQueryString(['page' => $page - 1]); 
                               ?>" 
                               aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" 
                               href="products.php?<?php 
                                    echo buildQueryString(['page' => $i]); 
                               ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="products.php?<?php 
                                    echo buildQueryString(['page' => $page + 1]); 
                               ?>" 
                               aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
        
        <!-- Quick Stats -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Quick Stats</h5>
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="text-primary"><?php echo $total_products; ?></h3>
                                    <p class="mb-0">Total Products</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="text-success"><?php echo count($categories); ?></h3>
                                    <p class="mb-0">Categories</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <?php
                                    // Count products in stock
                                    $in_stock_query = "SELECT COUNT(*) as in_stock FROM products WHERE quantity_in_stock > 0";
                                    $in_stock_result = mysqli_query($connection, $in_stock_query);
                                    $in_stock = mysqli_fetch_assoc($in_stock_result)['in_stock'];
                                    ?>
                                    <h3 class="text-info"><?php echo $in_stock; ?></h3>
                                    <p class="mb-0">In Stock</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <?php
                                    // Count featured products
                                    $featured_query = "SELECT COUNT(*) as featured FROM products WHERE is_featured = 1";
                                    $featured_result = mysqli_query($connection, $featured_query);
                                    $featured = mysqli_fetch_assoc($featured_result)['featured'];
                                    ?>
                                    <h3 class="text-warning"><?php echo $featured; ?></h3>
                                    <p class="mb-0">Featured</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to build query string for pagination
function buildQueryString($new_params = []) {
    $params = $_GET;
    unset($params['page']); // Remove old page parameter
    
    // Merge new parameters
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    
    return http_build_query($params);
}

// Include footer
include 'includes/footer.php';
?>