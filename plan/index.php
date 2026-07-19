<?php
/**
 * Application Front Controller / Router
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/helpers.php';

// Class Autoloader
spl_autoload_register(function (string $className) {
    $paths = [
        __DIR__ . '/controllers/',
        __DIR__ . '/models/'
    ];
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Parse requested route path relative to App URL base path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$urlPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';

// Extract base directory path from APP_URL
$appUrlPath = parse_url(APP_URL, PHP_URL_PATH) ?: '';
if ($appUrlPath !== '' && str_starts_with($urlPath, $appUrlPath)) {
    $urlPath = substr($urlPath, strlen($appUrlPath));
}
$routePath = '/' . ltrim($urlPath, '/');

// Handle modular APIs dynamically
if (str_starts_with($routePath, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    // Extract file name
    $apiFileName = substr($routePath, 5); // Remove '/api/'
    
    // Check if subdirectories or queries are passed, strip down to filename
    $apiFileName = explode('/', $apiFileName)[0];
    
    $apiFilePath = __DIR__ . '/api/' . $apiFileName . '.php';
    if (file_exists($apiFilePath)) {
        require_once $apiFilePath;
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'API endpoint not found.']);
        exit;
    }
}

// Map frontend routing paths
$routes = [
    '/' => ['DashboardController', 'index'],
    '/dashboard' => ['DashboardController', 'index'],
    '/login' => ['AuthController', 'login'],
    '/signup' => ['AuthController', 'signup'],
    '/logout' => ['AuthController', 'logout'],
    '/forgot-password' => ['AuthController', 'forgotPassword'],
    '/reset-password' => ['AuthController', 'resetPassword'],
    '/verify-email' => ['AuthController', 'verifyEmail'],
    '/tasks' => ['TaskController', 'index'],
    '/habits' => ['HabitController', 'index'],
    '/goals' => ['GoalController', 'index'],
    '/notes' => ['NoteController', 'index'],
    '/journal' => ['JournalController', 'index'],
    '/pomodoro' => ['PomodoroController', 'index'],
    '/analytics' => ['AnalyticsController', 'index'],
    '/settings' => ['SettingsController', 'index'],
];

if (array_key_exists($routePath, $routes)) {
    [$controllerClass, $method] = $routes[$routePath];
    
    try {
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass();
            if (method_exists($controller, $method)) {
                $controller->$method();
                exit;
            }
        }
        throw new Exception("Controller action not found: {$controllerClass}@{$method}");
    } catch (Exception $e) {
        error_log("Router Error: " . $e->getMessage());
        http_response_code(500);
        die("An unexpected error occurred while processing your request.");
    }
} else {
    // 404 Route Not Found
    http_response_code(404);
    
    // Try to render clean 404 page using layout or simple premium template
    if (isset($_SESSION['user_id'])) {
        $pageTitle = 'Page Not Found';
        $theme = $_SESSION['theme'] ?? DEFAULT_THEME;
        $accent = $_SESSION['accent_color'] ?? DEFAULT_ACCENT;
        $radius = $_SESSION['card_radius'] ?? DEFAULT_RADIUS;
        
        // Load main layout with clean 404 message inside
        require_once __DIR__ . '/views/layouts/main.php';
    } else {
        // Fallback guest 404
        require_once __DIR__ . '/views/auth/404.php';
    }
    exit;
}
