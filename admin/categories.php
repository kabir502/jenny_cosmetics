<?php
// admin/categories.php - Categories Management Page

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

// Handle Add/Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add Category
    if (isset($_POST['add_category'])) {
        $category_name = mysqli_real_escape_string($connection, trim($_POST['category_name']));
        $description = mysqli_real_escape_string($connection, trim($_POST['description']));
        $parent_category_id = !empty($_POST['parent_category_id']) ? (int)$_POST['parent_category_id'] : null;
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
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
                $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["category_image"]["tmp_name"], $target_file)) {
                    $image_url = 'assets/images/categories/' . $new_filename;
                }
            }
        }
        
        $insert_query = "INSERT INTO categories (category_name, description, parent_category_id, image_url, display_order, is_active, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = mysqli_prepare($connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssisii", $category_name, $description, $parent_category_id, $image_url, $display_order, $is_active);
        
        if (mysqli_stmt_execute($stmt)) {
            $category_id = mysqli_insert_id($connection);
            $message = "Category '$category_name' added successfully.";
            $message_type = 'success';
            
            // Log the action
            error_log("Admin {$_SESSION['admin_name']} added category ID: $category_id");
        } else {
            $message = "Failed to add category. " . mysqli_error($connection);
            $message_type = 'danger';
        }
    }
    
    // Edit Category
    if (isset($_POST['edit_category'])) {
        $category_id = (int)$_POST['category_id'];
        $category_name = mysqli_real_escape_string($connection, trim($_POST['category_name']));
        $description = mysqli_real_escape_string($connection, trim($_POST['description']));
        $parent_category_id = !empty($_POST['parent_category_id']) ? (int)$_POST['parent_category_id'] : null;
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle image upload
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
            $target_dir = "../assets/images/categories/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["category_image"]["name"], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["category_image"]["tmp_name"], $target_file)) {
                    $image_url = 'assets/images/categories/' . $new_filename;
                    
                    // Delete old image
                    $old_image_query = "SELECT image_url FROM categories WHERE category_id = ?";
                    $old_image_stmt = mysqli_prepare($connection, $old_image_query);
                    mysqli_stmt_bind_param($old_image_stmt, "i", $category_id);
                    mysqli_stmt_execute($old_image_stmt);
                    $old_image_result = mysqli_stmt_get_result($old_image_stmt);
                    $old_image = mysqli_fetch_assoc($old_image_result);
                    
                    if (!empty($old_image['image_url']) && file_exists('../' . $old_image['image_url'])) {
                        unlink('../' . $old_image['image_url']);
                    }
                    
                    // Update with new image
                    $update_query = "UPDATE categories SET category_name = ?, description = ?, parent_category_id = ?, image_url = ?, display_order = ?, is_active = ? WHERE category_id = ?";
                    $stmt = mysqli_prepare($connection, $update_query);
                    mysqli_stmt_bind_param($stmt, "ssisiii", $category_name, $description, $parent_category_id, $image_url, $display_order, $is_active, $category_id);
                }
            }
        } else {
            // Update without image
            $update_query = "UPDATE categories SET category_name = ?, description = ?, parent_category_id = ?, display_order = ?, is_active = ? WHERE category_id = ?";
            $stmt = mysqli_prepare($connection, $update_query);
            mysqli_stmt_bind_param($stmt, "ssiiii", $category_name, $description, $parent_category_id, $display_order, $is_active, $category_id);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Category '$category_name' updated successfully.";
            $message_type = 'success';
            
            // Log the action
            error_log("Admin {$_SESSION['admin_name']} updated category ID: $category_id");
        } else {
            $message = "Failed to update category. " . mysqli_error($connection);
            $message_type = 'danger';
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    // Check if category has products
    $check_products = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $check_stmt = mysqli_prepare($connection, $check_products);
    mysqli_stmt_bind_param($check_stmt, "i", $category_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $product_count = mysqli_fetch_assoc($check_result)['count'];
    
    if ($product_count > 0) {
        $message = "Cannot delete category. It has $product_count products associated with it.";
        $message_type = 'danger';
    } else {
        // Check if category has subcategories
        $check_sub = "SELECT COUNT(*) as count FROM categories WHERE parent_category_id = ?";
        $check_sub_stmt = mysqli_prepare($connection, $check_sub);
        mysqli_stmt_bind_param($check_sub_stmt, "i", $category_id);
        mysqli_stmt_execute($check_sub_stmt);
        $check_sub_result = mysqli_stmt_get_result($check_sub_stmt);
        $sub_count = mysqli_fetch_assoc($check_sub_result)['count'];
        
        if ($sub_count > 0) {
            $message = "Cannot delete category. It has $sub_count subcategories.";
            $message_type = 'danger';
        } else {
            // Get image before deleting
            $image_query = "SELECT image_url FROM categories WHERE category_id = ?";
            $image_stmt = mysqli_prepare($connection, $image_query);
            mysqli_stmt_bind_param($image_stmt, "i", $category_id);
            mysqli_stmt_execute($image_stmt);
            $image_result = mysqli_stmt_get_result($image_stmt);
            $category = mysqli_fetch_assoc($image_result);
            
            // Delete category
            $delete_query = "DELETE FROM categories WHERE category_id = ?";
            $delete_stmt = mysqli_prepare($connection, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $category_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                // Delete image file if exists
                if (!empty($category['image_url']) && file_exists('../' . $category['image_url'])) {
                    unlink('../' . $category['image_url']);
                }
                
                $message = "Category deleted successfully.";
                $message_type = 'success';
                
                // Log the action
                error_log("Admin {$_SESSION['admin_name']} deleted category ID: $category_id");
            } else {
                $message = "Failed to delete category.";
                $message_type = 'danger';
            }
        }
    }
}

// Toggle Category Status
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $category_id = (int)$_GET['toggle_status'];
    
    $toggle_query = "UPDATE categories SET is_active = NOT is_active WHERE category_id = ?";
    $toggle_stmt = mysqli_prepare($connection, $toggle_query);
    mysqli_stmt_bind_param($toggle_stmt, "i", $category_id);
    
    if (mysqli_stmt_execute($toggle_stmt)) {
        $message = "Category status updated successfully.";
        $message_type = 'success';
    } else {
        $message = "Failed to update category status.";
        $message_type = 'danger';
    }
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';

// Build query conditions
$where_conditions = [];
if (!empty($search)) {
    $where_conditions[] = "(category_name LIKE '%$search%' OR description LIKE '%$search%')";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total FROM categories $where_clause";
$count_result = mysqli_query($connection, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get categories with pagination
$categories_query = "SELECT 
                        c.*,
                        (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as product_count,
                        (SELECT category_name FROM categories WHERE category_id = c.parent_category_id) as parent_name
                    FROM categories c
                    $where_clause
                    ORDER BY c.display_order ASC, c.category_id DESC
                    LIMIT $offset, $records_per_page";

$categories_result = mysqli_query($connection, $categories_query);

// Get all categories for parent dropdown (excluding current category when editing)
$all_categories_query = "SELECT category_id, category_name FROM categories ORDER BY display_order, category_name";
$all_categories_result = mysqli_query($connection, $all_categories_query);

// Get category statistics
$stats_query = "SELECT 
                    COUNT(*) as total_categories,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_categories,
                    SUM(CASE WHEN parent_category_id IS NULL THEN 1 ELSE 0 END) as parent_categories,
                    (SELECT COUNT(*) FROM products) as total_products
                FROM categories";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

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
                            <i class="fas fa-folder me-2"></i>
                            Categories Management
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Categories</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add New Category
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($message)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(30,58,95,0.1); color: var(--primary);">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Categories</span>
                    <span class="stat-value"><?php echo $stats['total_categories']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(25,135,84,0.1); color: var(--success);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Active Categories</span>
                    <span class="stat-value"><?php echo $stats['active_categories']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(13,202,240,0.1); color: var(--info);">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Parent Categories</span>
                    <span class="stat-value"><?php echo $stats['parent_categories']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255,193,7,0.1); color: var(--warning);">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Products</span>
                    <span class="stat-value"><?php echo $stats['total_products']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="filters-card">
                <form method="GET" action="" class="filters-form">
                    <div class="row g-3">
                        <div class="col-lg-8 col-md-6">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search categories by name or description..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-lg-4 col-12">
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Search
                                </button>
                                <a href="categories.php" class="btn btn-light w-100 mt-2 mt-lg-0">
                                    <i class="fas fa-times me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <h5><i class="fas fa-list me-2"></i>Categories List</h5>
                        <span class="records-count">Showing <?php echo min($offset + 1, $total_records); ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records</span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table categories-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Category Name</th>
                                <th>Parent Category</th>
                                <th>Display Order</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($categories_result) > 0): ?>
                                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                <tr>
                                    <td>
                                        <span class="category-id">#<?php echo $category['category_id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="category-image">
                                            <?php if (!empty($category['image_url'])): ?>
                                                <img src="../<?php echo $category['image_url']; ?>" 
                                                     alt="<?php echo htmlspecialchars($category['category_name']); ?>"
                                                     onerror="this.src='../assets/images/no-image.jpg'">
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="fas fa-folder"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="category-info">
                                            <div class="category-name">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </div>
                                            <?php if (!empty($category['description'])): ?>
                                            <div class="category-description small text-muted">
                                                <?php echo substr(htmlspecialchars($category['description']), 0, 50) . (strlen($category['description']) > 50 ? '...' : ''); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($category['parent_name'])): ?>
                                            <span class="parent-category">
                                                <i class="fas fa-level-up-alt me-1"></i>
                                                <?php echo htmlspecialchars($category['parent_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="parent-category root">Root Category</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="display-order"><?php echo $category['display_order']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="product-count <?php echo $category['product_count'] > 0 ? 'has-products' : ''; ?>">
                                            <?php echo $category['product_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?toggle_status=<?php echo $category['category_id']; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="status-badge status-<?php echo $category['is_active'] ? 'active' : 'inactive'; ?>"
                                           onclick="return confirm('Toggle status for this category?')">
                                            <i class="fas fa-<?php echo $category['is_active'] ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="category-date">
                                            <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action view" title="View Products" 
                                                    onclick="viewProducts(<?php echo $category['category_id']; ?>)">
                                                <i class="fas fa-box"></i>
                                            </button>
                                            <button class="btn-action edit" title="Edit Category" 
                                                    onclick="editCategory(<?php echo $category['category_id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action delete" title="Delete Category" 
                                                    onclick="confirmDelete(<?php echo $category['category_id']; ?>)"
                                                    <?php echo $category['product_count'] > 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--light-gray);"></i>
                                        <p class="mt-3 mb-0">No categories found</p>
                                        <p class="small text-muted">Click "Add New Category" to create your first category</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="table-footer">
                    <nav aria-label="Categories pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">
                    <i class="fas fa-plus-circle me-2" style="color: var(--primary);"></i>
                    Add New Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="parent_category_id" class="form-label">Parent Category</label>
                            <select class="form-select" id="parent_category_id" name="parent_category_id">
                                <option value="">None (Root Category)</option>
                                <?php 
                                mysqli_data_seek($all_categories_result, 0);
                                while ($parent = mysqli_fetch_assoc($all_categories_result)): 
                                ?>
                                <option value="<?php echo $parent['category_id']; ?>">
                                    <?php echo htmlspecialchars($parent['category_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="display_order" name="display_order" value="0" min="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="category_image" class="form-label">Category Image</label>
                            <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
                            <small class="text-muted">Allowed: JPG, PNG, GIF, WEBP (Max: 2MB)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">
                    <i class="fas fa-edit me-2" style="color: var(--primary);"></i>
                    Edit Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="editCategoryForm">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_category_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_parent_category_id" class="form-label">Parent Category</label>
                            <select class="form-select" id="edit_parent_category_id" name="parent_category_id">
                                <option value="">None (Root Category)</option>
                                <?php 
                                mysqli_data_seek($all_categories_result, 0);
                                while ($parent = mysqli_fetch_assoc($all_categories_result)): 
                                ?>
                                <option value="<?php echo $parent['category_id']; ?>">
                                    <?php echo htmlspecialchars($parent['category_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_display_order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="edit_display_order" name="display_order" value="0" min="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_category_image" class="form-label">Category Image</label>
                            <input type="file" class="form-control" id="edit_category_image" name="category_image" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-12" id="current_image_container" style="display: none;">
                            <label class="form-label">Current Image</label>
                            <div>
                                <img id="current_image" src="" alt="Current category image" style="max-height: 100px; border-radius: 8px; border: 1px solid var(--border);">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Page-specific scripts -->
<script>
// Edit category function
function editCategory(categoryId) {
    // Fetch category details via AJAX
    fetch('get_category.php?id=' + categoryId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_category_id').value = data.category.category_id;
                document.getElementById('edit_category_name').value = data.category.category_name;
                document.getElementById('edit_description').value = data.category.description || '';
                document.getElementById('edit_parent_category_id').value = data.category.parent_category_id || '';
                document.getElementById('edit_display_order').value = data.category.display_order || 0;
                document.getElementById('edit_is_active').checked = data.category.is_active == 1;
                
                if (data.category.image_url) {
                    document.getElementById('current_image').src = '../' + data.category.image_url;
                    document.getElementById('current_image_container').style.display = 'block';
                } else {
                    document.getElementById('current_image_container').style.display = 'none';
                }
                
                new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
            } else {
                showNotification('Failed to load category details', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'danger');
        });
}

// View products in category
function viewProducts(categoryId) {
    window.location.href = 'products.php?category_id=' + categoryId;
}

// Confirm delete
function confirmDelete(categoryId) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        window.location.href = 'categories.php?delete=' + categoryId + '&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>';
    }
}

// Image preview
document.getElementById('category_image')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            this.value = '';
        }
    }
});

document.getElementById('edit_category_image')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            this.value = '';
        }
    }
});
</script>

<style>
/* ===== CATEGORIES PAGE SPECIFIC STYLES ===== */

/* Stat Cards */
.stat-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.stat-details {
    flex: 1;
}

.stat-label {
    display: block;
    font-size: 0.85rem;
    color: var(--dark-gray);
    margin-bottom: 0.25rem;
}

.stat-value {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.2;
}

/* Categories Table */
.categories-table {
    margin: 0;
}

.categories-table thead th {
    background: var(--light);
    color: var(--dark);
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
    padding: 1rem;
    white-space: nowrap;
}

.categories-table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--border);
}

.categories-table tbody tr:hover {
    background: var(--light);
}

/* Category ID */
.category-id {
    font-weight: 600;
    color: var(--primary);
    font-family: var(--font-mono);
}

/* Category Image */
.category-image {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: var(--light);
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--light);
    color: var(--dark-gray);
    font-size: 1.5rem;
}

/* Category Info */
.category-info {
    line-height: 1.4;
}

.category-name {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.2rem;
}

.category-description {
    color: var(--dark-gray);
    font-size: 0.8rem;
    max-width: 250px;
}

/* Parent Category */
.parent-category {
    font-size: 0.9rem;
    color: var(--dark);
    background: var(--light);
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.parent-category.root {
    background: transparent;
    color: var(--dark-gray);
    font-style: italic;
}

.parent-category i {
    color: var(--primary);
}

/* Display Order */
.display-order {
    display: inline-block;
    min-width: 30px;
    padding: 0.3rem 0.6rem;
    background: var(--light);
    border-radius: 20px;
    text-align: center;
    font-weight: 600;
    color: var(--dark);
}

/* Product Count */
.product-count {
    display: inline-block;
    min-width: 30px;
    height: 30px;
    background: var(--light);
    border-radius: 15px;
    text-align: center;
    line-height: 30px;
    font-weight: 600;
    color: var(--dark);
}

.product-count.has-products {
    background: var(--primary-light);
    color: white;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: var(--transition);
}

.status-badge:hover {
    transform: translateY(-2px);
    text-decoration: none;
}

.status-active {
    background: #d1e7dd;
    color: #0a3622;
}

.status-inactive {
    background: #f8d7da;
    color: #842029;
}

.status-active i {
    color: var(--success);
}

.status-inactive i {
    color: var(--danger);
}

/* Category Date */
.category-date {
    font-size: 0.9rem;
    color: var(--dark-gray);
    white-space: nowrap;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: white;
    color: var(--dark-gray);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
}

.btn-action:hover {
    background: var(--light);
    transform: translateY(-2px);
}

.btn-action.view:hover {
    color: var(--primary);
    border-color: var(--primary);
}

.btn-action.edit:hover {
    color: var(--warning);
    border-color: var(--warning);
}

.btn-action.delete:hover {
    color: var(--danger);
    border-color: var(--danger);
}

.btn-action.delete:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* Modal Form Styles */
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

.form-check-input:checked {
    background-color: var(--primary);
    border-color: var(--primary);
}

/* Modal Footer */
.modal-footer {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--border);
    background: var(--light);
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .categories-table thead th {
        font-size: 0.75rem;
        padding: 0.75rem;
    }
    
    .categories-table tbody td {
        padding: 0.75rem;
        font-size: 0.85rem;
    }
    
    .category-image {
        width: 40px;
        height: 40px;
    }
    
    .action-buttons {
        gap: 0.3rem;
    }
    
    .btn-action {
        width: 28px;
        height: 28px;
    }
}

@media (max-width: 576px) {
    .category-description {
        max-width: 150px;
    }
    
    .status-badge {
        padding: 0.3rem 0.6rem;
        font-size: 0.75rem;
    }
    
    .parent-category {
        font-size: 0.8rem;
        padding: 0.2rem 0.6rem;
    }
}

/* Print Styles */
@media print {
    .btn,
    .btn-action,
    .status-badge,
    .filter-actions,
    .back-to-top {
        display: none !important;
    }
    
    .table-card {
        border: 1px solid #000;
        box-shadow: none;
    }
    
    .category-image {
        border: 1px solid #000;
    }
    
    .status-badge {
        border: 1px solid #000;
        background: transparent !important;
        color: #000 !important;
    }
}
</style>