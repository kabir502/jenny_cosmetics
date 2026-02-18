<?php
// admin/edit_product.php - Edit Product Page

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

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: products.php?error=Invalid product ID");
    exit();
}

// Get product details
$product_query = "SELECT * FROM products WHERE product_id = ?";
$product_stmt = mysqli_prepare($connection, $product_query);
mysqli_stmt_bind_param($product_stmt, "i", $product_id);
mysqli_stmt_execute($product_stmt);
$product_result = mysqli_stmt_get_result($product_stmt);

if (mysqli_num_rows($product_result) == 0) {
    header("Location: products.php?error=Product not found");
    exit();
}

$product = mysqli_fetch_assoc($product_result);

// Get categories for dropdown
$categories_query = "SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name";
$categories_result = mysqli_query($connection, $categories_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    
    // Get form data
    $product_name = mysqli_real_escape_string($connection, trim($_POST['product_name']));
    $sku = mysqli_real_escape_string($connection, trim($_POST['sku']));
    $description = mysqli_real_escape_string($connection, trim($_POST['description']));
    $short_description = mysqli_real_escape_string($connection, trim($_POST['short_description']));
    $category_id = (int)$_POST['category_id'];
    $unit_price = (float)$_POST['unit_price'];
    $cost_price = !empty($_POST['cost_price']) ? (float)$_POST['cost_price'] : null;
    $quantity_in_stock = (int)$_POST['quantity_in_stock'];
    $min_stock_level = (int)$_POST['min_stock_level'];
    $max_stock_level = (int)$_POST['max_stock_level'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    if (empty($product_name) || empty($sku) || empty($category_id) || empty($unit_price)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if SKU already exists for another product
        $check_sku = "SELECT product_id FROM products WHERE sku = ? AND product_id != ?";
        $check_stmt = mysqli_prepare($connection, $check_sku);
        mysqli_stmt_bind_param($check_stmt, "si", $sku, $product_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "SKU already exists. Please use a different SKU.";
        } else {
            // Handle image upload
            $image_url = $product['image_url']; // Keep existing image by default
            
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
                            // Delete old image if exists
                            if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
                                unlink('../' . $product['image_url']);
                            }
                            $image_url = 'assets/images/products/' . $new_filename;
                        } else {
                            $error = "Failed to upload image.";
                        }
                    }
                } else {
                    $error = "Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.";
                }
            }
            
            // Handle image removal
            if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
                    unlink('../' . $product['image_url']);
                }
                $image_url = null;
            }
            
            // If no error, update product
            if (empty($error)) {
                $update_query = "UPDATE products SET 
                    product_name = ?, 
                    sku = ?, 
                    description = ?, 
                    short_description = ?, 
                    category_id = ?, 
                    unit_price = ?, 
                    cost_price = ?, 
                    quantity_in_stock = ?, 
                    min_stock_level = ?, 
                    max_stock_level = ?, 
                    image_url = ?, 
                    is_featured = ?, 
                    is_active = ?,
                    updated_at = NOW()
                    WHERE product_id = ?";
                
                $stmt = mysqli_prepare($connection, $update_query);
                mysqli_stmt_bind_param($stmt, "ssssiddiiissii", 
                    $product_name, $sku, $description, $short_description, $category_id,
                    $unit_price, $cost_price, $quantity_in_stock, $min_stock_level, $max_stock_level,
                    $image_url, $is_featured, $is_active, $product_id
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    // Log the action
                    error_log("Admin {$_SESSION['admin_name']} updated product ID: $product_id");
                    
                    header("Location: products.php?success=Product updated successfully");
                    exit();
                } else {
                    $error = "Failed to update product. " . mysqli_error($connection);
                }
            }
        }
    }
}

// Include admin header
include '../includes/admin_header.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header-box">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-edit me-2"></i>
                            Edit Product
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Product</li>
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

    <!-- Edit Product Form -->
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
                                           value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="sku" class="form-label">SKU *</label>
                                        <input type="text" class="form-control" id="sku" name="sku" 
                                               value="<?php echo htmlspecialchars($product['sku']); ?>" required>
                                        <small class="text-muted">Unique product identifier</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php if ($categories_result): ?>
                                                <?php mysqli_data_seek($categories_result, 0); ?>
                                                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                                <option value="<?php echo $category['category_id']; ?>" 
                                                    <?php echo ($product['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="short_description" class="form-label">Short Description</label>
                                    <textarea class="form-control" id="short_description" name="short_description" rows="2"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                                    <small class="text-muted">Brief description for product listings (max 500 characters)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Full Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
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
                                           value="<?php echo htmlspecialchars($product['unit_price']); ?>" 
                                           step="0.01" min="0" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cost_price" class="form-label">Cost Price ($)</label>
                                    <input type="number" class="form-control" id="cost_price" name="cost_price" 
                                           value="<?php echo htmlspecialchars($product['cost_price'] ?? ''); ?>" 
                                           step="0.01" min="0">
                                    <small class="text-muted">Your purchase cost</small>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h5 class="section-title">Inventory</h5>
                                
                                <div class="mb-3">
                                    <label for="quantity_in_stock" class="form-label">Quantity in Stock</label>
                                    <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock" 
                                           value="<?php echo htmlspecialchars($product['quantity_in_stock']); ?>" 
                                           min="0">
                                </div>
                                
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label for="min_stock_level" class="form-label">Min Stock Level</label>
                                        <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" 
                                               value="<?php echo htmlspecialchars($product['min_stock_level']); ?>" 
                                               min="0">
                                    </div>
                                    
                                    <div class="col-6 mb-3">
                                        <label for="max_stock_level" class="form-label">Max Stock Level</label>
                                        <input type="number" class="form-control" id="max_stock_level" name="max_stock_level" 
                                               value="<?php echo htmlspecialchars($product['max_stock_level']); ?>" 
                                               min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h5 class="section-title">Product Image</h5>
                                
                                <?php if (!empty($product['image_url'])): ?>
                                <div class="current-image mb-3">
                                    <label class="form-label">Current Image</label>
                                    <div class="image-preview">
                                        <img src="../<?php echo $product['image_url']; ?>" alt="Current product image">
                                        <div class="image-actions">
                                            <label class="btn btn-sm btn-light">
                                                <input type="checkbox" name="remove_image" value="1" id="remove_image" style="display: none;">
                                                <i class="fas fa-trash-alt me-1"></i> Remove Image
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="product_image" class="form-label"><?php echo !empty($product['image_url']) ? 'Change Image' : 'Upload Image'; ?></label>
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
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active (visible in store)
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured"
                                               <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_featured">
                                            Featured Product
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h5 class="section-title">Statistics</h5>
                                <div class="stats-display">
                                    <div class="stat-row">
                                        <span class="stat-label">Total Sold:</span>
                                        <span class="stat-value"><?php echo $product['total_sold'] ?? 0; ?></span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat-label">Rating:</span>
                                        <span class="stat-value"><?php echo $product['rating'] ?? 0; ?> / 5</span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat-label">Reviews:</span>
                                        <span class="stat-value"><?php echo $product['review_count'] ?? 0; ?></span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat-label">Created:</span>
                                        <span class="stat-value"><?php echo date('M d, Y', strtotime($product['created_at'])); ?></span>
                                    </div>
                                    <?php if (!empty($product['updated_at'])): ?>
                                    <div class="stat-row">
                                        <span class="stat-label">Updated:</span>
                                        <span class="stat-value"><?php echo date('M d, Y', strtotime($product['updated_at'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="form-actions">
                                <button type="submit" name="edit_product" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Product
                                </button>
                                <a href="products.php" class="btn btn-light">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="button" class="btn btn-danger ms-auto" onclick="confirmDelete(<?php echo $product_id; ?>)">
                                    <i class="fas fa-trash-alt me-2"></i>Delete Product
                                </button>
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
// Image preview for new image
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
        
        // Uncheck remove image if it was checked
        document.getElementById('remove_image').checked = false;
    } else {
        document.getElementById('imagePreview').style.display = 'none';
    }
});

// Handle remove image checkbox
document.getElementById('remove_image')?.addEventListener('change', function(e) {
    if (this.checked) {
        // Clear file input and hide preview
        document.getElementById('product_image').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        
        // Hide current image container
        const currentImage = document.querySelector('.current-image');
        if (currentImage) {
            currentImage.style.opacity = '0.5';
        }
    } else {
        const currentImage = document.querySelector('.current-image');
        if (currentImage) {
            currentImage.style.opacity = '1';
        }
    }
});

// Style the remove image checkbox as a button
document.getElementById('remove_image')?.addEventListener('click', function(e) {
    // The checkbox is hidden, this is just to handle the label click
});

// Confirm delete
function confirmDelete(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        window.location.href = 'delete_product.php?id=' + productId;
    }
}

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

// Warn before leaving if form is dirty
let formChanged = false;

document.querySelectorAll('.product-form input, .product-form textarea, .product-form select').forEach(input => {
    input.addEventListener('change', () => {
        formChanged = true;
    });
    input.addEventListener('keyup', () => {
        formChanged = true;
    });
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
    }
});

// Reset form changed flag after submission
document.querySelector('.product-form').addEventListener('submit', function() {
    formChanged = false;
});
</script>

<style>
/* Edit Product Page Specific Styles */

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

/* Current Image */
.current-image {
    margin-bottom: 1rem;
}

.image-preview {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: var(--light);
    border-radius: 8px;
    text-align: center;
    border: 1px dashed var(--border);
    position: relative;
}

.image-preview img {
    max-width: 100%;
    max-height: 150px;
    border-radius: 8px;
}

.image-actions {
    margin-top: 0.5rem;
}

.image-actions .btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.8rem;
}

/* Statistics Display */
.stats-display {
    background: var(--light);
    border-radius: 8px;
    padding: 1rem;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px dashed var(--border);
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-label {
    color: var(--dark-gray);
    font-size: 0.9rem;
}

.stat-value {
    font-weight: 600;
    color: var(--dark);
}

/* Form Check */
.form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

.form-check-label {
    color: var(--dark);
    cursor: pointer;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
    align-items: center;
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

.btn-light:hover {
    background: var(--light);
    border-color: var(--dark-gray);
}

.btn-danger {
    background: var(--danger);
    border: none;
    color: white;
}

.btn-danger:hover {
    background: #bb2d3b;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220,53,69,0.3);
}

.ms-auto {
    margin-left: auto;
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
    
    .ms-auto {
        margin-left: 0;
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

.breadcrumb-item.active {
    color: var(--dark-gray);
}
</style>