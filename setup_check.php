<?php
// setup_check.php - Check system setup

echo "<h2>System Setup Check</h2>";

// Test 1: Check PHP version
echo "<h3>1. PHP Version Check</h3>";
echo "PHP Version: " . phpversion() . "<br>";
if (version_compare(phpversion(), '7.4.0', '>=')) {
    echo "<span style='color:green;'>✓ PHP version is sufficient</span><br>";
} else {
    echo "<span style='color:red;'>✗ PHP version should be 7.4 or higher</span><br>";
}

// Test 2: Check required extensions
echo "<h3>2. Required Extensions</h3>";
$extensions = ['mysqli', 'session', 'json', 'mbstring', 'openssl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span style='color:green;'>✓ $ext extension is loaded</span><br>";
    } else {
        echo "<span style='color:red;'>✗ $ext extension is NOT loaded</span><br>";
    }
}

// Test 3: Check file permissions
echo "<h3>3. File Permissions</h3>";
$files_to_check = [
    'index.php' => '644',
    'config/constants.php' => '644',
    'config/database.php' => '644',
    'session_handler.php' => '644'
];

foreach ($files_to_check as $file => $recommended) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        if ($perms == $recommended) {
            echo "<span style='color:green;'>✓ $file has correct permissions ($perms)</span><br>";
        } else {
            echo "<span style='color:orange;'>⚠ $file has permissions $perms (recommended: $recommended)</span><br>";
        }
    } else {
        echo "<span style='color:red;'>✗ $file does not exist</span><br>";
    }
}

// Test 4: Check session handling
echo "<h3>4. Session Handling</h3>";
@session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";

// Test 5: Check database connection
echo "<h3>5. Database Connection</h3>";
try {
    $conn = mysqli_connect('localhost', 'root', '', 'jenny_cosmetics_db');
    if ($conn) {
        echo "<span style='color:green;'>✓ Database connection successful</span><br>";
        
        // Check tables
        $tables = ['users', 'products', 'categories', 'orders'];
        foreach ($tables as $table) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
            if (mysqli_num_rows($result) > 0) {
                echo "<span style='color:green;'>✓ Table '$table' exists</span><br>";
            } else {
                echo "<span style='color:red;'>✗ Table '$table' does not exist</span><br>";
            }
        }
        
        mysqli_close($conn);
    } else {
        echo "<span style='color:red;'>✗ Database connection failed: " . mysqli_connect_error() . "</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red;'>✗ Database error: " . $e->getMessage() . "</span><br>";
}

// Test 6: Check if session_start() is being called multiple times
echo "<h3>6. Session Start Check</h3>";
echo "Session status before any code: " . session_status() . " (2 = PHP_SESSION_ACTIVE)<br>";

// Test 7: Check directory structure
echo "<h3>7. Directory Structure</h3>";
$directories = ['config', 'includes', 'assets', 'assets/images'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "<span style='color:green;'>✓ Directory '$dir' exists</span><br>";
    } else {
        echo "<span style='color:red;'>✗ Directory '$dir' does not exist</span><br>";
    }
}

echo "<hr><h3>Summary</h3>";
echo "If you see any red ✗ marks, fix those issues first.<br>";
echo "Yellow ⚠ warnings should be addressed but aren't critical.<br>";
echo "Green ✓ marks mean everything is working correctly.<br>";

echo "<p><a href='index.php' class='btn btn-primary mt-3'>Test Main Page</a></p>";
?>