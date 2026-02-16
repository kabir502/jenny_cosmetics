<?php
// admin/get_user.php
require_once '../config/database.php';
require_once '../config/constants.php';

$user_id = (int)$_GET['id'];
$full_details = isset($_GET['details']) && $_GET['details'] == 'full';

$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => true, 'user' => $row]);
} else {
    echo json_encode(['success' => false]);
}
?>