<?php
// cart.php - Shopping Cart with Luxury Theme

// Start session
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// Initialize cart session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart functionality
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    
    // Get product details
    $product_query = "SELECT product_id, product_name, unit_price, quantity_in_stock, image_url 
                      FROM products WHERE product_id = $product_id AND is_active = 1";
    $product_result = mysqli_query($connection, $product_query);
    
    if (mysqli_num_rows($product_result) > 0) {
        $product = mysqli_fetch_assoc($product_result);
        
        // Check stock availability
        if ($product['quantity_in_stock'] >= $quantity) {
            // Add to cart
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'name' => $product['product_name'],
                    'price' => $product['unit_price'],
                    'quantity' => $quantity,
                    'image' => $product['image_url']
                ];
            }
            
            header("Location: cart.php?success=Product added to cart");
            exit();
        } else {
            header("Location: cart.php?error=Insufficient stock available");
            exit();
        }
    }
}

// Remove from cart
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        header("Location: cart.php?success=Product removed from cart");
        exit();
    }
}

// Update quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;
        
        if ($quantity > 0) {
            // Check stock availability
            $stock_query = "SELECT quantity_in_stock FROM products WHERE product_id = $product_id";
            $stock_result = mysqli_query($connection, $stock_query);
            $stock = mysqli_fetch_assoc($stock_result);
            
            if ($stock['quantity_in_stock'] >= $quantity) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            } else {
                $_SESSION['cart'][$product_id]['quantity'] = $stock['quantity_in_stock'];
            }
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    header("Location: cart.php");
    exit();
}

// Include header
include 'includes/header.php';
?>

<style>
/* ===== CART PAGE LUXURY THEME ===== */
:root {
    --gold: #D4AF37;
    --gold-light: #F4E5C1;
    --gold-dark: #AA8C2F;
    --navy: #1A2A4F;
    --navy-light: #2A3F6F;
    --navy-dark: #0F1A2F;
    --pearl: #F8F6F0;
    --charcoal: #36454F;
    --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

/* Cart Container */
.cart-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* Page Header */
.page-header {
    text-align: center;
    margin-bottom: 3rem;
    position: relative;
}

.page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.8rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.page-header .header-decoration {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 0.5rem;
}

.page-header .decoration-icon {
    font-size: 1.5rem;
    color: var(--gold);
    animation: sparkle 2s infinite;
}

@keyframes sparkle {
    0%, 100% { 
        opacity: 1; 
        transform: scale(1); 
        text-shadow: 0 0 5px var(--gold);
    }
    50% { 
        opacity: 0.9; 
        transform: scale(1.1); 
        text-shadow: 0 0 20px var(--gold), 0 0 30px var(--gold-light);
    }
}

/* Alert Messages */
.alert-luxury {
    border: none;
    border-radius: 16px;
    padding: 1.2rem 1.8rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    animation: slideDown 0.5s ease;
    position: relative;
    overflow: hidden;
}

.alert-luxury::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
}

.alert-luxury-success {
    background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
    color: #1B5E20;
    border: 1px solid var(--gold-light);
}

.alert-luxury-success::before {
    background: var(--gold);
}

.alert-luxury-danger {
    background: linear-gradient(135deg, #FFEBEE, #FFCDD2);
    color: #B71C1C;
    border: 1px solid #FFCDD2;
}

.alert-luxury-danger::before {
    background: #C62828;
}

.alert-luxury i {
    font-size: 1.3rem;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    border: 1px solid rgba(212,175,55,0.2);
    max-width: 600px;
    margin: 2rem auto;
}

.empty-cart-icon {
    margin-bottom: 2rem;
}

.empty-cart-icon i {
    font-size: 6rem;
    color: var(--gold);
    animation: sparkle 2s infinite;
    opacity: 0.7;
}

.empty-cart h2 {
    font-family: 'Playfair Display', serif;
    font-size: 2.2rem;
    color: var(--navy);
    margin-bottom: 1rem;
}

.empty-cart p {
    color: var(--charcoal);
    font-size: 1.2rem;
    margin-bottom: 2rem;
    font-family: 'Cormorant Garamond', serif;
}

.btn-luxury {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    border: none;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    border: 1px solid transparent;
}

.btn-luxury::before {
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
    z-index: 1;
}

.btn-luxury:hover::before {
    width: 300px;
    height: 300px;
}

.btn-luxury:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 25px rgba(212,175,55,0.3);
    color: var(--navy);
}

.btn-luxury i {
    margin-right: 0.5rem;
    position: relative;
    z-index: 2;
}

/* Cart Table */
.cart-table-wrapper {
    background: white;
    border-radius: 30px;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    border: 1px solid rgba(212,175,55,0.2);
    margin-bottom: 2rem;
    overflow-x: auto;
}

.cart-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 1rem;
}

.cart-table thead th {
    font-family: 'Playfair Display', serif;
    font-size: 1rem;
    font-weight: 700;
    color: var(--navy);
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 1rem;
    background: var(--pearl);
    border-radius: 12px;
}

.cart-table tbody tr {
    background: var(--pearl);
    border-radius: 20px;
    transition: var(--transition);
}

.cart-table tbody tr:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(212,175,55,0.15);
}

.cart-table td {
    padding: 1.5rem 1rem;
    vertical-align: middle;
}

/* Product Info */
.product-info-cell {
    min-width: 300px;
}

.product-info {
    display: flex;
    align-items: center;
    gap: 1.2rem;
}

.product-image {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    overflow: hidden;
    border: 2px solid var(--gold-light);
    background: white;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-image-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--pearl), white);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--gold-light);
}

.product-image-placeholder i {
    font-size: 2rem;
    color: var(--gold);
    opacity: 0.5;
}

.product-details h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 0.3rem;
}

.product-details .product-id {
    color: var(--charcoal);
    font-size: 0.8rem;
    font-family: monospace;
    background: rgba(212,175,55,0.1);
    padding: 0.2rem 0.5rem;
    border-radius: 30px;
    display: inline-block;
}

/* Price Cell */
.price-cell {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--navy);
}

.price-cell .currency {
    color: var(--gold);
    font-size: 0.9rem;
    margin-right: 0.2rem;
}

/* Quantity Cell */
.quantity-cell {
    min-width: 120px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    background: white;
    border-radius: 50px;
    overflow: hidden;
    border: 2px solid var(--gold-light);
    max-width: 120px;
}

.quantity-btn {
    width: 36px;
    height: 36px;
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

.quantity-btn:hover {
    background: var(--gold);
    color: var(--navy);
}

.quantity-input {
    width: 48px;
    height: 36px;
    border: none;
    text-align: center;
    font-weight: 600;
    color: var(--navy);
    background: transparent;
    font-size: 0.95rem;
}

.quantity-input:focus {
    outline: none;
}

/* Total Cell */
.total-cell {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--navy);
}

.total-cell .currency {
    color: var(--gold);
    font-size: 1rem;
    margin-right: 0.2rem;
}

/* Remove Button */
.btn-remove {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: transparent;
    border: 2px solid #ffcdd2;
    color: #f44336;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: var(--transition);
}

.btn-remove:hover {
    background: #f44336;
    color: white;
    transform: rotate(90deg) scale(1.1);
    border-color: #f44336;
}

/* Cart Summary */
.cart-summary {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    border-radius: 30px;
    padding: 2rem;
    color: white;
    border: 1px solid var(--gold);
    margin-bottom: 2rem;
}

.summary-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.summary-title i {
    color: var(--gold);
    animation: sparkle 2s infinite;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.8rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.summary-row:last-of-type {
    border-bottom: none;
}

.summary-label {
    color: rgba(255,255,255,0.8);
    font-size: 1rem;
}

.summary-value {
    font-weight: 600;
    font-size: 1.1rem;
}

.summary-row.total {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid var(--gold);
}

.summary-row.total .summary-label {
    color: var(--gold);
    font-size: 1.2rem;
    font-weight: 700;
}

.summary-row.total .summary-value {
    color: var(--gold);
    font-size: 1.5rem;
    font-weight: 800;
}

/* Cart Actions */
.cart-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.btn-outline-luxury {
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    padding: 1rem 2rem;
    background: transparent;
    color: var(--navy);
    border: 2px solid var(--gold);
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
}

.btn-outline-luxury:hover {
    background: var(--gold);
    color: var(--navy);
    transform: translateY(-3px);
    box-shadow: 0 15px 25px rgba(212,175,55,0.3);
}

.btn-gold {
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    border: none;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.btn-gold::before {
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
    z-index: 1;
}

.btn-gold:hover::before {
    width: 300px;
    height: 300px;
}

.btn-gold:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 25px rgba(212,175,55,0.4);
}

.btn-gold i,
.btn-outline-luxury i {
    position: relative;
    z-index: 2;
}

/* Responsive */
@media (max-width: 991px) {
    .page-header h1 {
        font-size: 2.4rem;
    }
    
    .cart-table thead {
        display: none;
    }
    
    .cart-table tbody tr {
        display: block;
        margin-bottom: 1.5rem;
        padding: 1rem;
    }
    
    .cart-table td {
        display: block;
        padding: 0.8rem;
        border: none;
    }
    
    .product-info-cell {
        min-width: auto;
    }
    
    .product-info {
        flex-direction: column;
        text-align: center;
    }
    
    .product-details {
        text-align: center;
    }
    
    .price-cell,
    .quantity-cell,
    .total-cell,
    .action-cell {
        text-align: center;
    }
    
    .quantity-controls {
        margin: 0 auto;
    }
    
    .btn-remove {
        margin: 0 auto;
    }
    
    .cart-actions {
        flex-direction: column;
    }
    
    .btn-outline-luxury,
    .btn-gold {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .cart-summary {
        padding: 1.5rem;
    }
    
    .summary-title {
        font-size: 1.3rem;
    }
    
    .summary-row.total .summary-value {
        font-size: 1.3rem;
    }
}

@media (max-width: 576px) {
    .page-header h1 {
        font-size: 1.8rem;
    }
    
    .cart-table-wrapper {
        padding: 1rem;
    }
    
    .empty-cart {
        padding: 2rem 1rem;
    }
    
    .empty-cart h2 {
        font-size: 1.8rem;
    }
    
    .empty-cart p {
        font-size: 1rem;
    }
    
    .product-image,
    .product-image-placeholder {
        width: 60px;
        height: 60px;
    }
    
    .product-details h3 {
        font-size: 1rem;
    }
}
</style>

<div class="cart-container">
    <!-- Page Header -->
    <div class="page-header" data-aos="fade-up">
        <h1>Shopping Cart</h1>
        <div class="header-decoration">
            <i class="fas fa-gem decoration-icon"></i>
            <span class="decoration-line"></span>
            <i class="fas fa-gem decoration-icon"></i>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert-luxury alert-luxury-success" data-aos="fade-up">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert-luxury alert-luxury-danger" data-aos="fade-up">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <!-- Empty Cart -->
        <div class="empty-cart" data-aos="fade-up">
            <div class="empty-cart-icon">
                <i class="fas fa-gem"></i>
            </div>
            <h2>Your Cart is Empty</h2>
            <p>Discover our exquisite collection and add some elegance to your cart.</p>
            <a href="products.php" class="btn-luxury">
                <i class="fas fa-store"></i>
                Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <!-- Cart Content -->
        <form action="cart.php" method="POST">
            <div class="cart-table-wrapper" data-aos="fade-up">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        foreach ($_SESSION['cart'] as $product_id => $item): 
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                        ?>
                            <tr data-aos="fade-up" data-aos-delay="50">
                                <td class="product-info-cell">
                                    <div class="product-info">
                                        <?php if ($item['image']): ?>
                                            <div class="product-image">
                                                <img src="<?php echo $item['image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="product-image-placeholder">
                                                <i class="fas fa-gem"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-details">
                                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <span class="product-id">SKU: <?php echo $product_id; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="price-cell">
                                    <span class="currency">$</span>
                                    <span class="amount"><?php echo number_format($item['price'], 2); ?></span>
                                </td>
                                <td class="quantity-cell">
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-btn" onclick="decrementQuantity(this, <?php echo $product_id; ?>)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="quantities[<?php echo $product_id; ?>]" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="10" class="quantity-input" 
                                               id="qty_<?php echo $product_id; ?>" 
                                               onchange="updateCart()">
                                        <button type="button" class="quantity-btn" onclick="incrementQuantity(this, <?php echo $product_id; ?>, 10)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="total-cell">
                                    <span class="currency">$</span>
                                    <span class="amount"><?php echo number_format($item_total, 2); ?></span>
                                </td>
                                <td class="action-cell">
                                    <a href="cart.php?action=remove&id=<?php echo $product_id; ?>" 
                                       class="btn-remove" 
                                       onclick="return confirm('Remove this item from your cart?')"
                                       title="Remove item">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary" data-aos="fade-up">
                <h3 class="summary-title">
                    <i class="fas fa-gem"></i>
                    Order Summary
                </h3>
                
                <div class="summary-row">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value">$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Shipping</span>
                    <span class="summary-value">$5.99</span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Tax (8.5%)</span>
                    <span class="summary-value">$<?php echo number_format($subtotal * 0.085, 2); ?></span>
                </div>
                
                <div class="summary-row total">
                    <span class="summary-label">Total</span>
                    <span class="summary-value">$<?php echo number_format($subtotal + 5.99 + ($subtotal * 0.085), 2); ?></span>
                </div>
            </div>

            <!-- Cart Actions -->
            <div class="cart-actions" data-aos="fade-up">
                <a href="products.php" class="btn-outline-luxury">
                    <i class="fas fa-arrow-left"></i>
                    Continue Shopping
                </a>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="update_cart" class="btn-outline-luxury">
                        <i class="fas fa-sync-alt"></i>
                        Update Cart
                    </button>
                    
                    <a href="checkout.php" class="btn-gold">
                        <i class="fas fa-check-circle"></i>
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Quantity Control JavaScript -->
<script>
function incrementQuantity(btn, productId, max) {
    const input = document.getElementById('qty_' + productId);
    let value = parseInt(input.value) || 1;
    if (value < max) {
        input.value = value + 1;
        autoUpdateCart();
    }
}

function decrementQuantity(btn, productId) {
    const input = document.getElementById('qty_' + productId);
    let value = parseInt(input.value) || 1;
    if (value > 1) {
        input.value = value - 1;
        autoUpdateCart();
    }
}

let updateTimer;
function autoUpdateCart() {
    clearTimeout(updateTimer);
    updateTimer = setTimeout(() => {
        document.querySelector('form').submit();
    }, 1000);
}

function updateCart() {
    document.querySelector('form').submit();
}

// Add loading state to buttons
document.querySelectorAll('.btn-gold, .btn-outline-luxury').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (this.classList.contains('btn-gold') && this.getAttribute('href') === '#') {
            e.preventDefault();
            this.classList.add('loading');
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }
    });
});

// AOS initialization
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>