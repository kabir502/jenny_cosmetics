<?php
// cart.php
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <h2>Shopping Cart</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="products.php">Continue shopping</a>
            </div>
        <?php else: ?>
            <form action="cart.php" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($_SESSION['cart'] as $product_id => $item): 
                                $item_total = $item['price'] * $item['quantity'];
                                $subtotal += $item_total;
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($item['image']): ?>
                                                <img src="<?php echo $item['image']; ?>" 
                                                     alt="<?php echo $item['name']; ?>" 
                                                     width="50" height="50" class="me-3">
                                            <?php endif; ?>
                                            <div>
                                                <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <small>Product ID: <?php echo $product_id; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <input type="number" name="quantities[<?php echo $product_id; ?>]" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="10" class="form-control" style="width: 80px;">
                                    </td>
                                    <td>$<?php echo number_format($item_total, 2); ?></td>
                                    <td>
                                        <a href="cart.php?action=remove&id=<?php echo $product_id; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Remove this item?')">Remove</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td colspan="2"><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Shipping:</td>
                                <td colspan="2">$5.99</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Tax (8.5%):</td>
                                <td colspan="2">$<?php echo number_format($subtotal * 0.085, 2); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td colspan="2">
                                    <strong>$<?php echo number_format($subtotal + 5.99 + ($subtotal * 0.085), 2); ?></strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
                    <div>
                        <button type="submit" name="update_cart" class="btn btn-warning">Update Cart</button>
                        <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>