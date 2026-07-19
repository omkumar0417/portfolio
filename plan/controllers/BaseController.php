<?php
/**
 * MVC Base Controller
 */

declare(strict_types=1);

abstract class BaseController {
    /**
     * Renders a primary user interface page within the core layout wrapper.
     */
    protected function render(string $viewPath, array $data = []): void {
        extract($data);
        
        $contentView = __DIR__ . '/../views/' . $viewPath . '.php';
        if (!file_exists($contentView)) {
            error_log("View not found: " . $contentView);
            http_response_code(500);
            die("View template not found.");
        }

        // Global layout variables
        $pageTitle = $data['pageTitle'] ?? 'AetherLife Planner';
        
        // Fetch preferences from session or use defaults
        $theme = $_SESSION['theme'] ?? DEFAULT_THEME;
        $accent = $_SESSION['accent_color'] ?? DEFAULT_ACCENT;
        $radius = (int)($_SESSION['card_radius'] ?? DEFAULT_RADIUS);
        $compact = (int)($_SESSION['compact_mode'] ?? 0);
        $sidebarStyle = $_SESSION['sidebar_style'] ?? 'glassmorphic';
        $wallpaper = $_SESSION['wallpaper'] ?? 'default';
        $fontSize = $_SESSION['font_size'] ?? 'medium';

        require_once __DIR__ . '/../views/layouts/main.php';
    }

    /**
     * Renders authentication/guest views within a dedicated clean layout wrapper.
     */
    protected function renderAuth(string $viewPath, array $data = []): void {
        extract($data);
        
        $contentView = __DIR__ . '/../views/' . $viewPath . '.php';
        if (!file_exists($contentView)) {
            error_log("Auth View not found: " . $contentView);
            http_response_code(500);
            die("Auth View template not found.");
        }

        $pageTitle = $data['pageTitle'] ?? 'Join AetherLife';
        require_once __DIR__ . '/../views/layouts/auth.php';
    }

    /**
     * Restricts page access to authenticated sessions only.
     */
    protected function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            // Save attempt path for target redirect after successful login
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';
            $this->redirect('/login');
        }
    }

    /**
     * Restricts page access to guest sessions only (prevents accessing login while logged in).
     */
    protected function requireGuest(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
    }

    /**
     * Emits a standard structured JSON response.
     */
    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Helper to perform path redirects relative to the application's base URL.
     */
    protected function redirect(string $path): void {
        header('Location: ' . APP_URL . $path);
        exit;
    }
}
