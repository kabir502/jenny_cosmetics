<?php
// index.php - Main landing page

// Include required files
require_once 'session_handler.php';
require_once 'config/database.php';

// Get featured products
$featured_products = [];
if ($connection) {
    $query = "SELECT * FROM products WHERE is_featured = 1 AND is_active = 1 LIMIT 6";
    $result = mysqli_query($connection, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $featured_products[] = $row;
        }
    }
}

// Get categories
$categories = [];
if ($connection) {
    $query = "SELECT * FROM categories WHERE is_active = 1 LIMIT 4";
    $result = mysqli_query($connection, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row mb-5">
    <div class="col-12">
        <div class="hero-section bg-primary text-white p-5 rounded-3">
            <h1 class="display-4 fw-bold">Welcome to <?php echo SITE_NAME; ?></h1>
            <p class="lead">Discover amazing cosmetics and imitation jewelry at unbeatable prices.</p>
            <a href="products.php" class="btn btn-light btn-lg mt-3">Start Shopping</a>
        </div>
    </div>
</div>

<!-- Featured Products -->
<?php if (!empty($featured_products)): ?>
<div class="row mb-5">
    <div class="col-12">
        <h2 class="mb-4 border-bottom pb-2">Featured Products</h2>
        <div class="row">
            <?php foreach ($featured_products as $product): ?>
            <div class="col-md-4 col-lg-2 mb-4">
                <div class="card h-100 product-card">
                    <?php if (!empty($product['image_url'])): ?>
                    <img src="<?php echo $product['image_url']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                         style="height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                        <p class="card-text text-primary fw-bold">
                            $<?php echo number_format($product['unit_price'], 2); ?>
                        </p>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0">
                        <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" 
                           class="btn btn-sm btn-outline-primary w-100">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Categories -->
<?php if (!empty($categories)): ?>
<div class="row mb-5">
    <div class="col-12">
        <h2 class="mb-4 border-bottom pb-2">Shop by Category</h2>
        <div class="row">
            <?php foreach ($categories as $category): ?>
            <div class="col-md-3 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($category['category_name']); ?></h5>
                        <p class="card-text text-muted"><?php echo substr($category['description'] ?? '', 0, 100); ?>...</p>
                        <a href="products.php?category=<?php echo $category['category_id']; ?>" 
                           class="btn btn-primary">Browse</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Welcome Message -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Why Choose Us?</h3>
                <div class="row mt-4">
                    <div class="col-md-3 text-center mb-3">
                        <i class="fas fa-shipping-fast fa-2x text-primary mb-2"></i>
                        <h5>Free Shipping</h5>
                        <p class="text-muted">On orders over $50</p>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <i class="fas fa-undo-alt fa-2x text-primary mb-2"></i>
                        <h5>30-Day Returns</h5>
                        <p class="text-muted">Easy return policy</p>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <i class="fas fa-lock fa-2x text-primary mb-2"></i>
                        <h5>Secure Payment</h5>
                        <p class="text-muted">100% secure transactions</p>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <i class="fas fa-headset fa-2x text-primary mb-2"></i>
                        <h5>24/7 Support</h5>
                        <p class="text-muted">Dedicated customer service</p>
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