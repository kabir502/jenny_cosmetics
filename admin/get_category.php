<?php
// admin/get_category.php
require_once '../config/database.php';
require_once '../config/constants.php';

$category_id = (int)$_GET['id'];

$query = "SELECT * FROM categories WHERE category_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => true, 'category' => $row]);
} else {
    echo json_encode(['success' => false]);
}
?>