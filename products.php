<?php
// products.php - Products listing page (Featured products excluded)

// Include session handler
require_once 'session_handler.php';

// Include database
require_once 'config/database.php';

// Initialize variables
$search = $_GET['search'] ?? '';
$category_id = $_GET['category'] ?? 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get categories for sidebar
$categories = [];
$categories_query = "SELECT c.category_id, c.category_name, COUNT(p.product_id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1
                     WHERE c.is_active = 1 
                     GROUP BY c.category_id 
                     ORDER BY c.category_name";
$categories_result = mysqli_query($connection, $categories_query);
if ($categories_result) {
    while ($cat = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $cat;
    }
}

// Build search query
$where_conditions = ["p.is_active = 1", "p.is_featured = 0"];

if (!empty($search)) {
    $search_term = mysqli_real_escape_string($connection, $search);
    $where_conditions[] = "(p.product_name LIKE '%$search_term%' OR 
                           p.description LIKE '%$search_term%' OR 
                           p.short_description LIKE '%$search_term%')";
}

if ($category_id > 0) {
    $category_id = (int)$category_id;
    $where_conditions[] = "p.category_id = $category_id";
}

// Get category name
$category_name = '';
if ($category_id > 0) {
    $cat_query = "SELECT category_name FROM categories WHERE category_id = $category_id";
    $cat_result = mysqli_query($connection, $cat_query);
    if ($cat_result && mysqli_num_rows($cat_result) > 0) {
        $category_name = mysqli_fetch_assoc($cat_result)['category_name'];
    }
}

// Get products count
$count_query = "SELECT COUNT(*) as total 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                WHERE " . implode(' AND ', $where_conditions);
$count_result = mysqli_query($connection, $count_query);
$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$products_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE " . implode(' AND ', $where_conditions) . "
                   ORDER BY p.product_name 
                   LIMIT $limit OFFSET $offset";
$products_result = mysqli_query($connection, $products_query);

$products = [];
if ($products_result) {
    while ($product = mysqli_fetch_assoc($products_result)) {
        $products[] = $product;
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header text-center mb-5" data-aos="fade-up">
    <h1 class="page-title">
        <?php 
        if ($category_name) {
            echo htmlspecialchars($category_name);
        } elseif ($search) {
            echo "Search Results";
        } else {
            echo "Our Collection";
        }
        ?>
    </h1>
    <p class="page-description">
        <?php 
        if ($search) {
            echo "Showing results for \"" . htmlspecialchars($search) . "\"";
        } else {
            echo "Discover exquisite pieces crafted for the modern connoisseur";
        }
        ?>
    </p>
    <div class="page-divider">
        <span class="diamond"><i class="fas fa-gem"></i></span>
    </div>
</div>

<div class="row">
    <!-- Sidebar Filters -->
    <div class="col-lg-3 mb-4">
        <div class="filter-sidebar" data-aos="fade-right">
            <!-- Categories Widget -->
            <div class="filter-widget">
                <h3 class="widget-title">
                    <i class="fas fa-list-ul"></i>
                    Categories
                </h3>
                <ul class="category-list">
                    <li class="<?php echo $category_id == 0 ? 'active' : ''; ?>">
                        <a href="products.php" class="category-link">
                            <span>
                                <i class="fas fa-gem"></i>
                                All Products
                            </span>
                            <span class="count"><?php echo $total_products; ?></span>
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                    <li class="<?php echo $cat['category_id'] == $category_id ? 'active' : ''; ?>">
                        <a href="products.php?category=<?php echo $cat['category_id']; ?>" class="category-link">
                            <span>
                                <i class="fas fa-chevron-right"></i>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </span>
                            <span class="count"><?php echo $cat['product_count']; ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Search Filter Widget -->
            <div class="filter-widget">
                <h3 class="widget-title">
                    <i class="fas fa-search"></i>
                    Search
                </h3>
                <form method="GET" action="products.php" class="search-filter-form">
                    <?php if ($category_id > 0): ?>
                        <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                    <?php endif; ?>
                    <div class="search-input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search our collection..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Stats Widget -->
            <div class="filter-widget">
                <h3 class="widget-title">
                    <i class="fas fa-chart-bar"></i>
                    Quick Stats
                </h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $total_products; ?></span>
                        <span class="stat-label">Products</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo count($categories); ?></span>
                        <span class="stat-label">Categories</span>
                    </div>
                    <div class="stat-item">
                        <?php
                        $in_stock_query = "SELECT COUNT(*) as in_stock FROM products WHERE quantity_in_stock > 0 AND is_active = 1 AND is_featured = 0";
                        $in_stock_result = mysqli_query($connection, $in_stock_query);
                        $in_stock = mysqli_fetch_assoc($in_stock_result)['in_stock'];
                        ?>
                        <span class="stat-value"><?php echo $in_stock; ?></span>
                        <span class="stat-label">In Stock</span>
                    </div>
                </div>
            </div>

            <!-- Featured Link -->
            <div class="filter-widget">
                <a href="index.php#featured" class="featured-link">
                    <i class="fas fa-crown"></i>
                    <span>View Featured Collection</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <!-- Clear Filters (shown when filters are active) -->
            <?php if (!empty($search) || $category_id > 0): ?>
            <div class="filter-widget">
                <a href="products.php" class="clear-filters">
                    <i class="fas fa-times-circle"></i>
                    Clear All Filters
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="col-lg-9">
        <!-- Results Info -->
        <div class="results-info" data-aos="fade-up">
            <p>
                <i class="fas fa-gem"></i>
                <?php echo $total_products; ?> exquisite piece<?php echo $total_products != 1 ? 's' : ''; ?> found
            </p>
            <a href="index.php" class="back-home-link">
                <i class="fas fa-home"></i>
                Back to Home
            </a>
        </div>

        <?php if (empty($products)): ?>
            <!-- No Results -->
            <div class="no-results" data-aos="fade-up">
                <div class="no-results-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <h3>No Pieces Found</h3>
                <p>
                    <?php if ($search): ?>
                        No products match your search. 
                        <a href="products.php">View all products</a>
                    <?php elseif ($category_id > 0): ?>
                        This category is currently empty.
                        <a href="products.php">Browse other categories</a>
                    <?php else: ?>
                        Our collection is being curated.
                        <a href="index.php">Check featured items</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <!-- Products Grid -->
            <div class="row g-4">
                <?php foreach ($products as $index => $product): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                    <div class="product-card-luxury">
                        <div class="product-image">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo $product['image_url']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-gem"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Stock Status Badge -->
                            <?php if ($product['quantity_in_stock'] <= 0): ?>
                                <span class="stock-badge out-of-stock">
                                    <i class="fas fa-times-circle"></i>
                                    Out of Stock
                                </span>
                            <?php elseif ($product['quantity_in_stock'] < 10): ?>
                                <span class="stock-badge low-stock">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Low Stock
                                </span>
                            <?php endif; ?>

                            <!-- Quick View Overlay -->
                            <div class="product-overlay">
                                <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" 
                                   class="btn-quick-view">
                                    <i class="fas fa-eye"></i>
                                    Quick View
                                </a>
                            </div>
                        </div>

                        <div class="product-info">
                            <div class="product-category">
                                <i class="fas fa-tag"></i>
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </div>

                            <h3 class="product-name">
                                <a href="product_detail.php?id=<?php echo $product['product_id']; ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </a>
                            </h3>

                            <?php if ($product['rating'] > 0): ?>
                                <div class="product-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= floor($product['rating'])): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - 0.5 <= $product['rating']): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <span class="rating-count">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                                </div>
                            <?php endif; ?>

                            <div class="product-price">
                                <span class="currency">$</span>
                                <span class="amount"><?php echo number_format($product['unit_price'], 2); ?></span>
                            </div>

                            <div class="product-actions">
                                <?php if ($product['quantity_in_stock'] > 0): ?>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <form action="cart.php" method="GET" class="add-to-cart-form">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="id" value="<?php echo $product['product_id']; ?>">
                                            <button type="submit" class="btn-add-to-cart">
                                                <i class="fas fa-shopping-cart"></i>
                                                Add to Cart
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="login.php?redirect=<?php echo urlencode('product_detail.php?id=' . $product['product_id']); ?>" 
                                           class="btn-login-to-buy">
                                            <i class="fas fa-sign-in-alt"></i>
                                            Login to Buy
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn-out-of-stock" disabled>
                                        <i class="fas fa-times-circle"></i>
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper" data-aos="fade-up">
                <nav aria-label="Product navigation">
                    <ul class="pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="products.php?<?php echo buildQueryString(['page' => $page - 1]); ?>" 
                               aria-label="Previous">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" 
                                   href="products.php?<?php echo buildQueryString(['page' => $i]); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="products.php?<?php echo buildQueryString(['page' => $page + 1]); ?>" 
                               aria-label="Next">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function
function buildQueryString($new_params = []) {
    $params = $_GET;
    unset($params['page']);
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return http_build_query($params);
}

// Include footer
include 'includes/footer.php';
?>

<style>
/* ===== COMPLETE PRODUCTS PAGE STYLES ===== */
:root {
    --gold: #D4AF37;
    --gold-light: #F4E5C1;
    --gold-dark: #AA8C2F;
    --navy: #1A2A4F;
    --navy-light: #2A3F6F;
    --navy-dark: #0F1A2F;
    --pearl: #F8F6F0;
    --charcoal: #36454F;
    --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

/* Page Header */
.page-header {
    margin-bottom: 3rem;
    position: relative;
    padding: 0 1rem;
}

.page-title {
    font-family: 'Playfair Display', serif;
    font-size: 3rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 0.5rem;
    text-transform: capitalize;
    letter-spacing: -0.02em;
}

.page-description {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem;
    color: var(--charcoal);
    margin-bottom: 1rem;
    font-style: italic;
}

.page-divider {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
}

.diamond {
    font-size: 2rem;
    color: var(--gold);
    animation: sparkle 2s infinite;
}

@keyframes sparkle {
    0%, 100% { 
        opacity: 1; 
        transform: scale(1); 
        text-shadow: 0 0 5px var(--gold);
    }
    50% { 
        opacity: 0.9; 
        transform: scale(1.1); 
        text-shadow: 0 0 20px var(--gold), 0 0 30px var(--gold-light);
    }
}

/* ===== FIXED SIDEBAR STYLES ===== */

/* Filter Sidebar Container */
.filter-sidebar {
    background: white;
    border-radius: 24px;
    padding: 1.8rem;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    position: sticky;
    top: 100px;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--gold) var(--pearl);
    border: 1px solid rgba(212,175,55,0.2);
}

/* Custom Scrollbar */
.filter-sidebar::-webkit-scrollbar {
    width: 5px;
}

.filter-sidebar::-webkit-scrollbar-track {
    background: var(--pearl);
    border-radius: 10px;
}

.filter-sidebar::-webkit-scrollbar-thumb {
    background: var(--gold);
    border-radius: 10px;
}

.filter-sidebar::-webkit-scrollbar-thumb:hover {
    background: var(--gold-dark);
}

/* Filter Widget */
.filter-widget {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid rgba(212,175,55,0.2);
}

.filter-widget:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

/* Widget Title */
.widget-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.widget-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 2px;
    background: linear-gradient(90deg, var(--gold) 0%, transparent 100%);
}

.widget-title i {
    color: var(--gold);
    font-size: 1.1rem;
    animation: sparkle 3s infinite;
}

/* Category List */
.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list li {
    margin-bottom: 0.5rem;
}

.category-list li a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.8rem 1rem;
    background: var(--pearl);
    border-radius: 12px;
    color: var(--charcoal);
    text-decoration: none;
    transition: var(--transition);
    border: 1px solid transparent;
}

.category-list li a:hover {
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    transform: translateX(8px);
    border-color: var(--gold);
    box-shadow: 0 5px 20px rgba(212,175,55,0.25);
}

.category-list li a i {
    color: var(--gold);
    font-size: 0.8rem;
    margin-right: 0.5rem;
    transition: var(--transition);
}

.category-list li a:hover i {
    transform: translateX(5px);
}

/* Active Category */
.category-list li.active a {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    color: white;
    border-color: var(--gold);
    box-shadow: 0 5px 20px rgba(212,175,55,0.3);
}

.category-list li.active a i {
    color: var(--gold);
}

.category-list li.active .count {
    background: rgba(255,255,255,0.2);
    color: white;
}

/* Category Count */
.category-list .count {
    background: rgba(0,0,0,0.05);
    padding: 0.2rem 0.7rem;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    transition: var(--transition);
}

.category-list li a:hover .count {
    background: rgba(255,255,255,0.3);
}

/* Search Filter Form */
.search-filter-form {
    width: 100%;
}

.search-input-group {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

.search-input-group input {
    width: 100%;
    padding: 0.9rem 1.2rem;
    padding-right: 3rem;
    border: 2px solid rgba(212,175,55,0.2);
    border-radius: 50px;
    transition: var(--transition);
    font-family: 'Cormorant Garamond', serif;
    font-size: 1rem;
    background: white;
}

.search-input-group input:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 5px rgba(212,175,55,0.15);
}

.search-input-group input::placeholder {
    color: var(--charcoal);
    opacity: 0.5;
    font-style: italic;
}

.search-btn {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    width: 45px;
    height: 45px;
    background: transparent;
    border: none;
    color: var(--gold);
    cursor: pointer;
    transition: var(--transition);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-btn:hover {
    background: var(--gold);
    color: var(--navy);
    transform: translateY(-50%) scale(1.1);
}

.search-btn i {
    font-size: 1.1rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.8rem;
}

.stat-item {
    text-align: center;
    padding: 1.2rem 0.5rem;
    background: var(--pearl);
    border-radius: 16px;
    transition: var(--transition);
    border: 1px solid transparent;
    box-shadow: 0 3px 10px rgba(0,0,0,0.03);
}

.stat-item:hover {
    transform: translateY(-8px);
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-color: var(--gold-dark);
    box-shadow: 0 15px 25px rgba(212,175,55,0.25);
}

.stat-value {
    display: block;
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 0.2rem;
    line-height: 1.2;
    font-family: 'Playfair Display', serif;
}

.stat-item:hover .stat-value {
    color: var(--navy-dark);
}

.stat-label {
    font-size: 0.7rem;
    color: var(--charcoal);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.stat-item:hover .stat-label {
    color: var(--navy);
}

/* Featured Link Widget */
.featured-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-radius: 50px;
    color: var(--navy);
    text-decoration: none;
    transition: var(--transition);
    border: 1px solid var(--gold-dark);
    box-shadow: 0 5px 15px rgba(212,175,55,0.2);
}

.featured-link:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 20px 30px rgba(212,175,55,0.35);
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
}

.featured-link i:first-child {
    font-size: 1.3rem;
    animation: sparkle 2s infinite;
}

.featured-link span {
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: 0.5px;
}

.featured-link i:last-child {
    transition: var(--transition);
}

.featured-link:hover i:last-child {
    transform: translateX(8px);
}

/* Clear Filters */
.clear-filters {
    display: block;
    text-align: center;
    padding: 0.8rem;
    color: var(--charcoal);
    text-decoration: none;
    font-size: 0.95rem;
    transition: var(--transition);
    border: 1px dashed rgba(212,175,55,0.3);
    border-radius: 50px;
}

.clear-filters:hover {
    color: var(--gold);
    border-color: var(--gold);
    background: rgba(212,175,55,0.05);
    transform: translateY(-2px);
}

.clear-filters i {
    margin-right: 0.3rem;
    font-size: 0.9rem;
}

/* Results Info */
.results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.2rem 1.8rem;
    background: white;
    border-radius: 50px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(212,175,55,0.2);
}

.results-info p {
    margin: 0;
    color: var(--charcoal);
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
}

.results-info p i {
    color: var(--gold);
    margin-right: 0.5rem;
    animation: sparkle 3s infinite;
}

.back-home-link {
    color: var(--navy);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.6rem 1.5rem;
    border-radius: 50px;
    transition: var(--transition);
    background: var(--pearl);
    border: 1px solid transparent;
}

.back-home-link:hover {
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    transform: translateX(-5px);
    border-color: var(--gold);
}

.back-home-link i {
    color: var(--gold);
    transition: var(--transition);
}

.back-home-link:hover i {
    transform: translateX(-3px);
}

/* Product Card */
.product-card-luxury {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    transition: var(--transition);
    position: relative;
    height: 100%;
    border: 1px solid rgba(212,175,55,0.1);
}

.product-card-luxury:hover {
    transform: translateY(-12px);
    box-shadow: 0 25px 45px rgba(212, 175, 55, 0.25);
    border-color: rgba(212,175,55,0.3);
}

.product-image {
    position: relative;
    padding-top: 100%;
    overflow: hidden;
    background: linear-gradient(135deg, var(--pearl), white);
}

.product-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.product-card-luxury:hover .product-image img {
    transform: scale(1.15);
}

.no-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--pearl);
}

.no-image i {
    font-size: 3.5rem;
    color: var(--gold);
    opacity: 0.4;
    animation: sparkle 3s infinite;
}

/* Stock Badge */
.stock-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    z-index: 3;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.stock-badge.out-of-stock {
    background: #C41E3A;
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}

.stock-badge.low-stock {
    background: #FF8C00;
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}

/* Product Overlay */
.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(26, 42, 79, 0.8), rgba(42, 63, 111, 0.8));
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: var(--transition);
    z-index: 4;
    backdrop-filter: blur(3px);
}

.product-card-luxury:hover .product-overlay {
    opacity: 1;
}

.btn-quick-view {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    text-decoration: none;
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-weight: 600;
    transform: translateY(30px);
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    border: 2px solid transparent;
    font-size: 1.1rem;
}

.product-card-luxury:hover .btn-quick-view {
    transform: translateY(0);
}

.btn-quick-view:hover {
    background: white;
    color: var(--gold);
    border-color: var(--gold);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 25px rgba(0,0,0,0.3);
}

/* Product Info */
.product-info {
    padding: 1.8rem 1.5rem;
}

.product-category {
    color: var(--gold);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 500;
}

.product-category i {
    font-size: 0.9rem;
    animation: sparkle 3s infinite;
}

.product-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.8rem;
    line-height: 1.4;
}

.product-name a {
    color: var(--navy);
    text-decoration: none;
    transition: var(--transition);
}

.product-name a:hover {
    color: var(--gold);
}

/* Product Rating */
.product-rating {
    display: flex;
    align-items: center;
    gap: 0.2rem;
    margin-bottom: 0.8rem;
}

.product-rating i {
    color: var(--gold);
    font-size: 0.9rem;
}

.product-rating .fa-star,
.product-rating .fa-star-half-alt {
    color: var(--gold);
    filter: drop-shadow(0 0 5px rgba(212,175,55,0.3));
}

.product-rating .far.fa-star {
    color: #ddd;
}

.rating-count {
    color: var(--charcoal);
    font-size: 0.8rem;
    margin-left: 0.5rem;
}

/* Product Price */
.product-price {
    display: flex;
    align-items: baseline;
    gap: 0.2rem;
    margin-bottom: 1.2rem;
}

.product-price .currency {
    color: var(--gold);
    font-size: 1.1rem;
    font-weight: 600;
}

.product-price .amount {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--navy);
    font-family: 'Playfair Display', serif;
    line-height: 1;
}

/* Action Buttons */
.product-actions {
    margin-top: 1.2rem;
}

.btn-add-to-cart,
.btn-login-to-buy,
.btn-out-of-stock {
    width: 100%;
    padding: 0.9rem 1.5rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-add-to-cart {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
}

.btn-add-to-cart::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-add-to-cart:hover::before {
    width: 400px;
    height: 400px;
}

.btn-add-to-cart:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 25px rgba(212,175,55,0.4);
}

.btn-login-to-buy {
    background: transparent;
    color: var(--navy);
    border: 2px solid var(--gold);
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.btn-login-to-buy::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    transition: left 0.4s;
    z-index: -1;
}

.btn-login-to-buy:hover::before {
    left: 0;
}

.btn-login-to-buy:hover {
    color: var(--navy);
    transform: translateY(-4px);
    border-color: transparent;
    box-shadow: 0 15px 25px rgba(212,175,55,0.3);
}

.btn-out-of-stock {
    background: #f0f0f0;
    color: #999;
    cursor: not-allowed;
    border: 1px solid #ddd;
}

/* Pagination */
.pagination-wrapper {
    margin-top: 4rem;
    text-align: center;
}

.pagination {
    display: inline-flex;
    gap: 0.5rem;
    padding: 0.5rem;
    background: white;
    border-radius: 60px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    border: 1px solid rgba(212,175,55,0.2);
}

.page-link {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 2px solid transparent;
    border-radius: 50%;
    color: var(--navy);
    text-decoration: none;
    transition: var(--transition);
    font-weight: 600;
    font-size: 1rem;
}

.page-link:hover {
    background: var(--gold-light);
    border-color: var(--gold);
    color: var(--navy);
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(212,175,55,0.25);
}

.page-item.active .page-link {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    box-shadow: 0 5px 15px rgba(212,175,55,0.3);
}

.page-item.disabled .page-link {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 5rem 2rem;
    background: white;
    border-radius: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    border: 1px solid rgba(212,175,55,0.2);
}

.no-results-icon {
    margin-bottom: 2rem;
}

.no-results-icon i {
    font-size: 6rem;
    color: var(--gold);
    animation: sparkle 2s infinite;
    opacity: 0.7;
}

.no-results h3 {
    font-family: 'Playfair Display', serif;
    font-size: 2.2rem;
    color: var(--navy);
    margin-bottom: 1rem;
}

.no-results p {
    color: var(--charcoal);
    font-size: 1.2rem;
    font-family: 'Cormorant Garamond', serif;
}

.no-results a {
    color: var(--gold);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    border-bottom: 1px solid transparent;
}

.no-results a:hover {
    color: var(--gold-dark);
    border-bottom-color: var(--gold);
}

/* Responsive */
@media (max-width: 1200px) {
    .page-title {
        font-size: 2.5rem;
    }
    
    .product-price .amount {
        font-size: 1.6rem;
    }
}

@media (max-width: 991px) {
    .filter-sidebar {
        position: static;
        max-height: none;
        margin-bottom: 2rem;
        overflow-y: visible;
    }
    
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .page-title {
        font-size: 2.2rem;
    }
}

@media (max-width: 768px) {
    .filter-sidebar {
        padding: 1.5rem;
    }
    
    .widget-title {
        font-size: 1.1rem;
    }
    
    .category-list li a {
        padding: 0.7rem 0.8rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .page-description {
        font-size: 1.1rem;
    }
    
    .results-info {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
        padding: 1.5rem;
        border-radius: 30px;
    }
    
    .product-card-luxury {
        max-width: 350px;
        margin: 0 auto;
    }
}

@media (max-width: 576px) {
    .filter-sidebar {
        padding: 1.2rem;
    }
    
    .widget-title {
        font-size: 1rem;
    }
    
    .category-list li a {
        font-size: 0.9rem;
        padding: 0.6rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 0.6rem;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-align: left;
        padding: 0.8rem 1.2rem;
    }
    
    .stat-value {
        margin-bottom: 0;
        font-size: 1.5rem;
    }
    
    .stat-label {
        font-size: 0.8rem;
    }
    
    .search-input-group input {
        font-size: 0.9rem;
        padding: 0.8rem 1rem;
    }
    
    .featured-link {
        padding: 0.8rem 1.2rem;
    }
    
    .featured-link span {
        font-size: 0.9rem;
    }
    
    .page-title {
        font-size: 1.8rem;
    }
    
    .pagination {
        gap: 0.2rem;
    }
    
    .page-link {
        width: 40px;
        height: 40px;
        font-size: 0.9rem;
    }
    
    .no-results {
        padding: 3rem 1rem;
    }
    
    .no-results h3 {
        font-size: 1.8rem;
    }
    
    .no-results p {
        font-size: 1rem;
    }
}

/* Loading States */
.filter-widget.loading {
    position: relative;
    pointer-events: none;
    opacity: 0.6;
}

.filter-widget.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 30px;
    height: 30px;
    margin-left: -15px;
    margin-top: -15px;
    border: 3px solid var(--gold);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Print Styles */
@media print {
    .filter-sidebar,
    .back-home-link,
    .product-actions,
    .pagination {
        display: none !important;
    }
    
    .product-card-luxury {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>