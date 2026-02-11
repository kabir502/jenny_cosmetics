<?php
// // config/constants.php

// // Site constants
// define('SITE_NAME', "Jenny's Cosmetics & Jewelry");
// define('SITE_URL', 'http://localhost/jenny_cosmetics');
// define('ADMIN_EMAIL', 'admin@jennyscosmetics.com');

// // Path constants
// define('UPLOAD_PATH', 'assets/images/products/');
// define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

// // Email configuration (for OTP)
// define('SMTP_HOST', 'smtp.gmail.com');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'kabirbaloch4444@gmail.com');
// define('SMTP_PASS', 'gwmdzicwjpgnskfd');

// // Session timeout (30 minutes)
// define('SESSION_TIMEOUT', 1800);


// config/constants.php

// Define constants only if not already defined
// if (!defined('SITE_NAME')) {
//     define('SITE_NAME', "Jenny's Cosmetics & Jewelry");
// }

// if (!defined('SITE_URL')) {
//     define('SITE_URL', 'http://localhost:82');
// }

// if (!defined('ADMIN_EMAIL')) {
//     define('ADMIN_EMAIL', 'admin@jennyscosmetics.com');
// }

// if (!defined('UPLOAD_PATH')) {
//     define('UPLOAD_PATH', 'assets/images/products/');
// }

// if (!defined('MAX_FILE_SIZE')) {
//     define('MAX_FILE_SIZE', 5242880); // 5MB
// }

// if (!defined('SESSION_TIMEOUT')) {
//     define('SESSION_TIMEOUT', 1800); // 30 minutes
// }

// // Development mode - set to false in production
// if (!defined('DEBUG_MODE')) {
//     define('DEBUG_MODE', true);
// }

// // Error reporting based on debug mode
// if (DEBUG_MODE) {
//     error_reporting(E_ALL);
//     ini_set('display_errors', 1);
//     ini_set('log_errors', 1);
//     ini_set('error_log', __DIR__ . '/../error_log.txt');
// } else {
//     error_reporting(0);
//     ini_set('display_errors', 0);
// }


// config/constants.php

// Define constants safely
defined('SITE_NAME') || define('SITE_NAME', "Jenny's Cosmetics & Jewelry");
defined('SITE_URL') || define('SITE_URL', 'http://localhost:82/jenny_cosmetics');
defined('ADMIN_EMAIL') || define('ADMIN_EMAIL', 'admin@jennyscosmetics.com');
defined('UPLOAD_PATH') || define('UPLOAD_PATH', 'assets/images/products/');
defined('MAX_FILE_SIZE') || define('MAX_FILE_SIZE', 5242880); // 5MB
defined('SESSION_TIMEOUT') || define('SESSION_TIMEOUT', 1800); // 30 minutes
defined('DEBUG_MODE') || define('DEBUG_MODE', true);

// Email configuration (for OTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'kabirbaloch4444@gmail.com');
define('SMTP_PASS', 'gwmdzicwjpgnskfd');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/error_log.txt');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>

