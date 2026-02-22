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

<style>
/* ===== ORDER SUCCESS PAGE LUXURY THEME - FIXED ===== */
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

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.order-success-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
    overflow: hidden;
}

/* Success Alert */
.success-alert {
    background: linear-gradient(135deg, #1A2A4F, #2A3F6F);
    border-radius: 20px;
    padding: 3rem 1.5rem;
    text-align: center;
    border: 2px solid var(--gold);
    margin-bottom: 3rem;
    position: relative;
    overflow: hidden;
    width: 100%;
    clear: both;
}

.success-alert::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(212,175,55,0.1) 0%, transparent 70%);
    animation: rotate 30s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 3rem;
    color: var(--navy);
    animation: sparkle 2s infinite;
    position: relative;
    z-index: 2;
}

@keyframes sparkle {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.success-alert h1 {
    font-family: 'Playfair Display', serif;
    color: white;
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 2;
    line-height: 1.2;
}

.success-alert p {
    color: rgba(255,255,255,0.9);
    font-size: 1.2rem;
    margin-bottom: 1rem;
    position: relative;
    z-index: 2;
    font-family: 'Cormorant Garamond', serif;
    line-height: 1.6;
}

.order-number {
    display: inline-block;
    background: rgba(255,255,255,0.1);
    padding: 0.5rem 2rem;
    border-radius: 50px;
    border: 1px solid var(--gold);
    color: var(--gold);
    font-weight: 600;
    font-size: 1.1rem;
    margin-top: 0.5rem;
    position: relative;
    z-index: 2;
}

/* Timeline */
.timeline-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(212,175,55,0.2);
    margin-bottom: 2rem;
    width: 100%;
}

.timeline-header {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    padding: 1rem 1.5rem;
    border-bottom: 2px solid var(--gold);
}

.timeline-header h5 {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.timeline-header i {
    color: var(--gold);
}

.timeline-body {
    padding: 2rem;
    width: 100%;
}

.timeline {
    display: flex;
    justify-content: space-between;
    position: relative;
    padding: 20px 0;
    width: 100%;
    gap: 10px;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 30px;
    left: 50px;
    right: 50px;
    height: 4px;
    background: linear-gradient(90deg, var(--gold-light), var(--gold), var(--gold-dark));
    transform: translateY(0);
    z-index: 1;
    border-radius: 2px;
}

.timeline-step {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
    min-width: 0;
}

.timeline-icon {
    width: 60px;
    height: 60px;
    background: white;
    border: 3px solid var(--gold-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 1.5rem;
    color: var(--charcoal);
    transition: var(--transition);
    position: relative;
    z-index: 3;
}

.timeline-step.completed .timeline-icon {
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-color: var(--gold-dark);
    color: var(--navy);
}

.timeline-content {
    padding: 0 5px;
}

.timeline-content h6 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 5px;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.timeline-content p {
    font-size: 0.8rem;
    color: var(--charcoal);
    margin-bottom: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Section Headers */
.section-header {
    margin: 2rem 0 1.5rem;
    width: 100%;
    clear: both;
}

.section-header h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-header i {
    color: var(--gold);
}

.section-divider {
    width: 100px;
    height: 2px;
    background: linear-gradient(90deg, var(--gold) 0%, transparent 100%);
}

/* Product Table */
.table-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(212,175,55,0.2);
    margin-bottom: 2rem;
    width: 100%;
}

.table-header {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    padding: 1rem 1.5rem;
    border-bottom: 2px solid var(--gold);
}

.table-header h5 {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.table-header i {
    color: var(--gold);
}

.table-responsive {
    padding: 1.5rem;
    overflow-x: auto;
    width: 100%;
}

.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 0.5rem;
    margin: 0;
}

.table thead th {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--navy);
    border-bottom: 2px solid var(--gold);
    padding: 0.75rem;
    text-transform: uppercase;
    font-size: 0.9rem;
    text-align: left;
}

.table tbody tr {
    background: var(--pearl);
    border-radius: 10px;
    transition: var(--transition);
}

.table tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212,175,55,0.2);
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid rgba(212,175,55,0.1);
}

.product-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.product-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--gold-light);
    flex-shrink: 0;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-image-placeholder {
    width: 60px;
    height: 60px;
    background: var(--pearl);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--gold-light);
    flex-shrink: 0;
}

.product-image-placeholder i {
    font-size: 1.5rem;
    color: var(--gold);
    opacity: 0.5;
}

.product-details h6 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 0.2rem;
}

.product-details small {
    color: var(--charcoal);
    font-size: 0.8rem;
}

.price {
    font-weight: 700;
    color: var(--navy);
}

.total-price {
    font-weight: 800;
    color: var(--gold);
    font-size: 1.1rem;
}

/* Info Cards */
.info-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(212,175,55,0.2);
    height: auto;
    width: 100%;
    margin-bottom: 1.5rem;
}

.info-header {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    padding: 1rem 1.5rem;
    border-bottom: 2px solid var(--gold);
}

.info-header h5 {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: white;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-header i {
    color: var(--gold);
}

.info-body {
    padding: 1.5rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(212,175,55,0.1);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--charcoal);
    font-weight: 500;
}

.info-value {
    font-weight: 700;
    color: var(--navy);
    text-align: right;
}

.total-row {
    font-size: 1.2rem;
    padding-top: 1rem;
    margin-top: 0.5rem;
    border-top: 2px solid var(--gold);
}

.total-row .info-label {
    color: var(--gold);
    font-weight: 700;
}

.total-row .info-value {
    color: var(--gold);
    font-size: 1.4rem;
    font-weight: 800;
}

.status-badge {
    display: inline-block;
    padding: 0.4rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
}

.status-pending { background: #FFE5B4; color: #B27C2E; }
.status-processing { background: #B8E1FF; color: #1E4A7A; }
.status-shipped { background: #C2E0F0; color: #1E5F7A; }
.status-delivered { background: #D4EDDA; color: #155724; }
.status-cancelled { background: #F8D7DA; color: #721C24; }

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.btn-luxury, .btn-outline-luxury {
    padding: 0.8rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
    border: none;
    font-size: 1rem;
    min-width: 180px;
}

.btn-luxury {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    position: relative;
    overflow: hidden;
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
}

.btn-luxury:hover::before {
    width: 400px;
    height: 400px;
}

.btn-luxury:hover {
    transform: translateY(-3px);
    box-shadow: 0 20px 30px rgba(212,175,55,0.3);
}

.btn-outline-luxury {
    background: transparent;
    border: 2px solid var(--gold);
    color: var(--navy);
}

.btn-outline-luxury:hover {
    background: var(--gold);
    color: var(--navy);
    transform: translateY(-3px);
    box-shadow: 0 20px 30px rgba(212,175,55,0.2);
}

/* Next Steps */
.steps-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
    width: 100%;
}

.step-item {
    background: var(--pearl);
    padding: 1.5rem 1rem;
    border-radius: 16px;
    text-align: center;
    transition: var(--transition);
    border: 1px solid transparent;
    height: 100%;
}

.step-item:hover {
    transform: translateY(-5px);
    border-color: var(--gold);
    box-shadow: 0 10px 20px rgba(212,175,55,0.1);
}

.step-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.3rem;
    color: var(--navy);
}

.step-item h6 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.step-item p {
    font-size: 0.85rem;
    color: var(--charcoal);
    margin: 0;
    line-height: 1.4;
}

/* Utility Classes */
.mt-4 { margin-top: 1.5rem; }
.mb-4 { margin-bottom: 1.5rem; }
.text-center { text-align: center; }

/* Clear floats */
.row::after {
    content: "";
    clear: both;
    display: table;
}

[class*="col-"] {
    float: left;
    padding: 0 10px;
    width: 100%;
}

@media (min-width: 768px) {
    .col-lg-8 {
        width: 66.66%;
    }
    .col-lg-4 {
        width: 33.33%;
    }
}

/* Print Styles */
@media print {
    .navbar, .footer, .btn-luxury, .btn-outline-luxury, .action-buttons, .steps-grid {
        display: none !important;
    }
    
    .success-alert {
        border: 2px solid #000;
        background: white;
        page-break-inside: avoid;
    }
    
    .success-alert h1, .success-alert p {
        color: #000;
    }
    
    .order-number {
        border: 1px solid #000;
        color: #000;
    }
    
    .timeline-icon {
        border: 2px solid #000;
        background: white !important;
    }
    
    .table-card, .info-card {
        border: 1px solid #000;
        box-shadow: none;
        page-break-inside: avoid;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .timeline {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .timeline::before {
        display: none;
    }
    
    .timeline-step {
        display: flex;
        align-items: center;
        gap: 1rem;
        text-align: left;
    }
    
    .timeline-icon {
        margin: 0;
        flex-shrink: 0;
    }
    
    .timeline-content {
        flex: 1;
    }
    
    .timeline-content h6,
    .timeline-content p {
        white-space: normal;
    }
    
    .steps-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-luxury, .btn-outline-luxury {
        width: 100%;
        max-width: 300px;
    }
    
    .success-alert h1 {
        font-size: 2rem;
    }
    
    .product-info {
        flex-direction: column;
        text-align: center;
    }
    
    .product-image, .product-image-placeholder {
        margin: 0 auto;
    }
}

@media (max-width: 576px) {
    .steps-grid {
        grid-template-columns: 1fr;
    }
    
    .table thead {
        display: none;
    }
    
    .table tbody tr {
        display: block;
        margin-bottom: 1rem;
        padding: 1rem;
    }
    
    .table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem;
        border-bottom: 1px solid rgba(212,175,55,0.1);
        text-align: right;
    }
    
    .table tbody td:before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--navy);
        margin-right: 1rem;
    }
    
    .product-info {
        flex-direction: row;
        text-align: left;
    }
    
    .product-details {
        text-align: left;
    }
    
    .success-alert {
        padding: 2rem 1rem;
    }
    
    .success-alert h1 {
        font-size: 1.8rem;
    }
}
</style>

<div class="order-success-container">
    <!-- Success Message -->
    <div class="success-alert" data-aos="fade-up">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Thank You For Your Order!</h1>
        <p>Your order has been successfully placed and is being processed.</p>
        <div class="order-number">
            Order #<?php echo $order['order_number']; ?>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Order Timeline -->
            <div class="timeline-card" data-aos="fade-up">
                <div class="timeline-header">
                    <h5><i class="fas fa-clock"></i> Order Status</h5>
                </div>
                <div class="timeline-body">
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
                        <div class="timeline-step <?php echo in_array($order['status'], ['Processing', 'Shipped', 'Delivered']) ? 'completed' : ''; ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="timeline-content">
                                <h6>Processing</h6>
                                <p>Preparing your order</p>
                            </div>
                        </div>
                        <div class="timeline-step <?php echo in_array($order['status'], ['Shipped', 'Delivered']) ? 'completed' : ''; ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="timeline-content">
                                <h6>Shipped</h6>
                                <p>On the way</p>
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
            <div class="table-card" data-aos="fade-up">
                <div class="table-header">
                    <h5><i class="fas fa-box"></i> Items Ordered</h5>
                </div>
                <div class="table-responsive">
                    <table class="table">
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
                                <td data-label="Product">
                                    <div class="product-info">
                                        <?php if ($item['image_url']): ?>
                                            <div class="product-image">
                                                <img src="<?php echo $item['image_url']; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="product-image-placeholder">
                                                <i class="fas fa-gem"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-details">
                                            <h6><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                            <small>SKU: <?php echo $item['sku']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Price" class="price">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td data-label="Quantity"><?php echo $item['quantity']; ?></td>
                                <td data-label="Total" class="total-price">$<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="info-card" data-aos="fade-left">
                <div class="info-header">
                    <h5><i class="fas fa-receipt"></i> Order Summary</h5>
                </div>
                <div class="info-body">
                    <div class="info-row">
                        <span class="info-label">Order Number:</span>
                        <span class="info-value"><?php echo $order['order_number']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Status:</span>
                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value"><?php echo $order['payment_method']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status:</span>
                        <span class="status-badge status-<?php echo strtolower($order['payment_status'] ?? 'Pending'); ?>">
                            <?php echo $order['payment_status'] ?? 'Pending'; ?>
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Subtotal:</span>
                        <span class="info-value">$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Shipping:</span>
                        <span class="info-value">$<?php echo number_format($order['shipping_amount'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tax:</span>
                        <span class="info-value">$<?php echo number_format($order['tax_amount'], 2); ?></span>
                    </div>
                    <div class="info-row total-row">
                        <span class="info-label">Total:</span>
                        <span class="info-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="info-card mt-4" data-aos="fade-left" data-aos-delay="100">
                <div class="info-header">
                    <h5><i class="fas fa-truck"></i> Shipping Information</h5>
                </div>
                <div class="info-body">
                    <h6 style="color: var(--gold); margin-bottom: 0.5rem;">Shipping Address</h6>
                    <p style="color: var(--charcoal); margin-bottom: 1.5rem; word-wrap: break-word;">
                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                    </p>
                    
                    <h6 style="color: var(--gold); margin-bottom: 0.5rem;">Estimated Delivery</h6>
                    <p style="color: var(--charcoal); margin-bottom: 0;">
                        <?php
                        $delivery_date = date('M d, Y', strtotime($order['order_date'] . ' + 3-7 days'));
                        echo $delivery_date . ' (3-7 business days)';
                        ?>
                    </p>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="info-card mt-4" data-aos="fade-left" data-aos-delay="200">
                <div class="info-header">
                    <h5><i class="fas fa-user"></i> Customer Information</h5>
                </div>
                <div class="info-body">
                    <h6 style="color: var(--gold); margin-bottom: 0.5rem;">Contact Details</h6>
                    <p style="color: var(--charcoal); margin-bottom: 0.5rem; word-wrap: break-word;">
                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                        <?php echo $order['email']; ?><br>
                        <?php echo $order['phone_cell']; ?>
                    </p>
                    
                    <?php if (!empty($order['notes'])): ?>
                    <h6 style="color: var(--gold); margin-bottom: 0.5rem; margin-top: 1rem;">Order Notes</h6>
                    <p style="color: var(--charcoal); margin-bottom: 0; word-wrap: break-word;">
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Steps -->
    <div class="section-header" data-aos="fade-up">
        <h3><i class="fas fa-forward"></i> What's Next?</h3>
        <div class="section-divider"></div>
    </div>

    <div class="steps-grid" data-aos="fade-up">
        <div class="step-item">
            <div class="step-icon">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h6>Order Confirmation</h6>
            <p>You'll receive an email confirmation shortly</p>
        </div>
        <div class="step-item">
            <div class="step-icon">
                <i class="fas fa-cog"></i>
            </div>
            <h6>Order Processing</h6>
            <p>We're preparing your items for shipment</p>
        </div>
        <div class="step-item">
            <div class="step-icon">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <h6>Order Shipped</h6>
            <p>You'll get tracking info when it ships</p>
        </div>
        <div class="step-item">
            <div class="step-icon">
                <i class="fas fa-home"></i>
            </div>
            <h6>Order Delivered</h6>
            <p>Your order will arrive at your doorstep</p>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons" data-aos="fade-up">
        <a href="products.php" class="btn-luxury">
            <i class="fas fa-shopping-bag"></i> Continue Shopping
        </a>
        <a href="orders.php" class="btn-outline-luxury">
            <i class="fas fa-list"></i> View All Orders
        </a>
        <button onclick="window.print()" class="btn-outline-luxury">
            <i class="fas fa-print"></i> Print Invoice
        </button>
    </div>
</div>

<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });

    // Print invoice function
    function printInvoice() {
        window.print();
    }
</script>

<?php include 'includes/footer.php'; ?>