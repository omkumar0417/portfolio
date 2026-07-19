<?php
/**
 * Master App Shell Layout
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/helpers.php';

$theme = $theme ?? DEFAULT_THEME;
$accent = $accent ?? DEFAULT_ACCENT;
$radiusVal = $radius ?? DEFAULT_RADIUS;
$compactVal = $compact ?? 0;
$sidebarStyleVal = $sidebarStyle ?? 'glassmorphic';

$radiusClass = 'radius-standard';
if ($radiusVal === 4) {
    $radiusClass = 'radius-sharp';
} elseif ($radiusVal === 24) {
    $radiusClass = 'radius-pillowy';
}

$compactClass = ($compactVal === 1) ? 'compact-mode' : '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($theme) ?>" data-bs-theme="<?= e($theme) ?>" class="accent-<?= e($accent) ?> <?= e($radiusClass) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Core Assets Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Theme Stylesheet -->
    <link href="<?= e(APP_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="<?= e($compactClass) ?>">
    
    <div id="app-layout">
        <!-- Sidebar Widget Component -->
        <?php require_once __DIR__ . '/../partials/sidebar.php'; ?>
        
        <!-- Main Content Layout Section -->
        <div id="main-viewport">
            
            <!-- Navbar Header Component -->
            <?php require_once __DIR__ . '/../partials/navbar.php'; ?>
            
            <!-- Main Inner Scroll Content -->
            <main class="container-fluid p-4" data-aos="fade-in">
                <!-- Session Alert Notifications -->
                <?php if (hasFlash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show glass-panel" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i> <?= e(getFlash('success')) ?>
                        <button type="button" class="btn-close text-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (hasFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show glass-panel" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= e(getFlash('error')) ?>
                        <button type="button" class="btn-close text-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Load target inner view dynamically -->
                <?php require $contentView; ?>
            </main>
            
        </div>
    </div>
    
    <!-- Mobile Bottom Navigation overlay -->
    <?php require_once __DIR__ . '/../partials/mobile_nav.php'; ?>

    <!-- Core CDN Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

    <!-- Global Application Config Constants -->
    <script>
        const APP_URL = "<?= e(APP_URL) ?>";
        const CSRF_TOKEN = "<?= e(generateCsrfToken()) ?>";
        
        // Initialize AOS animations
        document.addEventListener("DOMContentLoaded", function() {
            AOS.init({
                duration: 600,
                once: true,
                offset: 50
            });
        });
    </script>
    
    <!-- Main JS Bundle Asset -->
    <script src="<?= e(APP_URL) ?>/assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>

