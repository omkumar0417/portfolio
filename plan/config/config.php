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

// Load local configuration overrides containing secret keys if present
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'your_database_name');
if (!defined('DB_USER')) define('DB_USER', 'your_database_user');
if (!defined('DB_PASS')) define('DB_PASS', 'your_database_password');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// SMTP configuration for PHPMailer
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587); // 465 or 587
if (!defined('SMTP_USER')) define('SMTP_USER', 'your_smtp_email');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'your_smtp_password');
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', 'tls'); // 'ssl' or 'tls'
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'your_smtp_email');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'AetherLife Team');

// Default Theme settings
define('DEFAULT_THEME', 'dark');
define('DEFAULT_ACCENT', 'indigo');
define('DEFAULT_RADIUS', 12);

// Cron security configuration
if (!defined('CRON_SECRET')) define('CRON_SECRET', 'aether_cron_secret_123');

// Timezone Setup
date_default_timezone_set('UTC'); // Will load dynamic user timezone from session/db
