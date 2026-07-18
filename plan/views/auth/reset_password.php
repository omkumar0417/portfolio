<?php
/**
 * Reset Password View Template
 */

declare(strict_types=1);
?>
<div class="card glass-panel p-4">
    <h3 class="fw-bold text-white text-center mb-2">Set New Password</h3>
    <p class="text-muted text-center small mb-4">Choose a strong password containing at least 8 characters.</p>
    
    <form action="<?= e(APP_URL) ?>/reset-password" method="POST">
        <?php csrfInput(); ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        
        <div class="mb-3">
            <label for="password" class="form-label text-white small">New Password</label>
            <input type="password" name="password" id="password" class="form-control bg-transparent text-white border-secondary" placeholder="••••••••" required>
        </div>
        
        <div class="mb-4">
            <label for="confirm_password" class="form-label text-white small">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control bg-transparent text-white border-secondary" placeholder="••••••••" required>
        </div>
        
        <button type="submit" class="btn btn-accent w-100 mb-3">Save Password</button>
    </form>
</div>
