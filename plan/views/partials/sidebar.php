<?php
/**
 * Sidebar Navigation Component
 */

declare(strict_types=1);

$currentUri = $_SERVER['REQUEST_URI'];
$basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';

// Calculate clean route match
$route = '/';
if ($basePath !== '' && str_starts_with($currentUri, $basePath)) {
    $route = substr($currentUri, strlen($basePath));
}
$route = '/' . ltrim(explode('?', $route)[0], '/');

function isLinkActive(string $path, string $currentRoute): string {
    return ($path === $currentRoute || ($path === '/dashboard' && $currentRoute === '/')) ? 'active' : '';
}
?>
<aside id="sidebar">
    <div class="logo-area">
        <i class="fa-solid fa-wand-magic-sparkles me-2 text-primary" style="font-size: 1.35rem;"></i>
        <span class="logo-text">AetherLife</span>
    </div>
    
    <nav class="menu-links">
        <a href="<?= e(APP_URL) ?>/dashboard" class="<?= isLinkActive('/dashboard', $route) ?>">
            <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
        </a>
        <a href="<?= e(APP_URL) ?>/tasks" class="<?= isLinkActive('/tasks', $route) ?>">
            <i class="fa-solid fa-list-check"></i> <span>Tasks</span>
        </a>
        <a href="<?= e(APP_URL) ?>/habits" class="<?= isLinkActive('/habits', $route) ?>">
            <i class="fa-solid fa-calendar-check"></i> <span>Habits</span>
        </a>
        <a href="<?= e(APP_URL) ?>/goals" class="<?= isLinkActive('/goals', $route) ?>">
            <i class="fa-solid fa-bullseye"></i> <span>Goals</span>
        </a>
        <a href="<?= e(APP_URL) ?>/notes" class="<?= isLinkActive('/notes', $route) ?>">
            <i class="fa-solid fa-note-sticky"></i> <span>Notes</span>
        </a>
        <a href="<?= e(APP_URL) ?>/journal" class="<?= isLinkActive('/journal', $route) ?>">
            <i class="fa-solid fa-book-open"></i> <span>Journal</span>
        </a>
        <a href="<?= e(APP_URL) ?>/pomodoro" class="<?= isLinkActive('/pomodoro', $route) ?>">
            <i class="fa-solid fa-clock"></i> <span>Pomodoro</span>
        </a>
        <a href="<?= e(APP_URL) ?>/analytics" class="<?= isLinkActive('/analytics', $route) ?>">
            <i class="fa-solid fa-square-poll-vertical"></i> <span>Analytics</span>
        </a>
        <a href="<?= e(APP_URL) ?>/settings" class="<?= isLinkActive('/settings', $route) ?>">
            <i class="fa-solid fa-gears"></i> <span>Settings</span>
        </a>
    </nav>
    
    <div class="p-3 border-top border-color">
        <a href="<?= e(APP_URL) ?>/logout" class="d-flex align-items-center text-danger text-decoration-none fw-semibold ps-2" style="font-size: 0.9rem;">
            <i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out
        </a>
    </div>
</aside>
