<?php
// includes/auth_check.php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['logged_in'] === true;
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
}

// Redirect to admin login if not authenticated
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: admin/login.php");
        exit();
    }
}

// Check session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>