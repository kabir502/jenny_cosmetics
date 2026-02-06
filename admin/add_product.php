<?php
// admin/add_product.php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
requireAdminLogin();

// Get categories for dropdown
$categories_query = "SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name";
$categories_result = mysqli_query($connection, $categories_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include 'includes/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h4>Add New Product</h4>
                    </div>
                    <div class="card-body">
                        <form action="process_product.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">Product Name *</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sku" class="form-label">SKU *</label>
                                        <input type="text" class="form-control" id="sku" name="sku" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-control" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                                <option value="<?php echo $category['category_id']; ?>">
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="unit_price" class="form-label">Price *</label>
                                                <input type="number" class="form-control" id="unit_price" name="unit_price" 
                                                       step="0.01" min="0" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="cost_price" class="form-label">Cost Price</label>
                                                <input type="number" class="form-control" id="cost_price" name="cost_price" 
                                                       step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity_in_stock" class="form-label">Stock Quantity *</label>
                                        <input type="number" class="form-control" id="quantity_in_stock" 
                                               name="quantity_in_stock" min="0" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Product Image</label>
                                        <input type="file" class="form-control" id="image" name="image" 
                                               accept="image/*">
                                        <div class="form-text">Max size: 5MB. Allowed: JPG, PNG, GIF</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="weight_grams" class="form-label">Weight (grams)</label>
                                        <input type="number" class="form-control" id="weight_grams" 
                                               name="weight_grams" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="short_description" class="form-label">Short Description</label>
                                <textarea class="form-control" id="short_description" name="short_description" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1">
                                <label class="form-check-label" for="is_featured">Featured Product</label>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" name="submit">Add Product</button>
                            <a href="products.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>