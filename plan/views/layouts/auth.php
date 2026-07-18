<?php
/**
 * Master Guest Auth Shell Layout
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/helpers.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-bs-theme="dark" class="accent-indigo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    
    <!-- CSS Bindings -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="<?= e(APP_URL) ?>/assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(236, 72, 153, 0.15) 0%, transparent 40%),
                        #030712;
            padding: 2rem 1rem;
        }
        .auth-card-wrapper {
            width: 100%;
            max-width: 420px;
        }
    </style>
</head>
<body>
    
    <div class="auth-card-wrapper" data-aos="zoom-in" data-aos-duration="500">
        
        <div class="text-center mb-4">
            <h1 class="logo-text d-inline-block fw-extrabold fs-2" style="background: linear-gradient(45deg, hsl(239, 85%, 60%), #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">AETHERLIFE</h1>
            <p class="text-muted small mt-1">Elevate Your Personal Productivity</p>
        </div>

        <?php if (hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show glass-panel text-white border-success mb-3" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> <?= e(getFlash('success')) ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show glass-panel text-white border-danger mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= e(getFlash('error')) ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Load auth page content view dynamically -->
        <?php require $contentView; ?>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({ once: true });
        const APP_URL = "<?= e(APP_URL) ?>";
        const CSRF_TOKEN = "<?= e(generateCsrfToken()) ?>";
    </script>
</body>
</html>
