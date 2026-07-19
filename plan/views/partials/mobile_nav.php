<?php
/**
 * Sticky Bottom Navigation Component for Mobile Screens
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

function isMobileLinkActive(string $path, string $currentRoute): string {
    return ($path === $currentRoute || ($path === '/dashboard' && $currentRoute === '/')) ? 'active' : '';
}
?>
<nav id="mobile-bottom-nav" class="d-lg-none glass-panel">
    <ul>
        <li>
            <a href="<?= e(APP_URL) ?>/dashboard" class="<?= isMobileLinkActive('/dashboard', $route) ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span>Home</span>
            </a>
        </li>
        <li>
            <a href="<?= e(APP_URL) ?>/tasks" class="<?= isMobileLinkActive('/tasks', $route) ?>">
                <i class="fa-solid fa-list-check"></i>
                <span>Tasks</span>
            </a>
        </li>
        <li>
            <a href="<?= e(APP_URL) ?>/habits" class="<?= isMobileLinkActive('/habits', $route) ?>">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Habits</span>
            </a>
        </li>
        <li>
            <a href="<?= e(APP_URL) ?>/pomodoro" class="<?= isMobileLinkActive('/pomodoro', $route) ?>">
                <i class="fa-solid fa-clock"></i>
                <span>Pomodoro</span>
            </a>
        </li>
        <li>
            <a href="<?= e(APP_URL) ?>/notes" class="<?= isMobileLinkActive('/notes', $route) ?>">
                <i class="fa-solid fa-note-sticky"></i>
                <span>Notes</span>
            </a>
        </li>
    </ul>
</nav>
