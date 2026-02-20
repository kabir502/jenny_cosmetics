<?php
// admin/add_category.php - Add New Category Page

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

// Get parent categories for dropdown
$parent_query = "SELECT category_id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name";
$parent_result = mysqli_query($connection, $parent_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    
    // Get form data
    $category_name = mysqli_real_escape_string($connection, trim($_POST['category_name']));
    $description = mysqli_real_escape_string($connection, trim($_POST['description']));
    $parent_category_id = !empty($_POST['parent_category_id']) ? (int)$_POST['parent_category_id'] : null;
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    if (empty($category_name)) {
        $error = "Please enter a category name.";
    } else {
        // Check if category name already exists
        $check_query = "SELECT category_id FROM categories WHERE category_name = ?";
        $check_stmt = mysqli_prepare($connection, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $category_name);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Category name already exists. Please use a different name.";
        } else {
            // Handle image upload
            $image_url = null;
            if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
                $target_dir = "../assets/images/categories/";
                
                // Create directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES["category_image"]["name"], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    // Check file size (max 2MB)
                    if ($_FILES["category_image"]["size"] > 2 * 1024 * 1024) {
                        $error = "File size must be less than 2MB.";
                    } else {
                        $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
                        $target_file = $target_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES["category_image"]["tmp_name"], $target_file)) {
                            $image_url = 'assets/images/categories/' . $new_filename;
                        } else {
                            $error = "Failed to upload image.";
                        }
                    }
                } else {
                    $error = "Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.";
                }
            }
            
            // If no error, insert category
            if (empty($error)) {
                $insert_query = "INSERT INTO categories (
                    category_name, description, parent_category_id, image_url, display_order, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = mysqli_prepare($connection, $insert_query);
                
                // Handle null values
                if ($parent_category_id === null) {
                    $parent_category_id = 'NULL';
                }
                
                mysqli_stmt_bind_param($stmt, "ssisii", 
                    $category_name,           // s
                    $description,              // s
                    $parent_category_id,       // i
                    $image_url,                 // s
                    $display_order,             // i
                    $is_active                   // i
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    $category_id = mysqli_insert_id($connection);
                    
                    // Log the action
                    error_log("Admin {$_SESSION['admin_name']} added category ID: $category_id - $category_name");
                    
                    header("Location: categories.php?success=Category added successfully");
                    exit();
                } else {
                    $error = "Failed to add category: " . mysqli_stmt_error($stmt);
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        mysqli_stmt_close($check_stmt);
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
                            <i class="fas fa-folder-plus me-2"></i>
                            Add New Category
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="categories.php">Categories</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add Category</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <a href="categories.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Categories
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

    <!-- Add Category Form -->
    <div class="row">
        <div class="col-12">
            <div class="form-card">
                <form method="POST" action="" enctype="multipart/form-data" class="category-form">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information -->
                            <div class="form-section">
                                <h5 class="section-title">Basic Information</h5>
                                
                                <div class="mb-3">
                                    <label for="category_name" class="form-label">Category Name *</label>
                                    <input type="text" class="form-control" id="category_name" name="category_name" 
                                           value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name']) : ''; ?>" 
                                           required autofocus>
                                    <small class="text-muted">Unique category name</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                    <small class="text-muted">Brief description of the category (optional)</small>
                                </div>
                            </div>
                            
                            <!-- Parent Category -->
                            <div class="form-section">
                                <h5 class="section-title">Category Hierarchy</h5>
                                
                                <div class="mb-3">
                                    <label for="parent_category_id" class="form-label">Parent Category</label>
                                    <select class="form-select" id="parent_category_id" name="parent_category_id">
                                        <option value="">None (Root Category)</option>
                                        <?php if ($parent_result && mysqli_num_rows($parent_result) > 0): ?>
                                            <?php while ($parent = mysqli_fetch_assoc($parent_result)): ?>
                                            <option value="<?php echo $parent['category_id']; ?>" 
                                                <?php echo (isset($_POST['parent_category_id']) && $_POST['parent_category_id'] == $parent['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($parent['category_name']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                    <small class="text-muted">Select a parent category if this is a subcategory</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="display_order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" 
                                           value="<?php echo isset($_POST['display_order']) ? htmlspecialchars($_POST['display_order']) : '0'; ?>" 
                                           min="0" step="1">
                                    <small class="text-muted">Lower numbers appear first (0 = default)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Category Image -->
                            <div class="form-section">
                                <h5 class="section-title">Category Image</h5>
                                
                                <div class="mb-3">
                                    <label for="category_image" class="form-label">Upload Image</label>
                                    <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
                                    <small class="text-muted">Allowed: JPG, PNG, GIF, WEBP (Max: 2MB)</small>
                                </div>
                                
                                <div class="image-preview" id="imagePreview" style="display: none;">
                                    <img src="" alt="Preview" class="img-fluid rounded" style="max-height: 150px;">
                                </div>
                                
                                <div class="mt-3 text-muted small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Recommended size: 300x300 pixels
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <div class="form-section">
                                <h5 class="section-title">Status</h5>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                    <small class="text-muted d-block mt-1">Inactive categories won't be visible on the website</small>
                                </div>
                            </div>
                            
                            <!-- Preview Card -->
                            <div class="form-section">
                                <h5 class="section-title">Preview</h5>
                                <div class="category-preview card">
                                    <div class="card-body text-center">
                                        <div class="preview-icon mb-2">
                                            <i class="fas fa-folder fa-3x text-primary"></i>
                                        </div>
                                        <h6 class="preview-name mb-1">Category Name</h6>
                                        <p class="preview-parent small text-muted mb-0">Root Category</p>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2 text-center">Preview will update as you type</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="form-actions">
                                <button type="submit" name="add_category" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Category
                                </button>
                                <a href="categories.php" class="btn btn-light">
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
document.getElementById('category_image').addEventListener('change', function(e) {
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

// Live preview update
document.getElementById('category_name').addEventListener('input', function() {
    const name = this.value.trim();
    document.querySelector('.preview-name').textContent = name || 'Category Name';
});

document.getElementById('parent_category_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const parentName = selected.text;
    const previewParent = document.querySelector('.preview-parent');
    
    if (this.value) {
        previewParent.textContent = parentName;
    } else {
        previewParent.textContent = 'Root Category';
    }
});

// Form validation
document.querySelector('.category-form').addEventListener('submit', function(e) {
    const categoryName = document.getElementById('category_name').value.trim();
    
    if (!categoryName) {
        e.preventDefault();
        alert('Please enter a category name');
        return false;
    }
});

// Auto-generate slug (optional - if you have a slug field)
/*
document.getElementById('category_name').addEventListener('blur', function() {
    const name = this.value.trim();
    const slug = name.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
    // If you have a slug field, set it here
    // document.getElementById('slug').value = slug;
});
*/
</script>

<style>
/* Add Category Page Specific Styles */

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

/* Form Switch */
.form-switch {
    padding-left: 2.5em;
}

.form-switch .form-check-input {
    width: 2em;
    margin-left: -2.5em;
    height: 1.25em;
    cursor: pointer;
}

.form-switch .form-check-input:checked {
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

.image-preview img {
    max-width: 100%;
    max-height: 150px;
    border-radius: 8px;
}

/* Category Preview Card */
.category-preview {
    border: 1px solid var(--border);
    border-radius: 12px;
    transition: var(--transition);
    background: var(--light);
}

.category-preview:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.category-preview .card-body {
    padding: 1.5rem;
}

.preview-icon {
    color: var(--primary);
}

.preview-name {
    font-weight: 600;
    color: var(--dark);
}

.preview-parent {
    font-size: 0.85rem;
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

.btn-light:hover {
    background: var(--light);
    border-color: var(--dark-gray);
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
    
    .section-title {
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    .form-card {
        padding: 1rem;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
}

/* Loading State */
.btn-loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;
}

.btn-loading::after {
    content: '';
    position: absolute;
    width: 1.2rem;
    height: 1.2rem;
    top: 50%;
    left: 50%;
    margin-left: -0.6rem;
    margin-top: -0.6rem;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Tooltip */
[data-tooltip] {
    position: relative;
    cursor: help;
}

[data-tooltip]:before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.3rem 0.6rem;
    background: var(--dark);
    color: white;
    font-size: 0.75rem;
    border-radius: 4px;
    white-space: nowrap;
    display: none;
    z-index: 10;
}

[data-tooltip]:hover:before {
    display: block;
}
</style>