<?php
// search.php
require_once 'config/database.php';

$search_query = '';
$results = [];

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_query = mysqli_real_escape_string($connection, trim($_GET['q']));
    
    // Search in products
    $search_sql = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE (p.product_name LIKE '%$search_query%' 
                          OR p.description LIKE '%$search_query%' 
                          OR p.short_description LIKE '%$search_query%'
                          OR c.category_name LIKE '%$search_query%')
                     AND p.is_active = 1 
                   ORDER BY p.product_name";
    
    $search_result = mysqli_query($connection, $search_sql);
    
    if (mysqli_num_rows($search_result) > 0) {
        while ($row = mysqli_fetch_assoc($search_result)) {
            $results[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <h2>Search Results</h2>
        
        <div class="mb-4">
            <form action="search.php" method="GET" class="d-flex">
                <input type="text" name="q" class="form-control me-2" 
                       value="<?php echo htmlspecialchars($search_query); ?>" 
                       placeholder="Search products..." required>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        
        <?php if (!empty($search_query)): ?>
            <p>Showing results for: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong></p>
            
            <?php if (empty($results)): ?>
                <div class="alert alert-info">
                    No products found matching your search.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($results as $product): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo $product['image_url']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo $product['product_name']; ?>"
                                         style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted"><?php echo $product['category_name']; ?></small>
                                    </p>
                                    <p class="card-text"><?php echo substr($product['short_description'], 0, 100); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h5">$<?php echo number_format($product['unit_price'], 2); ?></span>
                                        <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-sm btn-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>