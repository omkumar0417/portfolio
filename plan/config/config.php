<?php
/**
 * Application Core Configuration
 */

// Define strict typing
declare(strict_types=1);

// Prevent direct file access if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global Application Constants
define('APP_NAME', 'AetherLife Planner');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'plan.omkumar0417.in';
define('APP_URL', $protocol . $domainName);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB limits

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u690119069_omkumar');
define('DB_USER', 'u690119069_plan');
define('DB_PASS', '87@omkumar@OM');
define('DB_CHARSET', 'utf8mb4');

// SMTP configuration for PHPMailer
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // 465 or 587
define('SMTP_USER', 'focusforgemind@gmail.com');
define('SMTP_PASS', 'rhqqqgavooscneaa');
define('SMTP_SECURE', 'tls'); // 'ssl' or 'tls'
define('SMTP_FROM_EMAIL', 'focusforgemind@gmail.com');
define('SMTP_FROM_NAME', 'AetherLife Team');

// Default Theme settings
define('DEFAULT_THEME', 'dark');
define('DEFAULT_ACCENT', 'indigo');
define('DEFAULT_RADIUS', 12);

// Timezone Setup
date_default_timezone_set('UTC'); // Will load dynamic user timezone from session/db
