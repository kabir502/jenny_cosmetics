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
            <li class="breadcrumb-item">
                <a href="products.php?category=<?php echo $product['category_id']; ?>">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </a>
            </li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars($product['product_name']); ?>
            </li>
        </ol>
    </nav>

    <!-- Product Details -->
    <div class="row">
        <!-- Product Image Gallery -->
        <div class="col-lg-6 mb-4">
            <div class="product-gallery">
                <div class="main-image">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                             class="img-fluid" id="mainProductImage">
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fas fa-gem"></i>
                            <p>No image available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-6 mb-4">
            <div class="product-detail-card">
                <!-- Category & Badges -->
                <div class="product-meta">
                    <span class="category-badge">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                    </span>
                    <?php if ($product['is_featured']): ?>
                        <span class="featured-badge">
                            <i class="fas fa-crown"></i> Featured
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Product Name -->
                <h1 class="product-detail-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>

                <!-- SKU -->
                <div class="product-sku">
                    <span class="sku-label">SKU:</span>
                    <span class="sku-value"><?php echo htmlspecialchars($product['sku']); ?></span>
                </div>

                <!-- Price -->
                <div class="product-price-section">
                    <div class="current-price">
                        <span class="currency">$</span>
                        <span class="amount"><?php echo number_format($product['unit_price'], 2); ?></span>
                    </div>
                    <?php if (!empty($product['cost_price']) && $product['cost_price'] > 0): ?>
                        <div class="original-price">
                            <span class="original">$<?php echo number_format($product['cost_price'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Short Description -->
                <?php if (!empty($product['short_description'])): ?>
                    <div class="short-description">
                        <p><?php echo nl2br(htmlspecialchars($product['short_description'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Stock Status -->
                <div class="stock-status">
                    <h5>Availability</h5>
                    <?php if ($product['quantity_in_stock'] > 0): ?>
                        <div class="in-stock">
                            <i class="fas fa-check-circle"></i>
                            <span>In Stock (<?php echo $product['quantity_in_stock']; ?> available)</span>
                        </div>
                    <?php else: ?>
                        <div class="out-of-stock">
                            <i class="fas fa-times-circle"></i>
                            <span>Out of Stock</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="product-actions">
                    <?php if (!$product['is_featured'] && $product['quantity_in_stock'] > 0): ?>
                        <!-- Quantity Selector -->
                        <div class="quantity-selector-wrapper">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn minus" onclick="decrementQuantity()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="quantity" class="qty-input" 
                                       value="1" min="1" max="<?php echo min(10, $product['quantity_in_stock']); ?>" 
                                       readonly>
                                <button type="button" class="qty-btn plus" 
                                        onclick="incrementQuantity(<?php echo min(10, $product['quantity_in_stock']); ?>)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <small>Max: <?php echo min(10, $product['quantity_in_stock']); ?> per order</small>
                        </div>

                        <!-- Add to Cart Button -->
                        <a href="cart.php?action=add&id=<?php echo $product['product_id']; ?>&quantity=1" 
                           class="btn-add-to-cart" id="addToCartBtn">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart
                        </a>
                    <?php elseif ($product['is_featured']): ?>
                        <div class="featured-message">
                            <i class="fas fa-crown"></i>
                            <p>This featured item is for display only. Visit our home page to explore more.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Wishlist Button -->
                    <?php if (file_exists('wishlist.php')): ?>
                        <a href="wishlist.php?action=add&id=<?php echo $product['product_id']; ?>" 
                           class="btn-wishlist"
                           onclick="return confirm('Add to wishlist?')">
                            <i class="fas fa-heart"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Share Buttons -->
                <div class="share-section">
                    <p class="share-title">Share this masterpiece:</p>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           target="_blank" class="share-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($product['product_name']); ?>" 
                           target="_blank" class="share-btn twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&media=<?php echo urlencode($product['image_url'] ?? ''); ?>&description=<?php echo urlencode($product['product_name']); ?>" 
                           target="_blank" class="share-btn pinterest">
                            <i class="fab fa-pinterest"></i>
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode($product['product_name']); ?>&body=<?php echo urlencode('Check out this product: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                           class="share-btn email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Description -->
    <?php if (!empty($product['description'])): ?>
    <div class="row mt-5">
        <div class="col-12">
            <div class="description-card">
                <h3 class="description-title">
                    <i class="fas fa-gem"></i>
                    Product Description
                </h3>
                <div class="description-content">
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
            <div class="section-header text-center mb-5">
                <span class="section-subtitle">Complete Your Collection</span>
                <h2 class="section-title">You May Also Love</h2>
                <div class="section-divider">
                    <span class="diamond"><i class="fas fa-gem"></i></span>
                </div>
            </div>
        </div>
        
        <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
        <div class="col-md-3 col-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
            <div class="product-card-luxury">
                <div class="product-image">
                    <?php if (!empty($related['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($related['product_name']); ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-gem"></i>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($related['is_featured']): ?>
                        <span class="featured-tag">
                            <i class="fas fa-crown"></i>
                        </span>
                    <?php endif; ?>
                    
                    <div class="product-overlay">
                        <a href="product_detail.php?id=<?php echo $related['product_id']; ?>" class="btn-view">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?php echo htmlspecialchars($related['product_name']); ?></h3>
                    <div class="product-price">
                        <span class="currency">$</span>
                        <span class="amount"><?php echo number_format($related['unit_price'], 2); ?></span>
                    </div>
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
    const addToCartBtn = document.getElementById('addToCartBtn');
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
/* ===== PRODUCT DETAIL PAGE STYLES ===== */
:root {
    --gold: #D4AF37;
    --gold-light: #F4E5C1;
    --gold-dark: #AA8C2F;
    --navy: #1A2A4F;
    --navy-light: #2A3F6F;
    --pearl: #F8F6F0;
    --charcoal: #36454F;
    --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

/* Breadcrumb */
.breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 2rem;
}

.breadcrumb-item a {
    color: var(--navy);
    text-decoration: none;
    font-weight: 500;
    position: relative;
    padding: 0.2rem 0;
}

.breadcrumb-item a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 1px;
    background: var(--gold);
    transition: var(--transition);
}

.breadcrumb-item a:hover::after {
    width: 100%;
}

.breadcrumb-item.active {
    color: var(--gold);
    font-weight: 600;
}

.breadcrumb-item + .breadcrumb-item::before {
    color: var(--gold);
    content: "•";
}

/* Product Gallery */
.product-gallery {
    background: white;
    border-radius: 30px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    position: relative;
    overflow: hidden;
}

.product-gallery::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(212,175,55,0.05) 0%, transparent 70%);
    animation: rotate 30s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.main-image {
    text-align: center;
    position: relative;
    z-index: 2;
}

.main-image img {
    max-height: 400px;
    object-fit: contain;
    transition: var(--transition);
}

.main-image:hover img {
    transform: scale(1.05);
}

.no-image-placeholder {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--pearl);
    border-radius: 20px;
}

.no-image-placeholder i {
    font-size: 5rem;
    color: var(--gold);
    margin-bottom: 1rem;
    animation: sparkle 2s infinite;
}

/* Product Detail Card */
.product-detail-card {
    background: white;
    border-radius: 30px;
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    height: 100%;
}

.product-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.category-badge {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 50px;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.category-badge i {
    color: var(--gold);
}

.featured-badge {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    padding: 0.5rem 1.5rem;
    border-radius: 50px;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.featured-badge i {
    animation: sparkle 2s infinite;
}

.product-detail-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 1rem;
    line-height: 1.2;
}

.product-sku {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.sku-label {
    color: var(--charcoal);
    font-weight: 600;
}

.sku-value {
    color: var(--gold);
    font-family: monospace;
    font-weight: 500;
}

.product-price-section {
    margin-bottom: 2rem;
    display: flex;
    align-items: baseline;
    gap: 1rem;
}

.current-price {
    display: flex;
    align-items: baseline;
    gap: 0.2rem;
}

.current-price .currency {
    font-size: 1.5rem;
    color: var(--gold);
    font-weight: 600;
}

.current-price .amount {
    font-size: 3rem;
    font-weight: 800;
    color: var(--navy);
    line-height: 1;
}

.original-price .original {
    color: var(--charcoal);
    text-decoration: line-through;
    font-size: 1.2rem;
    opacity: 0.7;
}

.short-description {
    background: var(--pearl);
    padding: 1.5rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    border-left: 4px solid var(--gold);
}

.short-description p {
    margin: 0;
    color: var(--charcoal);
    font-size: 1.1rem;
    line-height: 1.6;
    font-family: 'Cormorant Garamond', serif;
}

.stock-status {
    margin-bottom: 2rem;
}

.stock-status h5 {
    color: var(--navy);
    margin-bottom: 1rem;
    font-weight: 600;
}

.in-stock {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #2A6230;
    background: #E8F5E9;
    padding: 1rem;
    border-radius: 10px;
}

.in-stock i {
    font-size: 1.2rem;
    color: #4CAF50;
}

.out-of-stock {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #C41E3A;
    background: #FFEBEE;
    padding: 1rem;
    border-radius: 10px;
}

.out-of-stock i {
    font-size: 1.2rem;
    color: #F44336;
}

.product-actions {
    margin-bottom: 2rem;
}

.quantity-selector-wrapper {
    margin-bottom: 1.5rem;
}

.quantity-selector-wrapper label {
    display: block;
    color: var(--navy);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    max-width: 150px;
    background: var(--pearl);
    border-radius: 50px;
    overflow: hidden;
    border: 1px solid rgba(212,175,55,0.3);
}

.qty-btn {
    width: 40px;
    height: 40px;
    background: transparent;
    border: none;
    color: var(--gold);
    font-size: 1rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover {
    background: var(--gold);
    color: var(--navy);
}

.qty-input {
    width: 60px;
    height: 40px;
    border: none;
    text-align: center;
    font-weight: 600;
    color: var(--navy);
    background: transparent;
}

.qty-input:focus {
    outline: none;
}

.btn-add-to-cart {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
    color: var(--navy);
    text-decoration: none;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1.1rem;
    border: none;
    width: 100%;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    margin-bottom: 1rem;
}

.btn-add-to-cart::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-add-to-cart:hover::before {
    width: 300px;
    height: 300px;
}

.btn-add-to-cart:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
}

.btn-add-to-cart i {
    font-size: 1.2rem;
}

.btn-wishlist {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 1rem;
    background: transparent;
    color: var(--gold);
    border: 2px solid var(--gold);
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
}

.btn-wishlist:hover {
    background: var(--gold);
    color: var(--navy);
    transform: translateY(-3px);
}

.featured-message {
    background: linear-gradient(135deg, #FFF9E6, #FFF3CD);
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
    border: 2px solid var(--gold);
    margin-bottom: 1rem;
}

.featured-message i {
    font-size: 3rem;
    color: var(--gold);
    margin-bottom: 1rem;
    animation: sparkle 2s infinite;
}

.featured-message p {
    color: var(--navy);
    font-size: 1.1rem;
    margin: 0;
    font-family: 'Cormorant Garamond', serif;
}

/* Share Section */
.share-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.share-title {
    color: var(--charcoal);
    margin-bottom: 1rem;
    font-weight: 500;
}

.share-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.share-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: var(--transition);
}

.share-btn.facebook {
    background: #1877F2;
    color: white;
}

.share-btn.twitter {
    background: #1DA1F2;
    color: white;
}

.share-btn.pinterest {
    background: #E60023;
    color: white;
}

.share-btn.email {
    background: var(--navy);
    color: white;
}

.share-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* Description Card */
.description-card {
    background: white;
    border-radius: 30px;
    padding: 3rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    position: relative;
    overflow: hidden;
}

.description-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, var(--gold) 0%, transparent 100%);
}

.description-title {
    font-family: 'Playfair Display', serif;
    color: var(--navy);
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.description-title i {
    color: var(--gold);
    animation: sparkle 2s infinite;
}

.description-content {
    color: var(--charcoal);
    line-height: 1.8;
    font-size: 1.1rem;
}

/* Related Products Section */
.section-header {
    position: relative;
    margin-bottom: 3rem;
}

.section-subtitle {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
    color: var(--gold);
    text-transform: uppercase;
    letter-spacing: 3px;
    display: block;
    margin-bottom: 0.5rem;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 1rem;
}

.section-divider {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
}

.diamond {
    font-size: 1.5rem;
    color: var(--gold);
    animation: sparkle 2s infinite;
}

/* Product Card for Related */
.product-card-luxury {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: var(--transition);
    position: relative;
    height: 100%;
}

.product-card-luxury:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 30px rgba(212, 175, 55, 0.2);
}

.product-image {
    position: relative;
    padding-top: 100%;
    overflow: hidden;
}

.product-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.no-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--pearl);
}

.no-image i {
    font-size: 3rem;
    color: var(--gold);
    opacity: 0.5;
}

.featured-tag {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    animation: sparkle 2s infinite;
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(26, 42, 79, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: var(--transition);
    z-index: 2;
}

.product-card-luxury:hover .product-overlay {
    opacity: 1;
}

.btn-view {
    width: 50px;
    height: 50px;
    background: var(--gold);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--navy);
    text-decoration: none;
    transform: scale(0);
    transition: var(--transition);
}

.product-card-luxury:hover .btn-view {
    transform: scale(1);
}

.btn-view:hover {
    background: white;
    color: var(--gold);
}

.product-info {
    padding: 1.5rem;
    text-align: center;
}

.product-name {
    font-family: 'Playfair Display', serif;
    font-size: 1rem;
    font-weight: 600;
    color: var(--navy);
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.product-price {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.2rem;
}

.product-price .currency {
    font-size: 0.8rem;
    color: var(--gold);
}

.product-price .amount {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--navy);
}

/* Responsive */
@media (max-width: 768px) {
    .product-detail-title {
        font-size: 2rem;
    }
    
    .current-price .amount {
        font-size: 2.5rem;
    }
    
    .description-card {
        padding: 2rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .product-detail-card {
        padding: 1.5rem;
    }
    
    .product-detail-title {
        font-size: 1.8rem;
    }
    
    .product-meta {
        flex-direction: column;
    }
    
    .share-buttons {
        justify-content: center;
    }
    
    .quantity-controls {
        max-width: 100%;
    }
    
    .btn-add-to-cart {
        padding: 0.8rem;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>