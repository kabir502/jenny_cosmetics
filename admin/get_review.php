<?php
// admin/get_review.php
require_once '../config/database.php';
require_once '../config/constants.php';

$review_id = (int)$_GET['id'];

$query = "SELECT 
            pr.*,
            u.first_name,
            u.last_name,
            u.email,
            p.product_name,
            p.sku
          FROM product_reviews pr
          JOIN users u ON pr.user_id = u.user_id
          JOIN products p ON pr.product_id = p.product_id
          WHERE pr.review_id = ?";

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $review_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => true, 'review' => $row]);
} else {
    echo json_encode(['success' => false]);
}
?>