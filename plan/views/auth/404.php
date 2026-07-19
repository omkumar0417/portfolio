<?php
/**
 * Guest Fallback 404 View Template
 */

declare(strict_types=1);
?>
<div class="card glass-panel p-4 text-center">
    <h1 class="display-1 fw-bold text-primary mb-3">404</h1>
    <h4 class="text-white mb-3">Page Not Found</h4>
    <p class="text-muted small mb-4">The route you requested could not be resolved. It may have been moved or doesn't exist.</p>
    
    <a href="<?= e(APP_URL) ?>/login" class="btn btn-accent w-100">
        <i class="fa-solid fa-house-chimney me-2"></i> Go to Sign In
    </a>
</div>
