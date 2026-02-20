<?php
// product_detail.php - Product details page

// Include session handler
require_once 'session_handler.php';

// Include database
require_once 'config/database.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: products.php");
    exit();
}

// Get product details
$product_query = "SELECT p.*, c.category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  WHERE p.product_id = ? AND p.is_active = 1";
$product_stmt = mysqli_prepare($connection, $product_query);
mysqli_stmt_bind_param($product_stmt, "i", $product_id);
mysqli_stmt_execute($product_stmt);
$product_result = mysqli_stmt_get_result($product_stmt);

if (mysqli_num_rows($product_result) == 0) {
    header("Location: products.php?error=Product not found");
    exit();
}

$product = mysqli_fetch_assoc($product_result);

// Get related products (same category, excluding current product)
$related_query = "SELECT product_id, product_name, unit_price, image_url, is_featured 
                  FROM products 
                  WHERE category_id = ? AND product_id != ? AND is_active = 1 
                  LIMIT 4";
$related_stmt = mysqli_prepare($connection, $related_query);
mysqli_stmt_bind_param($related_stmt, "ii", $product['category_id'], $product_id);
mysqli_stmt_execute($related_stmt);
$related_result = mysqli_stmt_get_result($related_stmt);

// Include header
include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
           
            <?php if (!empty($product['category_name'])): ?>
            <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['product_name']); ?></li>
        </ol>
    </nav>

    <!-- Product Details -->
    <div class="row">
        <!-- Product Image -->
        <div class="col-md-5 mb-4">
            <div class="card">
                <div class="card-body text-center p-4">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                             class="img-fluid rounded" style="max-height: 400px; object-fit: contain;">
                    <?php else: ?>
                        <div class="no-image-placeholder p-5 bg-light rounded">
                            <i class="fas fa-image fa-5x text-muted"></i>
                            <p class="mt-3 text-muted">No image available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-md-7 mb-4">
            <div class="card">
                <div class="card-body p-4">
                    <!-- Category Badge -->
                    <div class="mb-3">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                        <?php if ($product['is_featured']): ?>
                            <span class="badge bg-warning text-dark ms-2">
                                <i class="fas fa-star me-1"></i>Featured
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Product Name -->
                    <h1 class="h2 mb-3"><?php echo htmlspecialchars($product['product_name']); ?></h1>

                    <!-- SKU -->
                    <p class="text-muted mb-3">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>

                    <!-- Price -->
                    <div class="mb-4">
                        <h3 class="text-primary mb-0">$<?php echo number_format($product['unit_price'], 2); ?></h3>
                        <?php if (!empty($product['cost_price']) && $product['cost_price'] > 0): ?>
                            <small class="text-muted">Retail price: $<?php echo number_format($product['cost_price'], 2); ?></small>
                        <?php endif; ?>
                    </div>

                    <!-- Short Description -->
                    <?php if (!empty($product['short_description'])): ?>
                        <div class="mb-4">
                            <h5>Quick Overview</h5>
                            <p class="lead"><?php echo nl2br(htmlspecialchars($product['short_description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Stock Status -->
                    <div class="mb-4">
                        <h5>Availability</h5>
                        <?php if ($product['quantity_in_stock'] > 0): ?>
                            <p class="text-success">
                                <i class="fas fa-check-circle me-2"></i>
                                In Stock (<?php echo $product['quantity_in_stock']; ?> available)
                            </p>
                        <?php else: ?>
                            <p class="text-danger">
                                <i class="fas fa-times-circle me-2"></i>
                                Out of Stock
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons - FIXED: Using your cart.php system -->
                    <div class="d-flex gap-2 mb-4">
                        <?php if (!$product['is_featured']): ?>
                            <!-- Regular Product - Show Add to Cart button linking to cart.php -->
                            <a href="cart.php?action=add&id=<?php echo $product['product_id']; ?>&quantity=1" 
                               class="btn btn-primary btn-lg flex-grow-1 
                               <?php echo $product['quantity_in_stock'] <= 0 ? 'disabled' : ''; ?>">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Add to Cart
                            </a>
                        <?php else: ?>
                            <!-- Featured Product - Show message instead -->
                            <button class="btn btn-secondary btn-lg flex-grow-1" disabled>
                                <i class="fas fa-star me-2"></i>
                                Featured Item - View Only
                            </button>
                        <?php endif; ?>
                        
                        <!-- Wishlist Button (if you have wishlist functionality) -->
                        <?php if (file_exists('wishlist.php')): ?>
                        <a href="wishlist.php?action=add&id=<?php echo $product['product_id']; ?>" 
                           class="btn btn-outline-danger btn-lg"
                           onclick="return confirm('Add to wishlist?')">
                            <i class="fas fa-heart"></i>
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Quantity Selector for non-featured products -->
                    <?php if (!$product['is_featured'] && $product['quantity_in_stock'] > 0): ?>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity:</label>
                        <div class="d-flex align-items-center" style="max-width: 150px;">
                            <button class="btn btn-outline-secondary" type="button" onclick="decrementQuantity()">-</button>
                            <input type="number" id="quantity" class="form-control text-center mx-2" value="1" min="1" max="<?php echo min(10, $product['quantity_in_stock']); ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="incrementQuantity(<?php echo min(10, $product['quantity_in_stock']); ?>)">+</button>
                        </div>
                        <small class="text-muted">Max: <?php echo min(10, $product['quantity_in_stock']); ?> per order</small>
                    </div>
                    <?php endif; ?>

                    <!-- Share Buttons -->
                    <div class="mt-3">
                        <p class="mb-2">Share this product:</p>
                        <div class="d-flex gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                               target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($product['product_name']); ?>" 
                               target="_blank" class="btn btn-outline-info btn-sm">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&media=<?php echo urlencode($product['image_url'] ?? ''); ?>&description=<?php echo urlencode($product['product_name']); ?>" 
                               target="_blank" class="btn btn-outline-danger btn-sm">
                                <i class="fab fa-pinterest"></i>
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode($product['product_name']); ?>&body=<?php echo urlencode('Check out this product: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                               class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-envelope"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Description -->
    <?php if (!empty($product['description'])): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Product Description</h5>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if (mysqli_num_rows($related_result) > 0): ?>
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="mb-4">Related Products</h3>
        </div>
        <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
        <div class="col-md-3 col-6 mb-4">
            <div class="card h-100">
                <div class="card-img-top text-center p-3 position-relative" style="height: 200px;">
                    <?php if ($related['is_featured']): ?>
                        <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
                            <i class="fas fa-star"></i> Featured
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($related['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($related['product_name']); ?>" 
                             style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    <?php else: ?>
                        <div class="h-100 d-flex align-items-center justify-content-center bg-light">
                            <i class="fas fa-image fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h6 class="card-title"><?php echo htmlspecialchars($related['product_name']); ?></h6>
                    <p class="card-text text-primary fw-bold">$<?php echo number_format($related['unit_price'], 2); ?></p>
                </div>
                <div class="card-footer bg-white border-0">
                    <a href="product_detail.php?id=<?php echo $related['product_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                        View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Quantity selector JavaScript -->
<script>
function incrementQuantity(max) {
    const input = document.getElementById('quantity');
    let value = parseInt(input.value) || 1;
    if (value < max) {
        input.value = value + 1;
        updateCartLink();
    }
}

function decrementQuantity() {
    const input = document.getElementById('quantity');
    let value = parseInt(input.value) || 1;
    if (value > 1) {
        input.value = value - 1;
        updateCartLink();
    }
}

function updateCartLink() {
    const quantity = document.getElementById('quantity').value;
    const addToCartBtn = document.querySelector('a[href*="cart.php?action=add"]');
    if (addToCartBtn) {
        const baseUrl = addToCartBtn.href.split('&quantity=')[0];
        addToCartBtn.href = baseUrl + '&quantity=' + quantity;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartLink();
});
</script>

<style>
/* Product Detail Page Styles */
.breadcrumb {
    background: transparent;
    padding: 0;
}

.breadcrumb-item a {
    color: var(--primary);
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
}

.no-image-placeholder {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Featured product badge */
.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

/* Disabled button for featured products */
.btn-secondary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Quantity selector */
.btn-outline-secondary {
    padding: 0.375rem 0.75rem;
}

#quantity {
    width: 70px;
    text-align: center;
}

/* Related products card */
.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.position-relative {
    position: relative;
}

@media (max-width: 768px) {
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
    
    #quantity {
        width: 50px;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>