<?php
// admin/add_product.php - COMPLETELY REWRITTEN TO AVOID THE BUG

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

// Initialize variables
$message = '';
$message_type = '';
$error = '';

// Get categories for dropdown
$categories_query = "SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name";
$categories_result = mysqli_query($connection, $categories_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    
    // Get form data and escape properly
    $product_name = mysqli_real_escape_string($connection, trim($_POST['product_name']));
    $sku = mysqli_real_escape_string($connection, trim($_POST['sku']));
    $description = mysqli_real_escape_string($connection, trim($_POST['description']));
    $short_description = mysqli_real_escape_string($connection, trim($_POST['short_description']));
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : 'NULL';
    $unit_price = !empty($_POST['unit_price']) ? (float)$_POST['unit_price'] : 0;
    $cost_price = !empty($_POST['cost_price']) ? (float)$_POST['cost_price'] : 'NULL';
    $quantity_in_stock = !empty($_POST['quantity_in_stock']) ? (int)$_POST['quantity_in_stock'] : 0;
    $min_stock_level = !empty($_POST['min_stock_level']) ? (int)$_POST['min_stock_level'] : 10;
    $max_stock_level = !empty($_POST['max_stock_level']) ? (int)$_POST['max_stock_level'] : 100;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    if (empty($product_name) || empty($sku) || empty($category_id) || $unit_price <= 0) {
        $error = "Please fill in all required fields with valid values.";
    } else {
        // Check if SKU already exists
        $check_sku = "SELECT product_id FROM products WHERE sku = '$sku'";
        $check_result = mysqli_query($connection, $check_sku);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "SKU already exists. Please use a different SKU.";
        } else {
            // Handle image upload
            $image_url = 'NULL';
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                $target_dir = "../assets/images/products/";
                
                // Create directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    // Check file size (max 2MB)
                    if ($_FILES["product_image"]["size"] > 2 * 1024 * 1024) {
                        $error = "File size must be less than 2MB.";
                    } else {
                        $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
                        $target_file = $target_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                            $image_url = "'assets/images/products/" . $new_filename . "'";
                        } else {
                            $error = "Failed to upload image.";
                        }
                    }
                } else {
                    $error = "Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.";
                }
            }
            
            // If no error, insert product using regular query (NO PREPARED STATEMENT)
            if (empty($error)) {
                // Handle NULL values
                $cost_price_sql = ($cost_price === 'NULL') ? 'NULL' : $cost_price;
                $image_url_sql = ($image_url === 'NULL') ? 'NULL' : $image_url;
                
                $insert_query = "INSERT INTO products (
                    product_name, sku, description, short_description, category_id,
                    unit_price, cost_price, quantity_in_stock, min_stock_level, max_stock_level,
                    image_url, is_featured, is_active, created_at
                ) VALUES (
                    '$product_name', '$sku', '$description', '$short_description', $category_id,
                    $unit_price, $cost_price_sql, $quantity_in_stock, $min_stock_level, $max_stock_level,
                    $image_url_sql, $is_featured, $is_active, NOW()
                )";
                
                if (mysqli_query($connection, $insert_query)) {
                    $product_id = mysqli_insert_id($connection);
                    
                    // Log the action
                    error_log("Admin {$_SESSION['admin_name']} added product ID: $product_id");
                    
                    header("Location: products.php?success=Product added successfully");
                    exit();
                } else {
                    $error = "Failed to add product: " . mysqli_error($connection);
                }
            }
        }
    }
}

// Include admin header
include '../includes/admin_header.php';
?>

<!-- Rest of your HTML form remains exactly the same -->
<!-- Keep all your existing HTML form code from your original file -->

<!-- Page Content -->
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header-box">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-plus-circle me-2"></i>
                            Add New Product
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add Product</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <a href="products.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add Product Form -->
    <div class="row">
        <div class="col-12">
            <div class="form-card">
                <form method="POST" action="" enctype="multipart/form-data" class="product-form">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-8">
                            <div class="form-section">
                                <h5 class="section-title">Basic Information</h5>
                                
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" 
                                           value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="sku" class="form-label">SKU *</label>
                                        <input type="text" class="form-control" id="sku" name="sku" 
                                               value="<?php echo isset($_POST['sku']) ? htmlspecialchars($_POST['sku']) : ''; ?>" required>
                                        <small class="text-muted">Unique product identifier</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php if ($categories_result): ?>
                                                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                                <option value="<?php echo $category['category_id']; ?>" 
                                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="short_description" class="form-label">Short Description</label>
                                    <textarea class="form-control" id="short_description" name="short_description" rows="2"><?php echo isset($_POST['short_description']) ? htmlspecialchars($_POST['short_description']) : ''; ?></textarea>
                                    <small class="text-muted">Brief description for product listings (max 500 characters)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Full Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pricing and Stock -->
                        <div class="col-md-4">
                            <div class="form-section">
                                <h5 class="section-title">Pricing</h5>
                                
                                <div class="mb-3">
                                    <label for="unit_price" class="form-label">Unit Price ($) *</label>
                                    <input type="number" class="form-control" id="unit_price" name="unit_price" 
                                           value="<?php echo isset($_POST['unit_price']) ? htmlspecialchars($_POST['unit_price']) : ''; ?>" 
                                           step="0.01" min="0" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cost_price" class="form-label">Cost Price ($)</label>
                                    <input type="number" class="form-control" id="cost_price" name="cost_price" 
                                           value="<?php echo isset($_POST['cost_price']) ? htmlspecialchars($_POST['cost_price']) : ''; ?>" 
                                           step="0.01" min="0">
                                    <small class="text-muted">Your purchase cost</small>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h5 class="section-title">Inventory</h5>
                                
                                <div class="mb-3">
                                    <label for="quantity_in_stock" class="form-label">Quantity in Stock</label>
                                    <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock" 
                                           value="<?php echo isset($_POST['quantity_in_stock']) ? htmlspecialchars($_POST['quantity_in_stock']) : '0'; ?>" 
                                           min="0">
                                </div>
                                
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label for="min_stock_level" class="form-label">Min Stock Level</label>
                                        <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" 
                                               value="<?php echo isset($_POST['min_stock_level']) ? htmlspecialchars($_POST['min_stock_level']) : '10'; ?>" 
                                               min="0">
                                    </div>
                                    
                                    <div class="col-6 mb-3">
                                        <label for="max_stock_level" class="form-label">Max Stock Level</label>
                                        <input type="number" class="form-control" id="max_stock_level" name="max_stock_level" 
                                               value="<?php echo isset($_POST['max_stock_level']) ? htmlspecialchars($_POST['max_stock_level']) : '100'; ?>" 
                                               min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h5 class="section-title">Product Image</h5>
                                
                                <div class="mb-3">
                                    <label for="product_image" class="form-label">Upload Image</label>
                                    <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*">
                                    <small class="text-muted">Allowed: JPG, PNG, GIF, WEBP (Max: 2MB)</small>
                                </div>
                                
                                <div class="image-preview" id="imagePreview" style="display: none;">
                                    <img src="" alt="Preview" style="max-width: 100%; max-height: 150px; border-radius: 8px; border: 1px solid var(--border);">
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h5 class="section-title">Status</h5>
                                
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                        <label class="form-check-label" for="is_active">
                                            Active (visible in store)
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                                        <label class="form-check-label" for="is_featured">
                                            Featured Product
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="form-actions">
                                <button type="submit" name="add_product" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Product
                                </button>
                                <a href="products.php" class="btn btn-light">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Page-specific scripts -->
<script>
// Image preview
document.getElementById('product_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Check file size
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            this.value = '';
            document.getElementById('imagePreview').style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.querySelector('img').src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagePreview').style.display = 'none';
    }
});

// Auto-generate SKU from product name (optional)
document.getElementById('product_name').addEventListener('blur', function() {
    const skuField = document.getElementById('sku');
    if (!skuField.value) {
        // Generate SKU from product name: uppercase, remove special chars, add timestamp
        const productName = this.value.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (productName) {
            const timestamp = Date.now().toString().slice(-6);
            skuField.value = productName.substring(0, 8) + '-' + timestamp;
        }
    }
});

// Form validation
document.querySelector('.product-form').addEventListener('submit', function(e) {
    const productName = document.getElementById('product_name').value.trim();
    const sku = document.getElementById('sku').value.trim();
    const category = document.getElementById('category_id').value;
    const price = document.getElementById('unit_price').value;
    
    if (!productName) {
        e.preventDefault();
        alert('Please enter a product name');
        return false;
    }
    
    if (!sku) {
        e.preventDefault();
        alert('Please enter a SKU');
        return false;
    }
    
    if (!category) {
        e.preventDefault();
        alert('Please select a category');
        return false;
    }
    
    if (!price || price <= 0) {
        e.preventDefault();
        alert('Please enter a valid price');
        return false;
    }
});
</script>

<style>
/* Add Product Page Specific Styles */

/* Form Card */
.form-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--shadow-sm);
}

/* Form Sections */
.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--light-gray);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    color: var(--primary);
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--primary-light);
    font-size: 1.1rem;
}

/* Form Elements */
.form-label {
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 0.6rem 1rem;
    transition: var(--transition);
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(30,58,95,0.1);
    outline: none;
}

/* Form Check */
.form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

/* Image Preview */
.image-preview {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--light);
    border-radius: 8px;
    text-align: center;
    border: 1px dashed var(--border);
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
}

.btn {
    padding: 0.6rem 1.5rem;
    font-size: 0.95rem;
    font-weight: 500;
    border-radius: 8px;
    transition: var(--transition);
}

.btn-primary {
    background: var(--primary);
    border: none;
    color: white;
}

.btn-primary:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-light {
    background: white;
    border: 1px solid var(--border);
    color: var(--dark);
}

/* Page Header */
.page-header-box {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border);
}

.page-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.breadcrumb {
    margin-bottom: 0;
    background: transparent;
    padding: 0;
}

.breadcrumb-item a {
    color: var(--primary);
    text-decoration: none;
}

/* Responsive */
@media (max-width: 768px) {
    .form-card {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .form-card {
        padding: 1rem;
    }
    
    .section-title {
        font-size: 1rem;
    }
}
</style>