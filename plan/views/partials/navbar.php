<?php
/**
 * Top Navigation Bar Component
 */

declare(strict_types=1);

$navName = $_SESSION['user_name'] ?? 'Alex Vance';
$navAvatar = $_SESSION['user_avatar'] ?? 'avatar_demo.png';

// Fallback image path checking
$avatarUrl = APP_URL . '/uploads/avatars/' . $navAvatar;
if ($navAvatar === 'avatar_demo.png') {
    $avatarUrl = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80&auto=format&fit=crop&q=60';
}
?>
<header id="top-navbar">
    <!-- Left Hand Toggler -->
    <div class="d-flex align-items-center">
        <button class="btn btn-sm d-lg-none text-white border-0 me-3" id="mobile-sidebar-toggle" style="font-size: 1.25rem;">
            <i class="fa-solid fa-bars"></i>
        </button>
        
        <h4 class="mb-0 fw-semibold d-none d-sm-block text-truncate fs-5" style="max-width: 250px;">
            <?= e($pageTitle) ?>
        </h4>
    </div>
    
    <!-- Central Global Search & Autocomplete -->
    <div class="search-wrapper d-none d-md-block">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" id="global-search-input" placeholder="Search tasks, notes, goals..." autocomplete="off">
        
        <!-- Dropdown Result overlay -->
        <div id="search-autocomplete-dropdown" class="search-autocomplete-box">
            <!-- Populated via Client AJAX App.js -->
        </div>
    </div>
    
    <!-- Right Hand Action Panels -->
    <div class="d-flex align-items-center gap-3">
        <!-- Notification Center -->
        <div class="dropdown" id="notification-center">
            <button class="btn text-white position-relative p-2" type="button" id="notificationDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" style="background: transparent; border: none;">
                <i class="fa-regular fa-bell" style="font-size: 1.25rem;"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notification-unread-count">
                    0
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end glass-panel p-2" aria-labelledby="notificationDropdownBtn" style="width: 320px; max-height: 400px; overflow-y: auto;">
                <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom border-color mb-2">
                    <span class="fw-bold text-white small">Notifications</span>
                    <button class="btn btn-link p-0 text-decoration-none text-primary small" id="mark-all-read-btn">Clear all</button>
                </div>
                <div id="notification-dropdown-list">
                    <!-- Populated via API polling -->
                    <li class="px-3 py-2 text-muted small text-center">No new notifications.</li>
                </div>
            </ul>
        </div>
        
        <!-- User Profile Quick Selector -->
        <div class="d-flex align-items-center gap-2">
            <a href="<?= e(APP_URL) ?>/settings" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="<?= e($avatarUrl) ?>" alt="Profile" class="rounded-circle border border-2 border-primary" style="width: 38px; height: 38px; object-fit: cover;">
                <span class="text-white d-none d-lg-block fw-semibold" style="font-size: 0.9rem;"><?= e($navName) ?></span>
            </a>
        </div>
    </div>
</header>
