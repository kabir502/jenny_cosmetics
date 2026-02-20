<?php
// admin/toggle_product.php - Toggle Product Active Status

// Include central session handler from root
require_once '../session_handler.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database
require_once '../config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$new_status = isset($_GET['status']) ? (int)$_GET['status'] : null;

if ($product_id <= 0 || $new_status === null) {
    header("Location: products.php?error=Invalid request");
    exit();
}

// Get status filter from URL to maintain filter after toggle
$status_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$update_query = "UPDATE products SET is_active = ? WHERE product_id = ?";
$stmt = mysqli_prepare($connection, $update_query);
mysqli_stmt_bind_param($stmt, "ii", $new_status, $product_id);

if (mysqli_stmt_execute($stmt)) {
    $action = $new_status ? 'activated' : 'deactivated';
    header("Location: products.php?status=$status_filter&success=Product $action successfully");
} else {
    header("Location: products.php?status=$status_filter&error=Failed to update product status");
}
exit();
?>