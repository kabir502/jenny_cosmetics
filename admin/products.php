<?php
// admin/products.php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
requireAdminLogin();

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total products count
$count_query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
$count_result = mysqli_query($connection, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get products with categories
$query = "SELECT p.*, c.category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE p.is_active = 1 
          ORDER BY p.created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($connection, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Manage Products</h4>
                        <a href="add_product.php" class="btn btn-primary">+ Add New Product</a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="table table-striped" id="productsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $product['product_id']; ?></td>
                                            <td>
                                                <?php if (!empty($product['image_url'])): ?>
                                                    <img src="../<?php echo $product['image_url']; ?>" 
                                                         alt="<?php echo $product['product_name']; ?>" 
                                                         width="50" height="50" class="rounded">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo $product['sku']; ?></td>
                                            <td><?php echo $product['category_name']; ?></td>
                                            <td>$<?php echo number_format($product['unit_price'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $product['quantity_in_stock'] > 10 ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $product['quantity_in_stock']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $product['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" 
                                                   class="btn btn-sm btn-warning">Edit</a>
                                                <a href="delete_product.php?id=<?php echo $product['product_id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure?')">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#productsTable').DataTable({
                "paging": false,
                "searching": true,
                "ordering": true,
                "info": false
            });
        });
    </script>
</body>
</html>