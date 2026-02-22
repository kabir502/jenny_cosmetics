<?php
// index.php - Main landing page

// Include required files
require_once 'session_handler.php';
require_once 'config/database.php';

// Get featured products
$featured_products = [];
if ($connection) {
    $query = "SELECT * FROM products WHERE is_featured = 1 AND is_active = 1 LIMIT 6";
    $result = mysqli_query($connection, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $featured_products[] = $row;
        }
    }
}

// Get categories
$categories = [];
if ($connection) {
    $query = "SELECT * FROM categories WHERE is_active = 1 LIMIT 4";
    $result = mysqli_query($connection, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Hero Section with Animation -->
<div class="row mb-5" data-aos="fade-up" data-aos-duration="1000">
    <div class="col-12">
        <div class="hero-section">
            <div class="hero-content">
                <span class="hero-subtitle">Welcome to</span>
                <h1 class="hero-title"><?php echo SITE_NAME; ?></h1>
                <p class="hero-description">Discover our exquisite collection of fine cosmetics and imitation jewelry, crafted for the modern connoisseur.</p>
                <div class="hero-buttons">
                    <a href="products.php" class="btn-luxury">Explore Collection</a>
                    <a href="#featured" class="btn-outline-luxury">View Featured</a>
                </div>
            </div>
            <div class="hero-decoration">
                <div class="floating-gem">
                    <i class="fas fa-gem"></i>
                </div>
                <div class="floating-gem-2">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Featured Products -->
<?php if (!empty($featured_products)): ?>
<div class="row mb-5" id="featured">
    <div class="col-12">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <span class="section-subtitle">Our Collection</span>
            <h2 class="section-title">Featured Products</h2>
            <div class="section-divider">
                <span class="diamond"><i class="fas fa-gem"></i></span>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featured_products as $index => $product): ?>
            <div class="col-md-4 col-lg-2" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="product-card-luxury">
                    <?php if (!empty($product['image_url'])): ?>
                    <div class="product-image">
                        <img src="<?php echo $product['image_url']; ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <div class="product-overlay">
                            <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <div class="product-price">
                            <span class="currency">$</span>
                            <span class="amount"><?php echo number_format($product['unit_price'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="products.php" class="btn-luxury-outline">View All Products</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Categories -->
<?php if (!empty($categories)): ?>
<div class="row mb-5">
    <div class="col-12">
        <div class="section-header text-center mb-5" data-aos="fade-up">
            <span class="section-subtitle">Shop by</span>
            <h2 class="section-title">Categories</h2>
            <div class="section-divider">
                <span class="diamond"><i class="fas fa-gem"></i></span>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($categories as $index => $category): ?>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="category-card">
                    <div class="category-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3 class="category-title"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                    <p class="category-description">
                        <?php echo substr(htmlspecialchars($category['description'] ?? 'Discover our exclusive collection'), 0, 80); ?>...
                    </p>
                    <a href="products.php?category=<?php echo $category['category_id']; ?>" class="category-link">
                        Browse Collection <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Features Section -->
<div class="row mb-5">
    <div class="col-12">
        <div class="features-grid">
            <div class="feature-item" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h4>Free Shipping</h4>
                <p>On orders over $50</p>
            </div>
            
            <div class="feature-item" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h4>30-Day Returns</h4>
                <p>Hassle-free returns</p>
            </div>
            
            <div class="feature-item" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h4>Secure Payment</h4>
                <p>100% secure checkout</p>
            </div>
            
            <div class="feature-item" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h4>24/7 Support</h4>
                <p>Dedicated assistance</p>
            </div>
        </div>
    </div>
</div>

<!-- Newsletter Section -->
<div class="row mb-5">
    <div class="col-12">
        <div class="newsletter-section" data-aos="fade-up">
            <div class="newsletter-content">
                <h3>Join Our Inner Circle</h3>
                <p>Subscribe to receive exclusive offers, jewelry care tips, and early access to new collections.</p>
                <form class="newsletter-form" action="subscribe.php" method="POST">
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="Your email address" required>
                        <button type="submit" class="btn-newsletter">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== HERO SECTION ===== */
.hero-section {
    position: relative;
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
    padding: 4rem;
    border-radius: 30px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 600px;
}

.hero-subtitle {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    color: var(--gold);
    text-transform: uppercase;
    letter-spacing: 3px;
    display: block;
    margin-bottom: 1rem;
}

.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 4rem;
    font-weight: 800;
    color: white;
    margin-bottom: 1.5rem;
    line-height: 1.2;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.hero-description {
    font-size: 1.2rem;
    color: rgba(255,255,255,0.9);
    margin-bottom: 2rem;
    line-height: 1.6;
    font-family: 'Cormorant Garamond', serif;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
}

.btn-luxury {
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
    color: var(--navy);
    border: none;
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
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
}

.btn-luxury:hover::before {
    width: 300px;
    height: 300px;
}

.btn-luxury:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(212, 175, 55, 0.4);
    color: var(--navy);
}

.btn-outline-luxury {
    background: transparent;
    color: var(--gold);
    border: 2px solid var(--gold);
    padding: 1rem 2.5rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
}

.btn-outline-luxury:hover {
    background: var(--gold);
    color: var(--navy);
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(212, 175, 55, 0.3);
}

.hero-decoration {
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.floating-gem {
    position: absolute;
    top: 20%;
    right: 15%;
    font-size: 5rem;
    color: var(--gold);
    opacity: 0.2;
    animation: float 6s ease-in-out infinite;
}

.floating-gem-2 {
    position: absolute;
    bottom: 20%;
    right: 25%;
    font-size: 3rem;
    color: var(--gold-light);
    opacity: 0.15;
    animation: floatReverse 8s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(10deg); }
}

@keyframes floatReverse {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(20px) rotate(-10deg); }
}

/* ===== SECTION HEADER ===== */
.section-header {
    position: relative;
}

.section-subtitle {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
    color: var(--gold);
    text-transform: uppercase;
    letter-spacing: 3px;
    display: block;
    margin-bottom: 0.5rem;
}

.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 1rem;
}

.section-divider {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
}

.diamond {
    font-size: 1.5rem;
    color: var(--gold);
    animation: sparkle 2s infinite;
}

/* ===== PRODUCT CARDS ===== */
.product-card-luxury {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: var(--transition);
    position: relative;
}

.product-card-luxury:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 30px rgba(212, 175, 55, 0.2);
}

.product-image {
    position: relative;
    padding-top: 100%;
    overflow: hidden;
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
    transform: scale(1.1);
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(26, 42, 79, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: var(--transition);
}

.product-card-luxury:hover .product-overlay {
    opacity: 1;
}

.btn-view {
    width: 50px;
    height: 50px;
    background: var(--gold);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--navy);
    text-decoration: none;
    transform: scale(0);
    transition: var(--transition);
}

.product-card-luxury:hover .btn-view {
    transform: scale(1);
}

.btn-view:hover {
    background: white;
    color: var(--gold);
}

.product-info {
    padding: 1.5rem;
    text-align: center;
}

.product-name {
    font-family: 'Playfair Display', serif;
    font-size: 1rem;
    font-weight: 600;
    color: var(--navy);
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.product-price {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    color: var(--gold);
}

.currency {
    font-size: 0.8rem;
    vertical-align: super;
}

.amount {
    font-size: 1.2rem;
}

/* ===== CATEGORY CARDS ===== */
.category-card {
    background: white;
    padding: 2rem;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: var(--transition);
    height: 100%;
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 30px rgba(212, 175, 55, 0.2);
}

.category-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    color: var(--navy);
    animation: sparkle 2s infinite;
}

.category-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 1rem;
}

.category-description {
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.category-link {
    color: var(--gold);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
}

.category-link:hover {
    gap: 1rem;
    color: var(--navy);
}

/* ===== FEATURES GRID ===== */
.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    padding: 2rem;
    background: white;
    border-radius: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.feature-item {
    text-align: center;
    padding: 1rem;
}

.feature-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.8rem;
    color: var(--navy);
    transition: var(--transition);
}

.feature-item:hover .feature-icon {
    transform: rotateY(180deg);
}

.feature-item h4 {
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 0.5rem;
}

.feature-item p {
    color: var(--text-muted);
    font-size: 0.9rem;
}

/* ===== NEWSLETTER ===== */
.newsletter-section {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
    padding: 4rem;
    border-radius: 30px;
    position: relative;
    overflow: hidden;
}

.newsletter-section::before {
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

.newsletter-content {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.newsletter-content h3 {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1rem;
}

.newsletter-content p {
    color: rgba(255,255,255,0.9);
    margin-bottom: 2rem;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
}

.newsletter-form .input-group {
    max-width: 500px;
    margin: 0 auto;
}

.newsletter-form input {
    background: rgba(255,255,255,0.1);
    border: 1px solid var(--gold);
    color: white;
    padding: 1rem;
    border-radius: 50px 0 0 50px;
}

.newsletter-form input:focus {
    background: rgba(255,255,255,0.2);
    outline: none;
    box-shadow: none;
    border-color: var(--gold);
}

.newsletter-form input::placeholder {
    color: rgba(255,255,255,0.7);
    font-family: 'Cormorant Garamond', serif;
    font-style: italic;
}

.btn-newsletter {
    background: var(--gold);
    color: var(--navy);
    border: none;
    padding: 1rem 2rem;
    border-radius: 0 50px 50px 0;
    font-weight: 600;
    transition: var(--transition);
}

.btn-newsletter:hover {
    background: var(--gold-dark);
    transform: translateX(5px);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 991px) {
    .hero-title {
        font-size: 3rem;
    }
    
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .hero-section {
        padding: 3rem 2rem;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-buttons {
        flex-direction: column;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .floating-gem,
    .floating-gem-2 {
        display: none;
    }
}

@media (max-width: 576px) {
    .hero-section {
        padding: 2rem 1rem;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .newsletter-section {
        padding: 2rem 1rem;
    }
    
    .newsletter-form .input-group {
        flex-direction: column;
    }
    
    .newsletter-form input,
    .btn-newsletter {
        border-radius: 50px;
    }
    
    .btn-newsletter {
        margin-top: 1rem;
    }
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
    box-shadow: 0 10px 25px rgba(212, 175, 55, 0.3);
}
</style>

<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>