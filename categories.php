<?php
// categories.php - Categories listing page

// Include session handler
require_once 'session_handler.php';

// Include database
require_once 'config/database.php';

// Get all categories
$categories_query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM products p 
                      WHERE p.category_id = c.category_id AND p.is_active = 1) as product_count
                     FROM categories c 
                     WHERE c.is_active = 1 
                     ORDER BY c.display_order, c.category_name";
$categories_result = mysqli_query($connection, $categories_query);

$categories = [];
if ($categories_result) {
    while ($category = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $category;
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-1">Product Categories</h1>
                <p class="text-muted mb-0">Browse our products by category</p>
            </div>
            <div>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-th-large me-2"></i>View All Products
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (empty($categories)): ?>
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No categories available at the moment.
        </div>
    </div>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($categories as $category): ?>
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <!-- Category Image -->
            <div class="category-image">
                <?php if ($category['image_url']): ?>
                <img src="<?php echo $category['image_url']; ?>" 
                     class="card-img-top" 
                     alt="<?php echo htmlspecialchars($category['category_name']); ?>"
                     style="height: 200px; object-fit: cover;">
                <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                     style="height: 200px;">
                    <i class="fas fa-folder fa-4x text-secondary"></i>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <!-- Category Name -->
                <h5 class="card-title">
                    <a href="products.php?category=<?php echo $category['category_id']; ?>" 
                       class="text-decoration-none text-dark">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </a>
                </h5>
                
                <!-- Product Count -->
                <p class="card-text">
                    <span class="badge bg-primary">
                        <?php echo $category['product_count']; ?> product<?php echo $category['product_count'] != 1 ? 's' : ''; ?>
                    </span>
                </p>
                
                <!-- Description -->
                <?php if (!empty($category['description'])): ?>
                <p class="card-text text-muted small">
                    <?php echo substr(htmlspecialchars($category['description']), 0, 100); ?>
                    <?php if (strlen($category['description']) > 100): ?>...<?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
            
            <!-- Card Footer -->
            <div class="card-footer bg-white border-top-0 pt-0">
                <a href="products.php?category=<?php echo $category['category_id']; ?>" 
                   class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-eye me-1"></i>Browse Products
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Statistics -->
<div class="row mt-5">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Category Statistics</h5>
                <div class="row text-center">
                    <div class="col-md-4 mb-3">
                        <div class="p-3 bg-light rounded">
                            <h3 class="text-primary"><?php echo count($categories); ?></h3>
                            <p class="mb-0">Total Categories</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 bg-light rounded">
                            <?php
                            // Get total products across all categories
                            $total_products_query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
                            $total_products_result = mysqli_query($connection, $total_products_query);
                            $total_products = mysqli_fetch_assoc($total_products_result)['total'];
                            ?>
                            <h3 class="text-success"><?php echo $total_products; ?></h3>
                            <p class="mb-0">Total Products</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 bg-light rounded">
                            <?php
                            // Get category with most products
                            $top_category_query = "SELECT c.category_name, COUNT(p.product_id) as product_count 
                                                   FROM categories c 
                                                   LEFT JOIN products p ON c.category_id = p.category_id 
                                                   WHERE p.is_active = 1 
                                                   GROUP BY c.category_id 
                                                   ORDER BY product_count DESC 
                                                   LIMIT 1";
                            $top_category_result = mysqli_query($connection, $top_category_query);
                            $top_category = mysqli_fetch_assoc($top_category_result);
                            ?>
                            <h3 class="text-warning"><?php echo $top_category['category_name'] ?? 'N/A'; ?></h3>
                            <p class="mb-0">Most Products (<?php echo $top_category['product_count'] ?? 0; ?>)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>