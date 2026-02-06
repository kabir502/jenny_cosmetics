<?php
// admin/backup_database.php
require_once '../config/database.php';
require_once '../includes/auth_check.php';
requireAdminLogin();

// Only accessible via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'])) {
    header("Location: dashboard.php?error=Invalid security token");
    exit();
}

// Create backup directory if it doesn't exist
$backup_dir = '../backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Generate backup filename
$backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';

// Get all tables
$tables = array();
$result = mysqli_query($connection, 'SHOW TABLES');
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

// Create backup file
$handle = fopen($backup_file, 'w');

// Add SQL header
fwrite($handle, "-- Database backup for " . DB_NAME . "\n");
fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
fwrite($handle, "-- Host: " . DB_HOST . "\n\n");

// Loop through tables
foreach ($tables as $table) {
    // Drop table if exists
    fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n\n");
    
    // Create table structure
    $create_table = mysqli_query($connection, "SHOW CREATE TABLE `$table`");
    $row = mysqli_fetch_row($create_table);
    fwrite($handle, $row[1] . ";\n\n");
    
    // Insert data
    $data_result = mysqli_query($connection, "SELECT * FROM `$table`");
    $num_fields = mysqli_num_fields($data_result);
    
    while ($row = mysqli_fetch_row($data_result)) {
        fwrite($handle, "INSERT INTO `$table` VALUES(");
        
        for ($i = 0; $i < $num_fields; $i++) {
            $row[$i] = addslashes($row[$i]);
            $row[$i] = str_replace("\n", "\\n", $row[$i]);
            
            if (isset($row[$i])) {
                fwrite($handle, "'" . $row[$i] . "'");
            } else {
                fwrite($handle, "NULL");
            }
            
            if ($i < ($num_fields - 1)) {
                fwrite($handle, ",");
            }
        }
        
        fwrite($handle, ");\n");
    }
    
    fwrite($handle, "\n\n");
}

fclose($handle);

// Log the backup in database
$admin_id = $_SESSION['admin_id'];
$backup_size = filesize($backup_file);
$backup_size_mb = round($backup_size / 1024 / 1024, 2);

$log_query = "INSERT INTO site_backups (backup_name, backup_type, file_path, 
              file_size_mb, created_by) VALUES (
              '" . basename($backup_file) . "',
              'Full',
              '$backup_file',
              $backup_size_mb,
              $admin_id)";

mysqli_query($connection, $log_query);

// Provide download link
header("Location: dashboard.php?success=Backup created successfully&backup_file=" . urlencode(basename($backup_file)));
exit();
?>