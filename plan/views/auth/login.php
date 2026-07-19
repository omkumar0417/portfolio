<?php
/**
 * Login Screen View Template
 */

declare(strict_types=1);
?>
<div class="card glass-panel p-4">
    <h3 class="fw-bold text-white text-center mb-3">Sign In</h3>
    
    <form action="<?= e(APP_URL) ?>/login" method="POST">
        <?php csrfInput(); ?>
        
        <div class="mb-3">
            <label for="email" class="form-label text-white small">Email Address</label>
            <input type="email" name="email" id="email" class="form-control bg-transparent text-white border-secondary" placeholder="name@domain.com" required>
        </div>
        
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label for="password" class="form-label text-white small">Password</label>
                <a href="<?= e(APP_URL) ?>/forgot-password" class="small text-decoration-none text-primary fw-medium">Forgot Password?</a>
            </div>
            <input type="password" name="password" id="password" class="form-control bg-transparent text-white border-secondary" placeholder="••••••••" required>
        </div>
        
        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" id="remember" class="form-check-input">
            <label class="form-check-label text-muted small" for="remember">Remember me on this device</label>
        </div>
        
        <button type="submit" class="btn btn-accent w-100 mb-3">Log In</button>
    </form>
    
    <p class="text-center text-muted small mb-0">
        Don't have an account? 
        <a href="<?= e(APP_URL) ?>/signup" class="text-decoration-none text-primary fw-bold">Sign Up</a>
    </p>
</div>
