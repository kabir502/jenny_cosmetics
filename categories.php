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

<!-- Page Header -->
<div class="page-header text-center mb-5" data-aos="fade-up">
    <h1 class="page-title">Product Categories</h1>
    <p class="page-description">Explore our exquisite collections by category</p>
    <div class="page-divider">
        <span class="diamond"><i class="fas fa-gem"></i></span>
    </div>
</div>

<!-- Action Bar -->
<div class="row mb-5" data-aos="fade-up">
    <div class="col-12">
        <div class="action-bar">
            <div class="action-content">
                <span class="action-icon">
                    <i class="fas fa-gem"></i>
                </span>
                <span class="action-text">
                    <?php echo count($categories); ?> exquisite collection<?php echo count($categories) != 1 ? 's' : ''; ?> to explore
                </span>
            </div>
            <a href="products.php" class="btn-luxury">
                <i class="fas fa-th-large me-2"></i>View All Products
            </a>
        </div>
    </div>
</div>

<?php if (empty($categories)): ?>
<!-- No Categories -->
<div class="row">
    <div class="col-12">
        <div class="no-categories" data-aos="fade-up">
            <div class="no-categories-icon">
                <i class="fas fa-gem"></i>
            </div>
            <h3>No Collections Available</h3>
            <p>Our categories are being curated with care. Please check back soon.</p>
            <a href="index.php" class="btn-luxury-outline">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Categories Grid -->
<div class="row g-4">
    <?php foreach ($categories as $index => $category): ?>
    <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
        <div class="category-card-luxury">
            <div class="category-image-wrapper">
                <?php if ($category['image_url']): ?>
                <div class="category-image">
                    <img src="<?php echo $category['image_url']; ?>" 
                         alt="<?php echo htmlspecialchars($category['category_name']); ?>">
                    <div class="category-overlay">
                        <a href="products.php?category=<?php echo $category['category_id']; ?>" 
                           class="btn-explore">
                            <i class="fas fa-eye"></i>
                            Explore
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="category-image placeholder">
                    <div class="placeholder-content">
                        <i class="fas fa-gem"></i>
                        <span><?php echo htmlspecialchars($category['category_name']); ?></span>
                    </div>
                    <div class="category-overlay">
                        <a href="products.php?category=<?php echo $category['category_id']; ?>" 
                           class="btn-explore">
                            <i class="fas fa-eye"></i>
                            Explore
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Product Count Badge -->
                <span class="product-count-badge">
                    <i class="fas fa-box"></i>
                    <?php echo $category['product_count']; ?>
                </span>
            </div>
            
            <div class="category-info">
                <h3 class="category-title">
                    <a href="products.php?category=<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </a>
                </h3>
                
                <?php if (!empty($category['description'])): ?>
                <p class="category-description">
                    <?php echo substr(htmlspecialchars($category['description']), 0, 80); ?>
                    <?php if (strlen($category['description']) > 80): ?>...<?php endif; ?>
                </p>
                <?php endif; ?>
                
                <div class="category-footer">
                    <a href="products.php?category=<?php echo $category['category_id']; ?>" 
                       class="category-link">
                        <span>Browse Collection</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Statistics Section -->
<div class="row mt-5">
    <div class="col-12">
        <div class="stats-card" data-aos="fade-up">
            <h3 class="stats-title">
                <i class="fas fa-gem"></i>
                Collection Statistics
            </h3>
            <div class="stats-grid-large">
                <!-- Total Categories -->
                <div class="stat-item-large">
                    <div class="stat-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo count($categories); ?></span>
                        <span class="stat-label">Total Categories</span>
                    </div>
                </div>
                
                <!-- Total Products -->
                <div class="stat-item-large">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $total_products_query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
                        $total_products_result = mysqli_query($connection, $total_products_query);
                        $total_products = mysqli_fetch_assoc($total_products_result)['total'];
                        ?>
                        <span class="stat-value"><?php echo $total_products; ?></span>
                        <span class="stat-label">Total Products</span>
                    </div>
                </div>
                
                <!-- Most Popular Category -->
                <div class="stat-item-large">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $top_category_query = "SELECT c.category_name, COUNT(p.product_id) as product_count 
                                               FROM categories c 
                                               LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1
                                               GROUP BY c.category_id 
                                               ORDER BY product_count DESC 
                                               LIMIT 1";
                        $top_category_result = mysqli_query($connection, $top_category_query);
                        $top_category = mysqli_fetch_assoc($top_category_result);
                        ?>
                        <span class="stat-value"><?php echo $top_category['category_name'] ?? 'N/A'; ?></span>
                        <span class="stat-label">Most Popular</span>
                        <span class="stat-sub"><?php echo $top_category['product_count'] ?? 0; ?> products</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== CATEGORIES PAGE STYLES ===== */
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
    margin-bottom: 2rem;
    position: relative;
}

.page-title {
    font-family: 'Playfair Display', serif;
    font-size: 3rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 0.5rem;
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

/* Action Bar */
.action-bar {
    background: linear-gradient(135deg, var(--navy), var(--navy-light));
    padding: 1.5rem 2rem;
    border-radius: 50px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 10px 30px rgba(26, 42, 79, 0.2);
    border: 1px solid var(--gold);
    position: relative;
    overflow: hidden;
}

.action-bar::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(212,175,55,0.1) 0%, transparent 70%);
    animation: rotate 30s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.action-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
    z-index: 2;
}

.action-icon {
    width: 50px;
    height: 50px;
    background: var(--gold);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--navy);
    font-size: 1.5rem;
    animation: sparkle 2s infinite;
}

.action-text {
    color: white;
    font-size: 1.2rem;
    font-family: 'Cormorant Garamond', serif;
}

.btn-luxury {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    padding: 0.8rem 2rem;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    transition: var(--transition);
    position: relative;
    z-index: 2;
    overflow: hidden;
    border: 1px solid var(--gold-light);
}

.btn-luxury::before {
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
    z-index: -1;
}

.btn-luxury:hover::before {
    width: 300px;
    height: 300px;
}

.btn-luxury:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 25px rgba(212,175,55,0.4);
    color: var(--navy);
}

/* Category Card */
.category-card-luxury {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    transition: var(--transition);
    height: 100%;
    position: relative;
    border: 1px solid rgba(212,175,55,0.1);
}

.category-card-luxury:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 45px rgba(212,175,55,0.25);
    border-color: rgba(212,175,55,0.3);
}

/* Category Image Wrapper */
.category-image-wrapper {
    position: relative;
    overflow: hidden;
    aspect-ratio: 1;
}

.category-image {
    width: 100%;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.category-card-luxury:hover .category-image img {
    transform: scale(1.15);
}

/* Placeholder Image */
.category-image.placeholder {
    background: linear-gradient(135deg, var(--pearl), #ffffff);
    display: flex;
    align-items: center;
    justify-content: center;
}

.placeholder-content {
    text-align: center;
    transform: translateY(0);
    transition: var(--transition);
}

.placeholder-content i {
    font-size: 4rem;
    color: var(--gold);
    opacity: 0.5;
    margin-bottom: 1rem;
    animation: sparkle 3s infinite;
}

.placeholder-content span {
    display: block;
    color: var(--navy);
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 600;
    opacity: 0.7;
}

.category-card-luxury:hover .placeholder-content {
    transform: scale(0.9);
}

/* Product Count Badge */
.product-count-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    z-index: 3;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 0.4rem;
    border: 1px solid rgba(255,255,255,0.3);
    backdrop-filter: blur(5px);
}

.product-count-badge i {
    font-size: 0.8rem;
}

/* Category Overlay */
.category-overlay {
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
    z-index: 2;
    backdrop-filter: blur(3px);
}

.category-card-luxury:hover .category-overlay {
    opacity: 1;
}

.btn-explore {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: var(--navy);
    text-decoration: none;
    padding: 0.8rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    transform: translateY(20px);
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid transparent;
}

.category-card-luxury:hover .btn-explore {
    transform: translateY(0);
}

.btn-explore:hover {
    background: white;
    color: var(--gold);
    border-color: var(--gold);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 15px 25px rgba(0,0,0,0.3);
}

.btn-explore i {
    font-size: 0.9rem;
}

/* Category Info */
.category-info {
    padding: 1.8rem 1.5rem;
    background: white;
}

.category-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.8rem;
    line-height: 1.4;
}

.category-title a {
    color: var(--navy);
    text-decoration: none;
    transition: var(--transition);
}

.category-title a:hover {
    color: var(--gold);
}

.category-description {
    color: var(--charcoal);
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 1.2rem;
    font-family: 'Cormorant Garamond', serif;
}

.category-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.category-link {
    color: var(--gold);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
    font-size: 0.95rem;
}

.category-link:hover {
    gap: 1rem;
    color: var(--navy);
}

.category-link i {
    font-size: 0.9rem;
}

/* No Categories */
.no-categories {
    text-align: center;
    padding: 5rem 2rem;
    background: white;
    border-radius: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    border: 1px solid rgba(212,175,55,0.2);
}

.no-categories-icon {
    margin-bottom: 2rem;
}

.no-categories-icon i {
    font-size: 6rem;
    color: var(--gold);
    animation: sparkle 2s infinite;
    opacity: 0.7;
}

.no-categories h3 {
    font-family: 'Playfair Display', serif;
    font-size: 2.2rem;
    color: var(--navy);
    margin-bottom: 1rem;
}

.no-categories p {
    color: var(--charcoal);
    font-size: 1.2rem;
    margin-bottom: 2rem;
    font-family: 'Cormorant Garamond', serif;
}

.btn-luxury-outline {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: transparent;
    color: var(--navy);
    border: 2px solid var(--gold);
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
}

.btn-luxury-outline:hover {
    background: var(--gold);
    color: var(--navy);
    transform: translateY(-3px);
    box-shadow: 0 15px 25px rgba(212,175,55,0.3);
}

/* Statistics Card */
.stats-card {
    background: linear-gradient(135deg, white, var(--pearl));
    border-radius: 30px;
    padding: 3rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    border: 1px solid rgba(212,175,55,0.2);
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(212,175,55,0.05) 0%, transparent 70%);
    animation: rotate 30s linear infinite;
}

.stats-title {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 2.5rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    position: relative;
    z-index: 2;
}

.stats-title i {
    color: var(--gold);
    animation: sparkle 2s infinite;
}

/* Stats Grid Large */
.stats-grid-large {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    position: relative;
    z-index: 2;
}

.stat-item-large {
    background: white;
    padding: 2.5rem 2rem;
    border-radius: 24px;
    text-align: center;
    transition: var(--transition);
    border: 1px solid rgba(212,175,55,0.1);
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

.stat-item-large:hover {
    transform: translateY(-10px);
    border-color: var(--gold);
    box-shadow: 0 20px 35px rgba(212,175,55,0.2);
}

.stat-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--gold-light), var(--gold));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: var(--navy);
    font-size: 2rem;
    transition: var(--transition);
}

.stat-item-large:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 10px 20px rgba(212,175,55,0.3);
}

.stat-content {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 2.8rem;
    font-weight: 800;
    color: var(--navy);
    line-height: 1.2;
    margin-bottom: 0.3rem;
    font-family: 'Playfair Display', serif;
}

.stat-label {
    display: block;
    color: var(--charcoal);
    font-size: 1rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.3rem;
}

.stat-sub {
    display: block;
    color: var(--gold);
    font-size: 0.9rem;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 1200px) {
    .page-title {
        font-size: 2.5rem;
    }
    
    .stat-value {
        font-size: 2.4rem;
    }
}

@media (max-width: 991px) {
    .stats-grid-large {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    
    .action-bar {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
        border-radius: 30px;
    }
    
    .action-content {
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 2.2rem;
    }
    
    .page-description {
        font-size: 1.1rem;
    }
    
    .stats-card {
        padding: 2rem;
    }
    
    .stats-title {
        font-size: 1.8rem;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .stat-value {
        font-size: 2rem;
    }
    
    .category-title {
        font-size: 1.2rem;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 2rem;
    }
    
    .stats-grid-large {
        grid-template-columns: 1fr;
    }
    
    .action-bar {
        padding: 1.5rem;
    }
    
    .btn-luxury {
        width: 100%;
        justify-content: center;
    }
    
    .stat-item-large {
        padding: 1.5rem;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
        margin-bottom: 1rem;
    }
    
    .stat-value {
        font-size: 1.8rem;
    }
    
    .category-card-luxury {
        max-width: 350px;
        margin: 0 auto;
    }
    
    .no-categories {
        padding: 3rem 1rem;
    }
    
    .no-categories h3 {
        font-size: 1.8rem;
    }
    
    .no-categories p {
        font-size: 1rem;
    }
}

/* Loading States */
.category-card-luxury.loading {
    position: relative;
    overflow: hidden;
}

.category-card-luxury.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    animation: loading 1.5s infinite;
}

@keyframes loading {
    to { left: 100%; }
}

/* Print Styles */
@media print {
    .action-bar,
    .btn-explore,
    .category-link {
        display: none !important;
    }
    
    .category-card-luxury {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .category-image {
        border: 1px solid #000;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>