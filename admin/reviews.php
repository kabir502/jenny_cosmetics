<?php
// admin/reviews.php - Product Reviews Management Page

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

// Handle Approve/Reject Review
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $review_id = (int)$_GET['approve'];
    
    $update_query = "UPDATE product_reviews SET is_approved = 1 WHERE review_id = ?";
    $update_stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $review_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $message = "Review approved successfully.";
        $message_type = 'success';
        
        // Log the action
        error_log("Admin {$_SESSION['admin_name']} approved review ID: $review_id");
    } else {
        $message = "Failed to approve review.";
        $message_type = 'danger';
    }
}

if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $review_id = (int)$_GET['reject'];
    
    $update_query = "UPDATE product_reviews SET is_approved = 0 WHERE review_id = ?";
    $update_stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $review_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $message = "Review rejected successfully.";
        $message_type = 'success';
        
        // Log the action
        error_log("Admin {$_SESSION['admin_name']} rejected review ID: $review_id");
    } else {
        $message = "Failed to reject review.";
        $message_type = 'danger';
    }
}

// Handle Delete Review
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $review_id = (int)$_GET['delete'];
    
    // Get review details for logging
    $get_query = "SELECT pr.*, p.product_name, CONCAT(u.first_name, ' ', u.last_name) as user_name 
                  FROM product_reviews pr
                  JOIN products p ON pr.product_id = p.product_id
                  JOIN users u ON pr.user_id = u.user_id
                  WHERE pr.review_id = ?";
    $get_stmt = mysqli_prepare($connection, $get_query);
    mysqli_stmt_bind_param($get_stmt, "i", $review_id);
    mysqli_stmt_execute($get_stmt);
    $get_result = mysqli_stmt_get_result($get_stmt);
    $review_data = mysqli_fetch_assoc($get_result);
    
    // Delete review
    $delete_query = "DELETE FROM product_reviews WHERE review_id = ?";
    $delete_stmt = mysqli_prepare($connection, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $review_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $message = "Review deleted successfully.";
        $message_type = 'success';
        
        // Update product rating (recalculate average)
        updateProductRating($connection, $review_data['product_id']);
        
        // Log the action
        error_log("Admin {$_SESSION['admin_name']} deleted review ID: $review_id for product: {$review_data['product_name']}");
    } else {
        $message = "Failed to delete review.";
        $message_type = 'danger';
    }
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    
    if (!empty($selected_ids)) {
        $ids_string = implode(',', array_map('intval', $selected_ids));
        
        if ($action == 'approve') {
            $query = "UPDATE product_reviews SET is_approved = 1 WHERE review_id IN ($ids_string)";
            $message = "Selected reviews approved successfully.";
        } elseif ($action == 'reject') {
            $query = "UPDATE product_reviews SET is_approved = 0 WHERE review_id IN ($ids_string)";
            $message = "Selected reviews rejected successfully.";
        } elseif ($action == 'delete') {
            // Get product IDs for rating recalculation
            $product_query = "SELECT DISTINCT product_id FROM product_reviews WHERE review_id IN ($ids_string)";
            $product_result = mysqli_query($connection, $product_query);
            $product_ids = [];
            while ($row = mysqli_fetch_assoc($product_result)) {
                $product_ids[] = $row['product_id'];
            }
            
            $query = "DELETE FROM product_reviews WHERE review_id IN ($ids_string)";
            $message = "Selected reviews deleted successfully.";
        }
        
        if (isset($query)) {
            if (mysqli_query($connection, $query)) {
                // Recalculate ratings for affected products
                if ($action == 'delete' && !empty($product_ids)) {
                    foreach ($product_ids as $pid) {
                        updateProductRating($connection, $pid);
                    }
                }
                $message_type = 'success';
                
                // Log the action
                error_log("Admin {$_SESSION['admin_name']} performed bulk $action on " . count($selected_ids) . " reviews");
            } else {
                $message = "Failed to perform bulk action.";
                $message_type = 'danger';
            }
        }
    } else {
        $message = "No reviews selected.";
        $message_type = 'warning';
    }
}

// Function to update product rating
function updateProductRating($connection, $product_id) {
    // Calculate average rating
    $rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as count 
                     FROM product_reviews 
                     WHERE product_id = ? AND is_approved = 1";
    $rating_stmt = mysqli_prepare($connection, $rating_query);
    mysqli_stmt_bind_param($rating_stmt, "i", $product_id);
    mysqli_stmt_execute($rating_stmt);
    $rating_result = mysqli_stmt_get_result($rating_stmt);
    $rating_data = mysqli_fetch_assoc($rating_result);
    
    $avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 2) : 0;
    $review_count = $rating_data['count'];
    
    // Update product
    $update_query = "UPDATE products SET rating = ?, review_count = ? WHERE product_id = ?";
    $update_stmt = mysqli_prepare($connection, $update_query);
    mysqli_stmt_bind_param($update_stmt, "dii", $avg_rating, $review_count, $product_id);
    mysqli_stmt_execute($update_stmt);
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($connection, $_GET['search']) : '';
$product_filter = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($connection, $_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query conditions
$where_conditions = [];

if (!empty($search)) {
    $where_conditions[] = "(pr.title LIKE '%$search%' OR pr.comment LIKE '%$search%' OR 
                            u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR 
                            p.product_name LIKE '%$search%')";
}

if ($product_filter > 0) {
    $where_conditions[] = "pr.product_id = $product_filter";
}

if ($rating_filter > 0) {
    $where_conditions[] = "pr.rating = $rating_filter";
}

if ($status_filter !== '') {
    if ($status_filter == 'approved') {
        $where_conditions[] = "pr.is_approved = 1";
    } elseif ($status_filter == 'pending') {
        $where_conditions[] = "pr.is_approved = 0";
    }
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(pr.created_at) >= '$date_from'";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(pr.created_at) <= '$date_to'";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM product_reviews pr
                JOIN users u ON pr.user_id = u.user_id
                JOIN products p ON pr.product_id = p.product_id
                $where_clause";
$count_result = mysqli_query($connection, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get reviews with pagination
$reviews_query = "SELECT 
                    pr.*,
                    u.first_name,
                    u.last_name,
                    u.email,
                    p.product_name,
                    p.sku,
                    p.image_url
                FROM product_reviews pr
                JOIN users u ON pr.user_id = u.user_id
                JOIN products p ON pr.product_id = p.product_id
                $where_clause
                ORDER BY pr.created_at DESC
                LIMIT $offset, $records_per_page";

$reviews_result = mysqli_query($connection, $reviews_query);

// Get products for filter dropdown
$products_query = "SELECT product_id, product_name FROM products WHERE is_active = 1 ORDER BY product_name";
$products_result = mysqli_query($connection, $products_query);

// Get review statistics
$stats_query = "SELECT 
                    COUNT(*) as total_reviews,
                    SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_reviews,
                    SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_reviews,
                    AVG(rating) as avg_rating,
                    MAX(rating) as max_rating,
                    MIN(rating) as min_rating,
                    COUNT(DISTINCT product_id) as products_with_reviews,
                    COUNT(DISTINCT user_id) as reviewers
                FROM product_reviews";
$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get rating distribution
$rating_dist_query = "SELECT 
                        rating,
                        COUNT(*) as count
                      FROM product_reviews
                      GROUP BY rating
                      ORDER BY rating DESC";
$rating_dist_result = mysqli_query($connection, $rating_dist_query);
$rating_distribution = [];
while ($row = mysqli_fetch_assoc($rating_dist_result)) {
    $rating_distribution[$row['rating']] = $row['count'];
}

// Get recent activity
$recent_query = "SELECT 
                    pr.*,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    p.product_name
                FROM product_reviews pr
                JOIN users u ON pr.user_id = u.user_id
                JOIN products p ON pr.product_id = p.product_id
                ORDER BY pr.created_at DESC
                LIMIT 5";
$recent_result = mysqli_query($connection, $recent_query);

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
                            <i class="fas fa-star me-2"></i>
                            Product Reviews Management
                        </h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Reviews</li>
                            </ol>
                        </nav>
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
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(30,58,95,0.1); color: var(--primary);">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Total Reviews</span>
                    <span class="stat-value"><?php echo $stats['total_reviews']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(25,135,84,0.1); color: var(--success);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Approved</span>
                    <span class="stat-value"><?php echo $stats['approved_reviews']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255,193,7,0.1); color: var(--warning);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value"><?php echo $stats['pending_reviews']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(13,202,240,0.1); color: var(--info);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Avg Rating</span>
                    <span class="stat-value"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(108,117,125,0.1); color: var(--dark-gray);">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Products</span>
                    <span class="stat-value"><?php echo $stats['products_with_reviews']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(111,66,193,0.1); color: #6f42c1;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <span class="stat-label">Reviewers</span>
                    <span class="stat-value"><?php echo $stats['reviewers']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Distribution -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="rating-distribution">
                <div class="distribution-header">
                    <h6><i class="fas fa-chart-pie me-2"></i>Rating Distribution</h6>
                </div>
                <div class="distribution-body">
                    <?php for ($i = 5; $i >= 1; $i--): 
                        $count = $rating_distribution[$i] ?? 0;
                        $percentage = $stats['total_reviews'] > 0 ? round(($count / $stats['total_reviews']) * 100) : 0;
                    ?>
                    <div class="rating-row">
                        <div class="rating-label">
                            <?php echo $i; ?> <i class="fas fa-star" style="color: #ffc107;"></i>
                        </div>
                        <div class="rating-bar-container">
                            <div class="rating-bar" style="width: <?php echo $percentage; ?>%; background: <?php 
                                echo $i >= 4 ? '#28a745' : ($i >= 3 ? '#ffc107' : '#dc3545'); 
                            ?>;"></div>
                        </div>
                        <div class="rating-count">
                            <?php echo $count; ?> (<?php echo $percentage; ?>%)
                        </div>
                    </div>
                    <?php endfor; ?>
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
                        <div class="col-lg-3 col-md-12">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search reviews, customers, products..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <select class="form-select" name="product_id">
                                <option value="0">All Products</option>
                                <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                                <option value="<?php echo $product['product_id']; ?>" 
                                    <?php echo $product_filter == $product['product_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-1 col-md-3">
                            <select class="form-select" name="rating">
                                <option value="0">Rating</option>
                                <option value="5" <?php echo $rating_filter == 5 ? 'selected' : ''; ?>>5 ★</option>
                                <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>4 ★</option>
                                <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>3 ★</option>
                                <option value="2" <?php echo $rating_filter == 2 ? 'selected' : ''; ?>>2 ★</option>
                                <option value="1" <?php echo $rating_filter == 1 ? 'selected' : ''; ?>>1 ★</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-1 col-md-3">
                            <select class="form-select" name="status">
                                <option value="">Status</option>
                                <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <input type="date" class="form-control" name="date_from" 
                                   placeholder="From Date" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <input type="date" class="form-control" name="date_to" 
                                   placeholder="To Date" value="<?php echo $date_to; ?>">
                        </div>
                        
                        <div class="col-lg-1 col-12">
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reviews Table -->
    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">
                        <h5><i class="fas fa-list me-2"></i>Reviews List</h5>
                        <span class="records-count">Showing <?php echo min($offset + 1, $total_records); ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> records</span>
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-light" onclick="exportToCSV()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                </div>
                
                <form method="POST" action="" id="bulkActionForm">
                <div class="bulk-actions-bar">
                    <div class="bulk-select">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                        <label for="selectAll">Select All</label>
                    </div>
                    <div class="bulk-buttons">
                        <select class="form-select form-select-sm" name="bulk_action" style="width: 150px;">
                            <option value="">Bulk Actions</option>
                            <option value="approve">Approve Selected</option>
                            <option value="reject">Reject Selected</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirmBulkAction()">
                            Apply
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table reviews-table">
                        <thead>
                            <tr>
                                <th width="30"></th>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Customer</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                                <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_ids[]" value="<?php echo $review['review_id']; ?>" class="review-checkbox">
                                    </td>
                                    <td>
                                        <span class="review-id">#<?php echo $review['review_id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-image-sm">
                                                <?php if (!empty($review['image_url'])): ?>
                                                    <img src="../<?php echo $review['image_url']; ?>" 
                                                         alt="<?php echo htmlspecialchars($review['product_name']); ?>">
                                                <?php else: ?>
                                                    <div class="no-image-sm">
                                                        <i class="fas fa-box"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-details">
                                                <div class="product-name"><?php echo htmlspecialchars($review['product_name']); ?></div>
                                                <div class="product-sku small text-muted">SKU: <?php echo $review['sku']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-name"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></div>
                                            <div class="customer-email small text-muted"><?php echo $review['email']; ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rating-display">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="review-content">
                                            <?php if (!empty($review['title'])): ?>
                                                <div class="review-title"><?php echo htmlspecialchars($review['title']); ?></div>
                                            <?php endif; ?>
                                            <div class="review-comment">
                                                <?php echo nl2br(htmlspecialchars(substr($review['comment'], 0, 100))); ?>
                                                <?php if (strlen($review['comment']) > 100): ?>...<?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="review-date">
                                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                        </div>
                                        <div class="review-time small text-muted">
                                            <?php echo date('h:i A', strtotime($review['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($review['is_approved']): ?>
                                            <span class="status-badge status-approved">
                                                <i class="fas fa-check-circle me-1"></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action view" title="View Review" 
                                                    onclick="viewReview(<?php echo $review['review_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (!$review['is_approved']): ?>
                                            <a href="?approve=<?php echo $review['review_id']; ?>&page=<?php echo $page; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $product_filter ? '&product_id='.$product_filter : ''; ?><?php echo $rating_filter ? '&rating='.$rating_filter : ''; ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?>" 
                                               class="btn-action approve" title="Approve Review" onclick="return confirm('Approve this review?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?reject=<?php echo $review['review_id']; ?>&page=<?php echo $page; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $product_filter ? '&product_id='.$product_filter : ''; ?><?php echo $rating_filter ? '&rating='.$rating_filter : ''; ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?>" 
                                               class="btn-action reject" title="Reject Review" onclick="return confirm('Reject this review?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="?delete=<?php echo $review['review_id']; ?>&page=<?php echo $page; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $product_filter ? '&product_id='.$product_filter : ''; ?><?php echo $rating_filter ? '&rating='.$rating_filter : ''; ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?>" 
                                               class="btn-action delete" title="Delete Review" onclick="return confirmDelete()">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-star" style="font-size: 3rem; color: var(--light-gray);"></i>
                                        <p class="mt-3 mb-0">No reviews found</p>
                                        <p class="small text-muted">Try adjusting your filters</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </form>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="table-footer">
                    <nav aria-label="Reviews pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&product_id=<?php echo $product_filter; ?>&rating=<?php echo $rating_filter; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&product_id=<?php echo $product_filter; ?>&rating=<?php echo $rating_filter; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&product_id=<?php echo $product_filter; ?>&rating=<?php echo $rating_filter; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
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

    <!-- Recent Activity -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="activity-card">
                <div class="activity-header">
                    <h5><i class="fas fa-history me-2"></i>Recent Reviews</h5>
                </div>
                <div class="activity-body">
                    <?php if (mysqli_num_rows($recent_result) > 0): ?>
                        <?php while ($recent = mysqli_fetch_assoc($recent_result)): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $recent['is_approved'] ? 'approved' : 'pending'; ?>">
                                <i class="fas fa-<?php echo $recent['is_approved'] ? 'check' : 'clock'; ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    <strong><?php echo htmlspecialchars($recent['user_name']); ?></strong> reviewed 
                                    <strong><?php echo htmlspecialchars($recent['product_name']); ?></strong>
                                </div>
                                <div class="activity-meta">
                                    <span class="rating-display small">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $recent['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                    <span class="activity-time">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('M d, h:i A', strtotime($recent['created_at'])); ?>
                                    </span>
                                </div>
                                <?php if (!empty($recent['comment'])): ?>
                                <div class="activity-comment">
                                    "<?php echo htmlspecialchars(substr($recent['comment'], 0, 100)) . (strlen($recent['comment']) > 100 ? '...' : ''); ?>"
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No recent reviews</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Review Modal -->
<div class="modal fade" id="viewReviewModal" tabindex="-1" aria-labelledby="viewReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewReviewModalLabel">
                    <i class="fas fa-star me-2" style="color: var(--primary);"></i>
                    Review Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewReviewContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include admin footer -->
<?php include '../includes/admin_footer.php'; ?>

<!-- Page-specific scripts -->
<script>
// View review function
function viewReview(reviewId) {
    const modal = new bootstrap.Modal(document.getElementById('viewReviewModal'));
    const contentDiv = document.getElementById('viewReviewContent');
    
    // Show loading
    contentDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // Fetch review details via AJAX
    fetch('get_review.php?id=' + reviewId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const review = data.review;
                const ratingStars = Array(5).fill(0).map((_, i) => 
                    `<i class="fas fa-star ${i < review.rating ? 'text-warning' : 'text-muted'}"></i>`
                ).join('');
                
                const html = `
                    <div class="review-details-view">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6>Product Information</h6>
                                    <p><strong>Product:</strong> ${review.product_name}</p>
                                    <p><strong>SKU:</strong> ${review.sku}</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="detail-section">
                                    <h6>Customer Information</h6>
                                    <p><strong>Name:</strong> ${review.first_name} ${review.last_name}</p>
                                    <p><strong>Email:</strong> ${review.email}</p>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="detail-section">
                                    <h6>Review Details</h6>
                                    <p><strong>Rating:</strong> ${ratingStars}</p>
                                    ${review.title ? `<p><strong>Title:</strong> ${review.title}</p>` : ''}
                                    <p><strong>Comment:</strong></p>
                                    <div class="review-comment-full">${review.comment.replace(/\n/g, '<br>')}</div>
                                    <p class="mt-2"><strong>Date:</strong> ${new Date(review.created_at).toLocaleString()}</p>
                                    <p><strong>Status:</strong> 
                                        <span class="status-badge status-${review.is_approved ? 'approved' : 'pending'}">
                                            ${review.is_approved ? 'Approved' : 'Pending'}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                contentDiv.innerHTML = html;
            } else {
                contentDiv.innerHTML = '<div class="alert alert-danger">Failed to load review details</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contentDiv.innerHTML = '<div class="alert alert-danger">An error occurred</div>';
        });
}

// Toggle select all checkboxes
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.getElementsByClassName('review-checkbox');
    
    for (let checkbox of checkboxes) {
        checkbox.checked = selectAll.checked;
    }
}

// Confirm bulk action
function confirmBulkAction() {
    const action = document.querySelector('select[name="bulk_action"]').value;
    const checkboxes = document.querySelectorAll('.review-checkbox:checked');
    
    if (checkboxes.length === 0) {
        alert('Please select at least one review.');
        return false;
    }
    
    if (!action) {
        alert('Please select an action.');
        return false;
    }
    
    let message = '';
    if (action === 'approve') {
        message = `Approve ${checkboxes.length} selected review(s)?`;
    } else if (action === 'reject') {
        message = `Reject ${checkboxes.length} selected review(s)?`;
    } else if (action === 'delete') {
        message = `Delete ${checkboxes.length} selected review(s)? This action cannot be undone.`;
    }
    
    return confirm(message);
}

// Confirm delete
function confirmDelete() {
    return confirm('Delete this review? This action cannot be undone.');
}

// Export to CSV
function exportToCSV() {
    // Collect table data
    const rows = [];
    const table = document.querySelector('.reviews-table');
    const headers = ['ID', 'Product', 'Customer', 'Rating', 'Review', 'Date', 'Status'];
    rows.push(headers);
    
    const dataRows = Array.from(table.querySelectorAll('tbody tr'));
    dataRows.forEach(row => {
        if (row.cells.length > 1) {
            const rating = Array.from(row.cells[4].querySelectorAll('.fa-star.text-warning')).length;
            const rowData = [
                row.cells[1].textContent.trim(),
                row.cells[2].querySelector('.product-name')?.textContent.trim() || '',
                row.cells[3].querySelector('.customer-name')?.textContent.trim() || '',
                rating,
                row.cells[5].querySelector('.review-title')?.textContent.trim() || '',
                row.cells[6].querySelector('.review-date')?.textContent.trim() || '',
                row.cells[7].textContent.trim()
            ];
            rows.push(rowData);
        }
    });
    
    // Create CSV
    let csv = rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reviews_export_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Reviews exported successfully', 'success');
}
</script>

<style>
/* ===== REVIEWS PAGE SPECIFIC STYLES ===== */

/* Stat Cards */
.stat-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.2rem;
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
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.stat-details {
    flex: 1;
}

.stat-label {
    display: block;
    font-size: 0.8rem;
    color: var(--dark-gray);
    margin-bottom: 0.2rem;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.2;
}

/* Rating Distribution */
.rating-distribution {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.distribution-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.distribution-header h6 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
}

.distribution-body {
    padding: 1.5rem;
}

.rating-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.rating-row:last-child {
    margin-bottom: 0;
}

.rating-label {
    width: 40px;
    font-weight: 600;
}

.rating-bar-container {
    flex: 1;
    height: 20px;
    background: var(--light);
    border-radius: 10px;
    overflow: hidden;
}

.rating-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease;
}

.rating-count {
    width: 80px;
    text-align: right;
    font-size: 0.9rem;
    color: var(--dark-gray);
}

/* Bulk Actions Bar */
.bulk-actions-bar {
    padding: 1rem 1.5rem;
    background: var(--light);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.bulk-select {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.bulk-select input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.bulk-select label {
    margin: 0;
    cursor: pointer;
    font-weight: 500;
}

.bulk-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Reviews Table */
.reviews-table {
    margin: 0;
}

.reviews-table thead th {
    background: var(--light);
    color: var(--dark);
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
    padding: 1rem 0.75rem;
    white-space: nowrap;
}

.reviews-table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--border);
    font-size: 0.9rem;
}

.reviews-table tbody tr:hover {
    background: var(--light);
}

/* Review ID */
.review-id {
    font-weight: 600;
    color: var(--primary);
    font-family: var(--font-mono);
    font-size: 0.85rem;
}

/* Product Info */
.product-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.product-image-sm {
    width: 40px;
    height: 40px;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: var(--light);
}

.product-image-sm img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image-sm {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--light);
    color: var(--dark-gray);
    font-size: 1.2rem;
}

.product-details {
    line-height: 1.3;
}

.product-name {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.2rem;
}

/* Rating Display */
.rating-display {
    white-space: nowrap;
}

.rating-display .fa-star {
    margin-right: 2px;
    font-size: 0.9rem;
}

.text-warning {
    color: #ffc107 !important;
}

/* Review Content */
.review-content {
    max-width: 250px;
}

.review-title {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.2rem;
}

.review-comment {
    color: var(--dark-gray);
    font-size: 0.85rem;
    line-height: 1.4;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-approved {
    background: #d1e7dd;
    color: #0a3622;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
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
    text-decoration: none;
}

.btn-action:hover {
    background: var(--light);
    transform: translateY(-2px);
}

.btn-action.view:hover {
    color: var(--primary);
    border-color: var(--primary);
}

.btn-action.approve:hover {
    color: var(--success);
    border-color: var(--success);
}

.btn-action.reject:hover {
    color: var(--warning);
    border-color: var(--warning);
}

.btn-action.delete:hover {
    color: var(--danger);
    border-color: var(--danger);
}

/* Activity Card */
.activity-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.activity-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
    background: var(--light);
}

.activity-header h5 {
    margin: 0;
    color: var(--dark);
    font-weight: 600;
}

.activity-body {
    padding: 1rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    transition: var(--transition);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: var(--light);
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.activity-icon.approved {
    background: rgba(25,135,84,0.1);
    color: var(--success);
}

.activity-icon.pending {
    background: rgba(255,193,7,0.1);
    color: var(--warning);
}

.activity-details {
    flex: 1;
}

.activity-title {
    margin-bottom: 0.3rem;
    color: var(--dark);
}

.activity-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.3rem;
}

.activity-comment {
    color: var(--dark-gray);
    font-size: 0.85rem;
    font-style: italic;
}

/* Review Details View */
.review-details-view {
    padding: 0.5rem;
}

.detail-section {
    background: var(--light);
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid var(--border);
}

.detail-section h6 {
    color: var(--primary);
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border);
}

.detail-section p {
    margin-bottom: 0.5rem;
}

.review-comment-full {
    background: white;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid var(--border);
    margin-top: 0.5rem;
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.5rem;
    }
    
    .stat-value {
        font-size: 1.2rem;
    }
    
    .rating-row {
        flex-wrap: wrap;
    }
    
    .rating-label {
        width: 100%;
        margin-bottom: 0.25rem;
    }
    
    .rating-bar-container {
        width: 100%;
    }
    
    .rating-count {
        width: 100%;
        text-align: left;
        margin-top: 0.25rem;
    }
    
    .product-info {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .action-buttons {
        justify-content: flex-start;
    }
}

@media (max-width: 576px) {
    .bulk-actions-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .bulk-buttons {
        width: 100%;
    }
    
    .bulk-buttons select,
    .bulk-buttons button {
        width: 100%;
    }
    
    .review-content {
        max-width: 150px;
    }
}

/* Print Styles */
@media print {
    .btn,
    .btn-action,
    .status-badge,
    .filter-actions,
    .table-actions,
    .bulk-actions-bar,
    .back-to-top {
        display: none !important;
    }
    
    .table-card {
        border: 1px solid #000;
        box-shadow: none;
    }
    
    .status-badge {
        border: 1px solid #000;
        background: transparent !important;
        color: #000 !important;
    }
}
</style>