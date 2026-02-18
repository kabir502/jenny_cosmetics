<?php
// admin/delete_product.php - Delete Product

// Include central session handler from root
require_once '../session_handler.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database
require_once '../config/database.php';
require_once '../config/constants.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: products.php?error=Invalid product ID");
    exit();
}

// Check if product has order items
$check_orders = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
$check_stmt = mysqli_prepare($connection, $check_orders);
mysqli_stmt_bind_param($check_stmt, "i", $product_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$order_count = mysqli_fetch_assoc($check_result)['count'];

if ($order_count > 0) {
    header("Location: products.php?error=Cannot delete product. It has been ordered by customers.");
    exit();
}

// Get product image before deleting
$image_query = "SELECT image_url FROM products WHERE product_id = ?";
$image_stmt = mysqli_prepare($connection, $image_query);
mysqli_stmt_bind_param($image_stmt, "i", $product_id);
mysqli_stmt_execute($image_stmt);
$image_result = mysqli_stmt_get_result($image_stmt);
$product = mysqli_fetch_assoc($image_result);

// Delete product
$delete_query = "DELETE FROM products WHERE product_id = ?";
$delete_stmt = mysqli_prepare($connection, $delete_query);
mysqli_stmt_bind_param($delete_stmt, "i", $product_id);

if (mysqli_stmt_execute($delete_stmt)) {
    // Delete product image if exists
    if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
        unlink('../' . $product['image_url']);
    }
    
    // Log the action
    error_log("Admin {$_SESSION['admin_name']} deleted product ID: $product_id");
    
    header("Location: products.php?success=Product deleted successfully");
    exit();
} else {
    header("Location: products.php?error=Failed to delete product");
    exit();
}
?>