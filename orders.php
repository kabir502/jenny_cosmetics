<?php
// orders.php - User orders listing page

// Include session handler
require_once 'session_handler.php';

// Include database
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user orders using prepared statement (security fix)
$user_id = $_SESSION['user_id'];
$orders_query = "SELECT * FROM orders 
                 WHERE user_id = ? 
                 ORDER BY order_date DESC 
                 LIMIT 20";

$stmt = mysqli_prepare($connection, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);

$orders = [];
while ($order = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $order;
}
mysqli_stmt_close($stmt);

// Include header
include 'includes/header.php';
?>

<style>
/* ===== ORDERS PAGE LUXURY THEME ===== */
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

.orders-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
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
        opacity: 0.8; 
        transform: scale(1.1); 
        text-shadow: 0 0 20px var(--gold), 0 0 30px var(--gold-light);
    }
}

/* Orders Card */
.orders-card {
    background: white;
    border-radius: 30px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    border: 1px solid rgba(212,175,55,0.2);
    margin-bottom: 2rem;
}

.card-header-luxury {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    padding: 1.5rem 2rem;
    border-bottom: 2px solid var(--gold);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: var(--navy);
    animation: sparkle 2s infinite;
}

.card-header-luxury h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin: 0;
}

.card-body-luxury {
    padding: 2rem;
}

/* Empty State */
.empty-orders {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--pearl);
    border-radius: 20px;
    margin: 2rem 0;
}

.empty-icon {
    margin-bottom: 2rem;
}

.empty-icon i {
    font-size: 6rem;
    color: var(--gold);
    animation: sparkle 2s infinite;
    opacity: 0.7;
}

.empty-orders h4 {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 1rem;
}

.empty-orders p {
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
    width: 400px;
    height: 400px;
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

/* Table Styles */
.table-wrapper {
    background: var(--pearl);
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    overflow-x: auto;
}

.orders-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 1rem;
}

.orders-table thead th {
    font-family: 'Playfair Display', serif;
    font-size: 1rem;
    font-weight: 700;
    color: var(--navy);
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    white-space: nowrap;
}

.orders-table tbody tr {
    background: white;
    border-radius: 20px;
    transition: var(--transition);
    box-shadow: 0 5px 15px rgba(0,0,0,0.03);
}

.orders-table tbody tr:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(212,175,55,0.15);
}

.orders-table td {
    padding: 1.5rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid rgba(212,175,55,0.1);
}

/* Order Number */
.order-number {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    color: var(--navy);
    font-size: 1.1rem;
    letter-spacing: 0.5px;
}

/* Date Cell */
.order-date {
    color: var(--charcoal);
    font-size: 0.95rem;
    font-family: 'Cormorant Garamond', serif;
}

.order-date i {
    color: var(--gold);
    margin-right: 0.3rem;
    font-size: 0.8rem;
}

/* Items Badge */
.items-badge {
    background: linear-gradient(135deg, var(--pearl), white);
    color: var(--navy);
    padding: 0.5rem 1.2rem;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid rgba(212,175,55,0.3);
}

.items-badge i {
    color: var(--gold);
    font-size: 0.8rem;
}

/* Total Amount */
.order-total {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    color: var(--navy);
    font-size: 1.2rem;
}

.order-total .currency {
    color: var(--gold);
    font-size: 0.9rem;
    margin-right: 0.2rem;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
    border: 1px solid transparent;
}

.status-badge i {
    font-size: 0.8rem;
}

.status-badge.pending {
    background: #FFF3CD;
    color: #856404;
    border-color: #FFD700;
}

.status-badge.processing {
    background: #D1ECF1;
    color: #0C5460;
    border-color: #17A2B8;
}

.status-badge.shipped {
    background: #C2E0F0;
    color: #1E5F7A;
    border-color: var(--gold);
}

.status-badge.delivered {
    background: #D4EDDA;
    color: #155724;
    border-color: #28A745;
}

.status-badge.cancelled {
    background: #F8D7DA;
    color: #721C24;
    border-color: #DC3545;
}

/* View Button */
.btn-view {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.2rem;
    background: transparent;
    color: var(--navy);
    border: 2px solid var(--gold);
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    font-size: 0.9rem;
}

.btn-view:hover {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(212,175,55,0.2);
}

.btn-view i {
    transition: var(--transition);
}

.btn-view:hover i {
    transform: translateX(3px);
}

/* Footer Actions */
.orders-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(212,175,55,0.2);
}

.footer-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--charcoal);
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem;
}

.footer-info i {
    color: var(--gold);
    animation: sparkle 3s infinite;
}

.footer-buttons {
    display: flex;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.btn-outline-luxury {
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.8rem 2rem;
    background: transparent;
    color: var(--navy);
    border: 2px solid var(--gold);
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    font-size: 0.95rem;
}

.btn-outline-luxury:hover {
    background: var(--gold);
    color: var(--navy);
    transform: translateY(-3px);
    box-shadow: 0 15px 25px rgba(212,175,55,0.3);
}

/* Responsive Design */
@media (max-width: 991px) {
    .page-header h1 {
        font-size: 2.4rem;
    }
    
    .card-header-luxury h1 {
        font-size: 1.8rem;
    }
    
    .orders-table thead {
        display: none;
    }
    
    .orders-table tbody tr {
        display: block;
        margin-bottom: 1.5rem;
        padding: 1rem;
    }
    
    .orders-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.8rem;
        border: none;
        border-bottom: 1px solid rgba(212,175,55,0.1);
    }
    
    .orders-table td:last-child {
        border-bottom: none;
    }
    
    .orders-table td::before {
        content: attr(data-label);
        font-weight: 700;
        color: var(--navy);
        font-family: 'Playfair Display', serif;
        font-size: 0.9rem;
    }
    
    .footer-buttons {
        width: 100%;
        justify-content: center;
    }
    
    .btn-outline-luxury {
        flex: 1;
        justify-content: center;
        min-width: 140px;
    }
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .card-header-luxury {
        padding: 1.2rem 1.5rem;
    }
    
    .header-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .card-header-luxury h1 {
        font-size: 1.5rem;
    }
    
    .card-body-luxury {
        padding: 1.5rem;
    }
    
    .orders-footer {
        flex-direction: column;
        text-align: center;
    }
    
    .footer-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-outline-luxury {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .page-header h1 {
        font-size: 1.8rem;
    }
    
    .empty-orders h4 {
        font-size: 1.8rem;
    }
    
    .empty-orders p {
        font-size: 1rem;
    }
    
    .btn-luxury {
        width: 100%;
    }
    
    .card-body-luxury {
        padding: 1rem;
    }
    
    .table-wrapper {
        padding: 0.5rem;
    }
}
</style>

<div class="orders-container">
    <!-- Page Header -->
    <div class="page-header" data-aos="fade-up">
        <h1>My Orders</h1>
        <div class="header-decoration">
            <i class="fas fa-gem decoration-icon"></i>
            <span class="decoration-line"></span>
            <i class="fas fa-gem decoration-icon"></i>
        </div>
    </div>

    <!-- Orders Card -->
    <div class="orders-card" data-aos="fade-up">
        <div class="card-header-luxury">
            <div class="header-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <h1>Order History</h1>
        </div>
        
        <div class="card-body-luxury">
            <?php if (empty($orders)): ?>
                <!-- Empty State -->
                <div class="empty-orders">
                    <div class="empty-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h4>No Orders Yet</h4>
                    <p>Your collection awaits. Start exploring our exquisite pieces.</p>
                    <a href="products.php" class="btn-luxury">
                        <i class="fas fa-shopping-cart me-2"></i>Browse Collection
                    </a>
                </div>
            <?php else: ?>
                <!-- Orders Table -->
                <div class="table-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <?php
                            // Get item count for this order using prepared statement
                            $item_count_query = "SELECT COUNT(*) as count FROM order_items WHERE order_id = ?";
                            $stmt_count = mysqli_prepare($connection, $item_count_query);
                            mysqli_stmt_bind_param($stmt_count, "i", $order['order_id']);
                            mysqli_stmt_execute($stmt_count);
                            $item_count_result = mysqli_stmt_get_result($stmt_count);
                            $item_count = mysqli_fetch_assoc($item_count_result)['count'];
                            mysqli_stmt_close($stmt_count);
                            ?>
                            <tr>
                                <td data-label="Order Number">
                                    <span class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                </td>
                                <td data-label="Date">
                                    <span class="order-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                    </span>
                                </td>
                                <td data-label="Items">
                                    <span class="items-badge">
                                        <i class="fas fa-box"></i>
                                        <?php echo $item_count; ?> item<?php echo $item_count != 1 ? 's' : ''; ?>
                                    </span>
                                </td>
                                <td data-label="Total">
                                    <span class="order-total">
                                        <span class="currency">$</span>
                                        <?php echo number_format($order['total_amount'], 2); ?>
                                    </span>
                                </td>
                                <td data-label="Status">
                                    <?php
                                    $status_class = strtolower($order['status']);
                                    $status_icons = [
                                        'pending' => 'clock',
                                        'processing' => 'cog',
                                        'shipped' => 'truck',
                                        'delivered' => 'check-circle',
                                        'cancelled' => 'times-circle'
                                    ];
                                    $icon = $status_icons[$status_class] ?? 'circle';
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <i class="fas fa-<?php echo $icon; ?>"></i>
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <a href="order_success.php?id=<?php echo urlencode($order['order_id']); ?>" 
                                       class="btn-view">
                                        <span>View Details</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="orders-footer">
                    <div class="footer-info">
                        <i class="fas fa-gem"></i>
                        <span>Showing your last <?php echo count($orders); ?> orders</span>
                    </div>
                    <div class="footer-buttons">
                        <a href="products.php" class="btn-outline-luxury">
                            <i class="fas fa-shopping-bag"></i>
                            Continue Shopping
                        </a>
                        <a href="profile.php" class="btn-outline-luxury">
                            <i class="fas fa-user"></i>
                            My Profile
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });
</script>

<?php include 'includes/footer.php'; ?>