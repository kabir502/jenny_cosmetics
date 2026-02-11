<?php
// session_handler.php - Centralized session management

// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure session settings
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Function to check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}

// Function to check if admin is logged in
if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}

// Generate CSRF token
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Validate CSRF token
if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Redirect to login if not authenticated
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header("Location: login.php");
            exit();
        }
    }
}

// Check session timeout
if (!function_exists('checkSessionTimeout')) {
    function checkSessionTimeout() {
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
            session_unset();
            session_destroy();
            header("Location: login.php?timeout=1");
            exit();
        }
        $_SESSION['LAST_ACTIVITY'] = time();
    }
}
?>