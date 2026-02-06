<?php
// categories.php - Categories listing page

// Include session handler
require_once 'session_handler.php';

// Include database
require_once 'config/database.php';

// Get all categories
$categories_query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM products p 
                      WHERE p.category_id = c.category_id AND p.is_active = 1) as product_count
                     FROM categories c 
                     WHERE c.is_active = 1 
                     ORDER BY c.display_order, c.category_name";
$categories_result = mysqli_query($connection, $categories_query);

$categories = [];
if ($categories_result) {
    while ($category = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $category;
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-1">Product Categories</h1>
                <p class="text-muted mb-0">Browse our products by category</p>
            </div>
            <div>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-th-large me-2"></i>View All Products
                </a>
           