<?php
/**
 * Forgot Password View Template
 */

declare(strict_types=1);
?>
<div class="card glass-panel p-4">
    <h3 class="fw-bold text-white text-center mb-2">Recover Password</h3>
    <p class="text-muted text-center small mb-4">Enter your email address and we'll dispatch a link to reset your password.</p>
    
    <form action="<?= e(APP_URL) ?>/forgot-password" method="POST">
        <?php csrfInput(); ?>
        
        <div class="mb-4">
            <label for="email" class="form-label text-white small">Email Address</label>
            <input type="email" name="email" id="email" class="form-control bg-transparent text-white border-secondary" placeholder="name@domain.com" required>
        </div>
        
        <button type="submit" class="btn btn-accent w-100 mb-3">Send Reset Link</button>
    </form>
    
    <p class="text-center text-muted small mb-0">
        Go back to 
        <a href="<?= e(APP_URL) ?>/login" class="text-decoration-none text-primary fw-bold">Sign In</a>
    </p>
</div>
