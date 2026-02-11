<?php
// order_success.php - Order confirmation/success page

// Include session handler
require_once 'session_handler.php';

// Include database
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    // No specific order, maybe show recent orders
    header("Location: orders.php");
    exit();
}

// Get order details
$order_query = "SELECT o.*, 
                CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                u.email, u.phone_cell
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE o.order_id = $order_id 
                AND o.user_id = {$_SESSION['user_id']}";
$order_result = mysqli_query($connection, $order_query);

if (!$order_result || mysqli_num_rows($order_result) == 0) {
    // Order not found or doesn't belong to user
    header("Location: orders.php?error=Order not found");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT oi.*, p.product_name, p.image_url, p.sku
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = $order_id";
$items_result = mysqli_query($connection, $items_query);

$order_items = [];
$subtotal = 0;
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items[] = $item;
    $subtotal += $item['total_price'];
}

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Success Message -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="alert alert-success text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-check-circle fa-4x text-success"></i>
                </div>
                <h1 class="display-5 fw-bold">Thank You For Your Order!</h1>
                <p class="lead">Your order has been successfully placed and is being processed.</p>
                <p class="mb-0">Order Confirmation: <strong><?php echo $order['order_number']; ?></strong></p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Summary -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Order Details</h5>
                </div>
                <div class="card-body">
                    <!-- Order Status Timeline -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="mb-3">Order Status</h6>
                            <div class="timeline">
                                <div class="timeline-step completed">
                                    <div class="timeline-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Order Placed</h6>
                                        <p><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
                                    </div>
                                </div>
                                <div class="timeline-step <?php echo $order['status'] == 'Processing' || $order['status'] == 'Shipped' || $order['status'] == 'Delivered' ? 'completed' : ''; ?>">
                                    <div class="timeline-icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Processing</h6>
                                        <p>Preparing your order</p>
                                    </div>
                                </div>
                                <div class="timeline-step <?php echo $order['status'] == 'Shipped' || $order['status'] == 'Delivered' ? 'completed' : ''; ?>">
                                    <div class="timeline-icon">
                                        <i class="fas fa-shipping-fast"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Shipped</h6>
                                        <p>On the way to you</p>
                                    </div>
                                </div>
                                <div class="timeline-step <?php echo $order['status'] == 'Delivered' ? 'completed' : ''; ?>">
                                    <div class="timeline-icon">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Delivered</h6>
                                        <p>Order received</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h6 class="mb-3">Items Ordered</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($item['image_url']): ?>
                                            <img src="<?php echo $item['image_url']; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                 class="me-3" width="60" height="60" style="object-fit: cover;">
                                            <?php else: ?>
                                            <div class="bg-light me-3 d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="fas fa-box text-secondary"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                <small class="text-muted">SKU: <?php echo $item['sku']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Support -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-headset me-2"></i>Need Help?</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                                <h6>Call Us</h6>
                                <p class="mb-0 small">+1 (234) 567-8900</p>
                                <small class="text-muted">Mon-Fri, 9am-6pm</small>
                            </div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                <h6>Email Us</h6>
                                <p class="mb-0 small">support@jennyscosmetics.com</p>
                                <small class="text-muted">24/7 Support</small>
                            </div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-comments fa-2x text-primary mb-2"></i>
                                <h6>Live Chat</h6>
                                <p class="mb-0 small">Available Now</p>
                                <small class="text-muted">Click to start chat</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary & Shipping -->
        <div class="col-lg-4">
            <!-- Order Summary Card -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Order Number:</span>
                            <strong><?php echo $order['order_number']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Order Date:</span>
                            <strong><?php echo date('M d, Y', strtotime($order['order_date'])); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Order Status:</span>
                            <span class="badge bg-<?php 
                                $status_colors = [
                                    'Pending' => 'warning',
                                    'Processing' => 'info',
                                    'Shipped' => 'primary',
                                    'Delivered' => 'success',
                                    'Cancelled' => 'danger'
                                ];
                                echo $status_colors[$order['status']] ?? 'secondary';
                            ?>"><?php echo $order['status']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Method:</span>
                            <strong><?php echo $order['payment_method']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Status:</span>
                            <span class="badge bg-<?php echo $order['payment_status'] == 'Completed' ? 'success' : 'warning'; ?>">
                                <?php echo $order['payment_status']; ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>$<?php echo number_format($order['shipping_amount'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax:</span>
                            <span>$<?php echo number_format($order['tax_amount'], 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Total:</strong>
                            <strong class="h5">$<?php echo number_format($order['total_amount'], 2); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-grid gap-2">
                        <a href="orders.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-1"></i>View All Orders
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="fas fa-print me-1"></i>Print Invoice
                        </button>
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Shipping Information</h5>
                </div>
                <div class="card-body">
                    <h6>Shipping Address</h6>
                    <p class="mb-3">
                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                    </p>
                    
                    <?php if ($order['shipping_method']): ?>
                    <h6>Shipping Method</h6>
                    <p class="mb-3">
                        <?php echo $order['shipping_method']; ?>
                        <?php if ($order['tracking_number']): ?>
                        <br>
                        <small class="text-muted">
                            Tracking: <?php echo $order['tracking_number']; ?>
                        </small>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                    <h6>Estimated Delivery</h6>
                    <p class="mb-0">
                        <?php
                        $delivery_date = date('M d, Y', strtotime($order['order_date'] . ' + 3-7 days'));
                        echo $delivery_date . ' (3-7 business days)';
                        ?>
                    </p>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h5>
                </div>
                <div class="card-body">
                    <h6>Contact Details</h6>
                    <p class="mb-2">
                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                        <?php echo $order['email']; ?><br>
                        <?php echo $order['phone_cell']; ?>
                    </p>
                    
                    <?php if ($order['notes']): ?>
                    <h6 class="mt-3">Order Notes</h6>
                    <p class="mb-0">
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Steps -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-forward me-2"></i>What's Next?</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <div class="mb-2">
                                    <i class="fas fa-envelope-open-text fa-2x text-primary"></i>
                                </div>
                                <h6>Order Confirmation</h6>
                                <p class="small mb-0">You'll receive an email confirmation shortly</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <div class="mb-2">
                                    <i class="fas fa-cog fa-2x text-primary"></i>
                                </div>
                                <h6>Order Processing</h6>
                                <p class="small mb-0">We're preparing your items for shipment</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <div class="mb-2">
                                    <i class="fas fa-shipping-fast fa-2x text-primary"></i>
                                </div>
                                <h6>Order Shipped</h6>
                                <p class="small mb-0">You'll get tracking info when it ships</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 border rounded">
                                <div class="mb-2">
                                    <i class="fas fa-home fa-2x text-primary"></i>
                                </div>
                                <h6>Order Delivered</h6>
                                <p class="small mb-0">Your order will arrive at your doorstep</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Continue Shopping -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
            </a>
            <a href="index.php" class="btn btn-outline-secondary btn-lg ms-2">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>
</div>

<style>
    /* Timeline Styles */
    .timeline {
        display: flex;
        justify-content: space-between;
        position: relative;
        padding: 20px 0;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 4px;
        background: #e9ecef;
        transform: translateY(-50%);
        z-index: 1;
    }
    
    .timeline-step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }
    
    .timeline-icon {
        width: 60px;
        height: 60px;
        background: #fff;
        border: 4px solid #e9ecef;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-size: 1.5rem;
        color: #6c757d;
    }
    
    .timeline-step.completed .timeline-icon {
        background: #28a745;
        border-color: #28a745;
        color: white;
    }
    
    .timeline-step.active .timeline-icon {
        background: #007bff;
        border-color: #007bff;
        color: white;
    }
    
    .timeline-content h6 {
        margin-bottom: 5px;
        font-size: 0.9rem;
    }
    
    .timeline-content p {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 0;
    }
    
    /* Print Styles */
    @media print {
        .navbar, .footer, .btn, .alert {
            display: none !important;
        }
        
        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
        }
        
        .container {
            max-width: 100% !important;
        }
    }
    
    /* Order Status Badges */
    .badge.bg-warning { background-color: #ffc107 !important; }
    .badge.bg-info { background-color: #17a2b8 !important; }
    .badge.bg-primary { background-color: #007bff !important; }
    .badge.bg-success { background-color: #28a745 !important; }
    .badge.bg-danger { background-color: #dc3545 !important; }
</style>

<script>
    // Print invoice function
    function printInvoice() {
        window.print();
    }
    
    // Download invoice as PDF (placeholder)
    function downloadInvoice() {
        alert('Invoice download feature would be implemented here with a PDF generation library.');
    }
    
    // Share order via social media
    function shareOrder() {
        const orderNumber = '<?php echo $order["order_number"]; ?>';
        const text = `I just placed an order at Jenny's Cosmetics & Jewelry! Order #${orderNumber}`;
        
        if (navigator.share) {
            navigator.share({
                title: 'My Order Confirmation',
                text: text,
                url: window.location.href
            });
        } else {
            // Fallback: Copy to clipboard
            navigator.clipboard.writeText(text).then(() => {
                alert('Order details copied to clipboard!');
            });
        }
    }
    
    // Track order button
    function trackOrder() {
        const trackingNumber = '<?php echo $order["tracking_number"] ?? ""; ?>';
        if (trackingNumber) {
            alert(`Tracking Number: ${trackingNumber}\n\nYou can track your order on our courier's website.`);
        } else {
            alert('Tracking information will be available once your order is shipped.');
        }
    }
</script>

<?php
// Include footer
include 'includes/footer.php';
?>