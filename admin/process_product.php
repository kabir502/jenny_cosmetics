<?php
// admin/process_product.php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit();
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'])) {
    header("Location: products.php?error=Invalid security token");
    exit();
}

$action = $_POST['action'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

// Sanitize inputs
$product_name = mysqli_real_escape_string($connection, trim($_POST['product_name']));
$sku = mysqli_real_escape_string($connection, trim($_POST['sku']));
$category_id = (int)$_POST['category_id'];
$unit_price = (float)$_POST['unit_price'];
$cost_price = !empty($_POST['cost_price']) ? (float)$_POST['cost_price'] : null;
$quantity_in_stock = (int)$_POST['quantity_in_stock'];
$description = mysqli_real_escape_string($connection, trim($_POST['description']));
$short_description = mysqli_real_escape_string($connection, trim($_POST['short_description']));
$weight_grams = !empty($_POST['weight_grams']) ? (float)$_POST['weight_grams'] : null;
$is_featured = isset($_POST['is_featured']) ? 1 : 0;
$is_active = isset($_POST['is_active']) ? 1 : 0;

// Validate required fields
if (empty($product_name) || empty($sku) || $category_id <= 0 || $unit_price <= 0) {
    header("Location: products.php?error=Please fill all required fields");
    exit();
}

// Check if SKU already exists (for new products or when SKU changes)
if ($action === 'add') {
    $check_sku = "SELECT product_id FROM products WHERE sku = '$sku'";
    $sku_result = mysqli_query($connection, $check_sku);
    
    if (mysqli_num_rows($sku_result) > 0) {
        header("Location: add_product.php?error=SKU already exists");
        exit();
    }
}

// Handle file upload
$image_url = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $file_name = basename($file['name']);
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_ext, $allowed_ext)) {
        if ($file_size <= MAX_FILE_SIZE) {
            // Generate unique filename
            $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
            $upload_path = '../' . UPLOAD_PATH . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $image_url = UPLOAD_PATH . $new_filename;
            }
        }
    }
}

if ($action === 'add') {
    // Insert new product
    $query = "INSERT INTO products (
                product_name, sku, category_id, unit_price, cost_price, 
                quantity_in_stock, description, short_description, 
                image_url, weight_grams, is_featured, is_active
              ) VALUES (
                '$product_name', '$sku', $category_id, $unit_price, 
                " . ($cost_price !== null ? $cost_price : 'NULL') . ",
                $quantity_in_stock, '$description', '$short_description',
                " . ($image_url ? "'$image_url'" : 'NULL') . ",
                " . ($weight_grams !== null ? $weight_grams : 'NULL') . ",
                $is_featured, $is_active
              )";
    
    if (mysqli_query($connection, $query)) {
        header("Location: products.php?success=Product added successfully");
        exit();
    } else {
        header("Location: add_product.php?error=Failed to add product");
        exit();
    }
} elseif ($action === 'edit') {
    // Update existing product
    $update_fields = [];
    $update_fields[] = "product_name = '$product_name'";
    $update_fields[] = "sku = '$sku'";
    $update_fields[] = "category_id = $category_id";
    $update_fields[] = "unit_price = $unit_price";
    $update_fields[] = $cost_price !== null ? "cost_price = $cost_price" : "cost_price = NULL";
    $update_fields[] = "quantity_in_stock = $quantity_in_stock";
    $update_fields[] = "description = '$description'";
    $update_fields[] = "short_description = '$short_description'";
    $update_fields[] = "weight_grams = " . ($weight_grams !== null ? $weight_grams : 'NULL');
    $update_fields[] = "is_featured = $is_featured";
    $update_fields[] = "is_active = $is_active";
    
    if ($image_url) {
        // Delete old image if exists
        $old_image_query = "SELECT image_url FROM products WHERE product_id = $product_id";
        $old_image_result = mysqli_query($connection, $old_image_query);
        $old_image = mysqli_fetch_assoc($old_image_result);
        
        if ($old_image['image_url']) {
            unlink('../' . $old_image['image_url']);
        }
        
        $update_fields[] = "image_url = '$image_url'";
    }
    
    $query = "UPDATE products SET " . implode(', ', $update_fields) . 
             " WHERE product_id = $product_id";
    
    if (mysqli_query($connection, $query)) {
        header("Location: products.php?success=Product updated successfully");
        exit();
    } else {
        header("Location: edit_product.php?id=$product_id&error=Failed to update product");
        exit();
    }
}
?>