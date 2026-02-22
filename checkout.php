<?php
// checkout.php - Checkout Page

require_once 'config/database.php';
require_once 'includes/auth_check.php';
requireLogin();

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 5.99;
$tax = $subtotal * 0.085;
$total = $subtotal + $shipping + $tax;

// Get user address
$user_query = "SELECT * FROM users WHERE user_id = {$_SESSION['user_id']}";
$user_result = mysqli_query($connection, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Process checkout
$error_message = '';
$debug_info = '';

// FIX: Check for form submission by looking for any POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_info .= "POST received. ";
    
    // FIX: Don't check for place_order button - check for required fields instead
    if (!empty($_POST['shipping_address']) && !empty($_POST['payment_method'])) {
        $debug_info .= "Required fields detected. ";
        
        // Get form data
        $shipping_address = mysqli_real_escape_string($connection, $_POST['shipping_address']);
        $billing_address = mysqli_real_escape_string($connection, $_POST['billing_address'] ?? $shipping_address);
        $payment_method = mysqli_real_escape_string($connection, $_POST['payment_method']);
        $notes = mysqli_real_escape_string($connection, $_POST['notes'] ?? '');
        
        $debug_info .= "Data collected. ";
        
        // Generate order number
        $order_number = 'ORD' . date('Ymd') . rand(1000, 9999);
        
        // Insert order
        $order_query = "INSERT INTO orders (
            order_number, user_id, order_date, status, subtotal, tax_amount, 
            shipping_amount, total_amount, payment_method, shipping_address, 
            billing_address, notes, payment_status
        ) VALUES (
            '$order_number', {$_SESSION['user_id']}, NOW(), 'Pending', 
            $subtotal, $tax, $shipping, $total, '$payment_method', 
            '$shipping_address', '$billing_address', '$notes', 'Pending'
        )";
        
        $debug_info .= "Query prepared. ";
        
        $result = mysqli_query($connection, $order_query);
        
        if ($result) {
            $debug_info .= "Order inserted successfully. ";
            $order_id = mysqli_insert_id($connection);
            
            // Add order items
            $item_count = 0;
            foreach ($_SESSION['cart'] as $product_id => $item) {
                $item_total = $item['price'] * $item['quantity'];
                
                $item_query = "INSERT INTO order_items (
                    order_id, product_id, quantity, unit_price, total_price
                ) VALUES (
                    $order_id, $product_id, {$item['quantity']}, 
                    {$item['price']}, $item_total
                )";
                
                if (mysqli_query($connection, $item_query)) {
                    $item_count++;
                    
                    // Update stock
                    $update_stock = "UPDATE products SET 
                                    quantity_in_stock = quantity_in_stock - {$item['quantity']}
                                    WHERE product_id = $product_id";
                    mysqli_query($connection, $update_stock);
                }
            }
            
            $debug_info .= "$item_count items added. ";
            
            // Clear cart
            $_SESSION['cart'] = [];
            $debug_info .= "Cart cleared. ";
            
            // Redirect
            header("Location: order_success.php?id=$order_id");
            exit();
        } else {
            $error_message = "Database Error: " . mysqli_error($connection);
            $debug_info .= "Error: " . mysqli_error($connection);
        }
    } else {
        $debug_info .= "Required fields missing. POST data: " . print_r($_POST, true);
    }
}

// Include header
include 'includes/header.php';
?>

<!-- DEBUG INFO - Remove after fixing -->
<?php if (!empty($debug_info)): ?>
<div style="background: #e3f2fd; color: #0d47a1; padding: 15px; margin: 20px; border-radius: 5px; border-left: 4px solid #2196f3;">
    <strong>Debug Info:</strong> <?php echo $debug_info; ?>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div style="background: #ffebee; color: #b71c1c; padding: 15px; margin: 20px; border-radius: 5px; border-left: 4px solid #f44336;">
    <strong>Error:</strong> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<style>
/* ===== CHECKOUT PAGE LUXURY THEME ===== */
:root {
    --gold: #D4AF37;
    --gold-light: #F4E5C1;
    --gold-dark: #AA8C2F;
    --navy: #1A2A4F;
    --navy-light: #2A3F6F;
    --navy-dark: #0F1A2F;
    --pearl: #F8F6F0;
    --charcoal: #36454F;
    --transition: all 0.3s ease;
}

.checkout-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

/* Page Header */
.page-header {
    margin-bottom: 2rem;
    text-align: center;
}

.page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.page-header p {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
    color: var(--charcoal);
    font-style: italic;
}

.header-decoration {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.header-decoration span {
    width: 60px;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
}

.header-decoration i {
    color: var(--gold);
    font-size: 1.2rem;
    animation: sparkle 2s infinite;
}

@keyframes sparkle {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.1); }
}

/* Checkout Card */
.checkout-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(212,175,55,0.2);
    height: 100%;
}

.card-header {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    padding: 1rem 1.5rem;
    border-bottom: 2px solid var(--gold);
}

.card-header h5 {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-header i {
    color: var(--gold);
}

.card-body {
    padding: 1.5rem;
}

/* Form Styles */
.form-label {
    font-weight: 600;
    color: var(--navy);
    margin-bottom: 0.3rem;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.form-label i {
    color: var(--gold);
    font-size: 0.9rem;
}

.form-control, .form-select {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0.6rem 0.8rem;
    transition: var(--transition);
    width: 100%;
}

.form-control:focus, .form-select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(212,175,55,0.1);
    outline: none;
}

.form-text {
    font-size: 0.8rem;
    color: var(--charcoal);
    margin-top: 0.2rem;
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

/* Order Summary Card */
.summary-card {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid var(--gold);
    height: 100%;
    color: white;
}

.summary-header {
    padding: 1rem 1.5rem;
    border-bottom: 2px solid var(--gold);
}

.summary-header h5 {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.summary-header i {
    color: var(--gold);
}

.summary-body {
    padding: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.summary-row:last-of-type {
    border-bottom: none;
}

.summary-label {
    color: rgba(255,255,255,0.9);
    font-size: 0.95rem;
}

.summary-value {
    font-weight: 600;
    color: var(--gold);
    font-size: 1rem;
}

.summary-total {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid var(--gold);
    font-size: 1.2rem;
}

.summary-total .summary-label {
    color: var(--gold);
    font-weight: 700;
}

.summary-total .summary-value {
    color: var(--gold);
    font-size: 1.4rem;
    font-weight: 800;
}

/* Order Items Preview */
.items-preview {
    margin-bottom: 1rem;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.3rem 0;
    font-size: 0.9rem;
}

.preview-item-name {
    color: rgba(255,255,255,0.9);
}

.preview-item-price {
    color: var(--gold);
    font-weight: 600;
}

/* Alert */
.alert {
    border: none;
    border-radius: 12px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    animation: slideDown 0.5s ease;
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

.alert-danger {
    background: #FFEBEE;
    color: #B71C1C;
    border-left: 4px solid #C62828;
}

.alert i {
    font-size: 1.1rem;
}

/* Place Order Button */
.btn-place-order {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    border: none;
    color: var(--navy);
    font-weight: 700;
    padding: 1rem;
    border-radius: 8px;
    width: 100%;
    font-size: 1.1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: var(--transition);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    margin-top: 1rem;
}

.btn-place-order::before {
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

.btn-place-order:hover::before {
    width: 400px;
    height: 400px;
}

.btn-place-order:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 30px rgba(212,175,55,0.3);
}

.btn-place-order:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-place-order i {
    margin-right: 0.5rem;
}

/* Payment Icons */
.payment-icons {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.payment-icon {
    font-size: 1.5rem;
    color: var(--gold);
    opacity: 0.7;
}

/* Security Note */
.security-note {
    text-align: center;
    margin-top: 1rem;
}

.security-note small {
    color: rgba(255,255,255,0.7);
    font-size: 0.8rem;
}

.security-note i {
    color: var(--gold);
    margin-right: 0.3rem;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .page-header p {
        font-size: 1rem;
    }
    
    .card-header h5 {
        font-size: 1.1rem;
    }
    
    .summary-total .summary-value {
        font-size: 1.2rem;
    }
}

@media (max-width: 576px) {
    .page-header h1 {
        font-size: 1.8rem;
    }
    
    .card-body, .summary-body {
        padding: 1rem;
    }
    
    .btn-place-order {
        padding: 0.8rem;
        font-size: 1rem;
    }
}

/* Loading State */
.btn-place-order.loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;
}

.btn-place-order.loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin-left: -10px;
    margin-top: -10px;
    border: 2px solid var(--navy);
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<div class="checkout-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>Secure Checkout</h1>
        <p>Complete your purchase</p>
        <div class="header-decoration">
            <span></span>
            <i class="fas fa-gem"></i>
            <span></span>
        </div>
    </div>

    <div class="row">
        <!-- Checkout Form -->
        <div class="col-lg-8 mb-4">
            <div class="checkout-card">
                <div class="card-header">
                    <h5><i class="fas fa-shipping-fast"></i> Shipping Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="checkoutForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? 'test'; ?>">
                        
                        <!-- Shipping Address -->
                        <div class="mb-4">
                            <label for="shipping_address" class="form-label">
                                <i class="fas fa-map-marker-alt"></i> Shipping Address *
                            </label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                      rows="3" required><?php 
                                echo htmlspecialchars(trim(
                                    ($user['address_street'] ?? '') . ', ' . 
                                    ($user['address_city'] ?? '') . ', ' . 
                                    ($user['address_country'] ?? '')
                                ));
                            ?></textarea>
                        </div>
                        
                        <!-- Billing Address -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <label for="billing_address_display" class="form-label mb-0 me-3">
                                    <i class="fas fa-credit-card"></i> Billing Address
                                </label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="same_as_shipping" checked>
                                    <label class="form-check-label" for="same_as_shipping">Same as shipping</label>
                                </div>
                            </div>
                            <!-- Display textarea (disabled, but shows the value) -->
                            <textarea class="form-control" id="billing_address_display" 
                                      rows="3" disabled><?php 
                                echo htmlspecialchars(trim(
                                    ($user['address_street'] ?? '') . ', ' . 
                                    ($user['address_city'] ?? '') . ', ' . 
                                    ($user['address_country'] ?? '')
                                ));
                            ?></textarea>
                            <!-- Hidden field that will always be submitted -->
                            <input type="hidden" name="billing_address" id="billing_address_hidden" 
                                   value="<?php echo htmlspecialchars(trim(
                                       ($user['address_street'] ?? '') . ', ' . 
                                       ($user['address_city'] ?? '') . ', ' . 
                                       ($user['address_country'] ?? '')
                                   )); ?>">
                            <div class="form-text">Leave as is if same as shipping address</div>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="mb-4">
                            <label for="payment_method" class="form-label">
                                <i class="fas fa-credit-card"></i> Payment Method *
                            </label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="Credit Card">💳 Credit Card</option>
                                <option value="PayPal">🅿️ PayPal</option>
                                <option value="Bank Transfer">🏦 Bank Transfer</option>
                                <option value="Cash on Delivery">💵 Cash on Delivery</option>
                            </select>
                            <div class="payment-icons">
                                <i class="fab fa-cc-visa payment-icon"></i>
                                <i class="fab fa-cc-mastercard payment-icon"></i>
                                <i class="fab fa-cc-amex payment-icon"></i>
                                <i class="fab fa-cc-paypal payment-icon"></i>
                            </div>
                        </div>
                        
                        <!-- Order Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                <i class="fas fa-pen"></i> Order Notes
                            </label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" 
                                      placeholder="Any special instructions for your order?"></textarea>
                        </div>
                        
                        <!-- Place Order Button -->
                        <button type="submit" name="place_order" class="btn-place-order" id="placeOrderBtn">
                            <i class="fas fa-check-circle"></i> Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4 mb-4">
            <div class="summary-card">
                <div class="summary-header">
                    <h5><i class="fas fa-shopping-bag"></i> Order Summary</h5>
                </div>
                <div class="summary-body">
                    <!-- Items Preview -->
                    <div class="items-preview">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="preview-item">
                            <span class="preview-item-name">
                                <?php echo htmlspecialchars(substr($item['name'], 0, 20)); ?> 
                                <small>(x<?php echo $item['quantity']; ?>)</small>
                            </span>
                            <span class="preview-item-price">
                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="summary-row">
                        <span class="summary-label">Subtotal:</span>
                        <span class="summary-value">$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Shipping:</span>
                        <span class="summary-value">$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Tax (8.5%):</span>
                        <span class="summary-value">$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <div class="summary-total">
                        <span class="summary-label">Total:</span>
                        <span class="summary-value">$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <!-- Security Note -->
                    <div class="security-note">
                        <small>
                            <i class="fas fa-lock"></i> Your information is secure
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Same as shipping checkbox
document.getElementById('same_as_shipping').addEventListener('change', function() {
    const shippingAddress = document.getElementById('shipping_address').value;
    const billingDisplay = document.getElementById('billing_address_display');
    const billingHidden = document.getElementById('billing_address_hidden');
    
    if (this.checked) {
        billingDisplay.value = shippingAddress;
        billingHidden.value = shippingAddress;
        billingDisplay.disabled = true;
    } else {
        billingDisplay.disabled = false;
        // When unchecked, keep the current hidden value
    }
});

// When user types in the billing display field, update the hidden field
document.getElementById('billing_address_display').addEventListener('input', function() {
    if (!this.disabled) {
        document.getElementById('billing_address_hidden').value = this.value;
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const sameAsShipping = document.getElementById('same_as_shipping');
    const billingDisplay = document.getElementById('billing_address_display');
    
    if (sameAsShipping.checked) {
        billingDisplay.disabled = true;
    }
});

// Form loading state
document.getElementById('checkoutForm').addEventListener('submit', function() {
    document.getElementById('placeOrderBtn').disabled = true;
    document.getElementById('placeOrderBtn').innerHTML = 'Processing...';
});
</script>

<?php include 'includes/footer.php'; ?>