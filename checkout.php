<?php
// checkout.php
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

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'])) {
        header("Location: checkout.php?error=Invalid security token");
        exit();
    }
    
    // Get user data
    $user_id = $_SESSION['user_id'];
    
    // Generate order number
    $order_number = 'ORD' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    
    // Create order
    $order_query = "INSERT INTO orders (
        order_number, user_id, subtotal, tax_amount, shipping_amount, 
        total_amount, shipping_address, billing_address, payment_method
    ) VALUES (
        '$order_number', $user_id, $subtotal, $tax, $shipping, $total,
        '{$_POST['shipping_address']}', '{$_POST['billing_address']}', 
        '{$_POST['payment_method']}'
    )";
    
    if (mysqli_query($connection, $order_query)) {
        $order_id = mysqli_insert_id($connection);
        
        // Add order items
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $item_total = $item['price'] * $item['quantity'];
            
            $item_query = "INSERT INTO order_items (
                order_id, product_id, quantity, unit_price, total_price
            ) VALUES (
                $order_id, $product_id, {$item['quantity']}, 
                {$item['price']}, $item_total
            )";
            
            mysqli_query($connection, $item_query);
            
            // Update product stock
            $update_stock = "UPDATE products SET 
                            quantity_in_stock = quantity_in_stock - {$item['quantity']},
                            total_sold = total_sold + {$item['quantity']}
                            WHERE product_id = $product_id";
            mysqli_query($connection, $update_stock);
        }
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Redirect to success page
        header("Location: order_success.php?id=$order_id");
        exit();
    } else {
        header("Location: checkout.php?error=Failed to process order");
        exit();
    }
}

// Get user address
$user_query = "SELECT * FROM users WHERE user_id = {$_SESSION['user_id']}";
$user_result = mysqli_query($connection, $user_query);
$user = mysqli_fetch_assoc($user_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <h2>Checkout</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address *</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                          rows="3" required><?php 
                                    echo htmlspecialchars($user['address_street'] . ', ' . 
                                           $user['address_city'] . ', ' . 
                                           $user['address_country']);
                                ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="billing_address" class="form-label">Billing Address</label>
                                <textarea class="form-control" id="billing_address" name="billing_address" 
                                          rows="3"><?php 
                                    echo htmlspecialchars($user['address_street'] . ', ' . 
                                           $user['address_city'] . ', ' . 
                                           $user['address_country']);
                                ?></textarea>
                                <div class="form-text">Leave blank if same as shipping address</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method *</label>
                                <select class="form-control" id="payment_method" name="payment_method" required>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="PayPal">PayPal</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cash on Delivery">Cash on Delivery</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Order Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>$<?php echo number_format($shipping, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax:</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong>$<?php echo number_format($total, 2); ?></strong>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100" name="place_order">
                            Place Order
                        </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>