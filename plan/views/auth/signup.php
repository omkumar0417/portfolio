<?php
/**
 * Sign Up Screen View Template
 */

declare(strict_types=1);
?>
<div class="card glass-panel p-4">
    <h3 class="fw-bold text-white text-center mb-3">Create Account</h3>
    
    <form action="<?= e(APP_URL) ?>/signup" method="POST">
        <?php csrfInput(); ?>
        
        <div class="mb-3">
            <label for="name" class="form-label text-white small">Full Name</label>
            <input type="text" name="name" id="name" class="form-control bg-transparent text-white border-secondary" placeholder="John Doe" required>
        </div>
        
        <div class="mb-3">
            <label for="email" class="form-label text-white small">Email Address</label>
            <input type="email" name="email" id="email" class="form-control bg-transparent text-white border-secondary" placeholder="name@domain.com" required>
        </div>
        
        <div class="mb-3">
            <label for="password" class="form-label text-white small">Password</label>
            <input type="password" name="password" id="password" class="form-control bg-transparent text-white border-secondary" placeholder="••••••••" required>
            <div class="form-text text-muted" style="font-size: 0.75rem;">Password must be at least 8 characters long.</div>
        </div>
        
        <div class="mb-3">
            <label for="confirm_password" class="form-label text-white small">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control bg-transparent text-white border-secondary" placeholder="••••••••" required>
        </div>
        
        <button type="submit" class="btn btn-accent w-100 mb-3">Sign Up</button>
    </form>
    
    <p class="text-center text-muted small mb-0">
        Already have an account? 
        <a href="<?= e(APP_URL) ?>/login" class="text-decoration-none text-primary fw-bold">Sign In</a>
    </p>
</div>
