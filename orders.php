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

// Get user orders
$user_id = $_SESSION['user_id'];
$orders_query = "SELECT * FROM orders 
                 WHERE user_id = $user_id 
                 ORDER BY order_date DESC 
                 LIMIT 20";
$orders_result = mysqli_query($connection, $orders_query);

$orders = [];
while ($order = mysqli_fetch_assoc($orders_result)) {
    $orders[] = $order;
}

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4"><i class="fas fa-shopping-bag me-2"></i>My Orders</h1>
            
            <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                <h4>No Orders Yet</h4>
                <p>You haven't placed any orders yet. Start shopping now!</p>
                <a href="products.php" class="btn btn-primary">Browse Products</a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
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
                        // Get item count for this order
                        $item_count_query = "SELECT COUNT(*) as count FROM order_items WHERE order_id = {$order['order_id']}";
                        $item_count_result = mysqli_query($connection, $item_count_query);
                        $item_count = mysqli_fetch_assoc($item_count_result)['count'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $order['order_number']; ?></strong>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $item_count; ?> item<?php echo $item_count != 1 ? 's' : ''; ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <?php
                                $status_colors = [
                                    'Pending' => 'warning',
                                    'Processing' => 'info',
                                    'Shipped' => 'primary',
                                    'Delivered' => 'success',
                                    'Cancelled' => 'danger'
                                ];
                                $color = $status_colors[$order['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_success.php?id=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-1"></i>Continue Shopping
                </a>
                <a href="profile.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-user me-1"></i>My Profile
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>