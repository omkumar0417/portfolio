<?php
/**
 * Settings & Backup Panel View Template
 */

declare(strict_types=1);

$csrfTokenStr = generateCsrfToken();

$currentAvatar = $user['avatar'] ?? 'avatar_demo.png';
$avatarUrl = APP_URL . '/uploads/avatars/' . $currentAvatar;
if ($currentAvatar === 'avatar_demo.png') {
    $avatarUrl = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120&auto=format&fit=crop&q=60';
}
?>
<div class="row g-4">
    <!-- Left Hand Navigation Tabs links -->
    <div class="col-lg-3" data-aos="fade-right">
        <div class="card glass-panel p-3">
            <div class="nav flex-column nav-pills" id="settings-tab" role="tablist" aria-orientation="vertical">
                <button class="nav-link text-start text-white mb-2 active" id="tab-profile-link" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab" aria-controls="tab-profile" aria-selected="true">
                    <i class="fa-regular fa-user me-2"></i> Profile
                </button>
                <button class="nav-link text-start text-white mb-2" id="tab-theme-link" data-bs-toggle="pill" data-bs-target="#tab-theme" type="button" role="tab" aria-controls="tab-theme" aria-selected="false">
                    <i class="fa-solid fa-palette me-2"></i> Theme & Layout
                </button>
                <button class="nav-link text-start text-white mb-2" id="tab-security-link" data-bs-toggle="pill" data-bs-target="#tab-security" type="button" role="tab" aria-controls="tab-security" aria-selected="false">
                    <i class="fa-solid fa-lock me-2"></i> Security
                </button>
                <button class="nav-link text-start text-white mb-2" id="tab-backup-link" data-bs-toggle="pill" data-bs-target="#tab-backup" type="button" role="tab" aria-controls="tab-backup" aria-selected="false">
                    <i class="fa-solid fa-database me-2"></i> Backup & Data
                </button>
            </div>
        </div>
    </div>

    <!-- Right Hand Forms containers -->
    <div class="col-lg-9" data-aos="fade-left">
        <div class="tab-content" id="settings-tabContent">
            
            <!-- 1. Profile Editing tab -->
            <div class="tab-pane fade show active" id="tab-profile" role="tabpanel" aria-labelledby="tab-profile-link">
                <div class="card glass-panel p-4">
                    <h5 class="text-white fw-bold mb-4">Profile Metadata Details</h5>
                    
                    <div class="row align-items-center mb-4">
                        <div class="col-auto">
                            <img src="<?= e($avatarUrl) ?>" alt="Avatar" class="rounded-circle border border-primary border-3" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                        <div class="col-auto">
                            <form action="<?= e(APP_URL) ?>/settings" method="POST" enctype="multipart/form-data">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="action" value="update_avatar">
                                <div class="input-group input-group-sm">
                                    <input type="file" name="avatar" class="form-control bg-transparent text-white border-secondary" required>
                                    <button type="submit" class="btn btn-accent small">Upload Photo</button>
                                </div>
                                <div class="form-text text-muted" style="font-size:0.75rem;">JPG, PNG or GIF, max 2MB.</div>
                            </form>
                        </div>
                    </div>

                    <form action="<?= e(APP_URL) ?>/settings" method="POST">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="prof-name" class="form-label text-muted small">Display Name</label>
                                <input type="text" name="name" id="prof-name" class="form-control bg-transparent text-white border-secondary" value="<?= e($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="prof-email" class="form-label text-muted small">Email Address (Locked)</label>
                                <input type="email" id="prof-email" class="form-control bg-transparent text-muted border-secondary" value="<?= e($user['email']) ?>" readonly>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="prof-timezone" class="form-label text-muted small">Timezone</label>
                                <select name="timezone" id="prof-timezone" class="form-select bg-transparent text-white border-secondary">
                                    <option value="UTC" class="bg-dark" <?= $user['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC (Standard)</option>
                                    <option value="Asia/Kolkata" class="bg-dark" <?= $user['timezone'] === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata</option>
                                    <option value="America/New_York" class="bg-dark" <?= $user['timezone'] === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                                    <option value="Europe/London" class="bg-dark" <?= $user['timezone'] === 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="prof-country" class="form-label text-muted small">Country</label>
                                <input type="text" name="country" id="prof-country" class="form-control bg-transparent text-white border-secondary" value="<?= e($user['country'] ?? '') ?>" placeholder="India, Germany...">
                            </div>
                            <div class="col-md-4">
                                <label for="prof-lang" class="form-label text-muted small">Language</label>
                                <select name="language" id="prof-lang" class="form-select bg-transparent text-white border-secondary">
                                    <option value="en" class="bg-dark" selected>English</option>
                                    <option value="de" class="bg-dark">Deutsch</option>
                                    <option value="fr" class="bg-dark">Français</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="prof-occ" class="form-label text-muted small">Occupation</label>
                                <input type="text" name="occupation" id="prof-occ" class="form-control bg-transparent text-white border-secondary" value="<?= e($user['occupation'] ?? '') ?>" placeholder="Developer, Student...">
                            </div>
                            <div class="col-md-6">
                                <label for="prof-bday" class="form-label text-muted small">Birthday</label>
                                <input type="date" name="birthday" id="prof-bday" class="form-control bg-transparent text-white border-secondary" value="<?= e($user['birthday'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="prof-bio" class="form-label text-muted small">Bio Statement</label>
                            <textarea name="bio" id="prof-bio" rows="3" class="form-control bg-transparent text-white border-secondary" placeholder="A brief sentence about your visual workspace goals..."><?= e($user['bio'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-accent"><i class="fa-regular fa-floppy-disk me-2"></i> Save Profile Details</button>
                    </form>
                </div>
            </div>

            <!-- 2. Theme Customizer tab -->
            <div class="tab-pane fade" id="tab-theme" role="tabpanel" aria-labelledby="tab-theme-link">
                <div class="card glass-panel p-4">
                    <h5 class="text-white fw-bold mb-4">Workspace Themes & Design Configs</h5>
                    
                    <form action="<?= e(APP_URL) ?>/settings" method="POST">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="action" value="update_theme">

                        <div class="row g-3 mb-4">
                            <!-- Theme Light/Dark radios -->
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Mode / Brightness</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input type="radio" name="theme" id="theme-dark" class="form-check-input" value="dark" <?= ($settings['theme'] ?? 'dark') === 'dark' ? 'checked' : '' ?>>
                                        <label for="theme-dark" class="form-check-label text-white small">Dark Space Mode</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="theme" id="theme-light" class="form-check-input" value="light" <?= ($settings['theme'] ?? 'dark') === 'light' ? 'checked' : '' ?>>
                                        <label for="theme-light" class="form-check-label text-white small">Light Office Mode</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Accent selection -->
                            <div class="col-md-6">
                                <label for="accent-picker" class="form-label text-muted small">Accent Neon Tint</label>
                                <select name="accent_color" id="accent-picker" class="form-select bg-transparent text-white border-secondary">
                                    <option value="indigo" class="bg-dark" <?= ($settings['accent_color'] ?? 'indigo') === 'indigo' ? 'selected' : '' ?>>Slate Blue</option>
                                    <option value="emerald" class="bg-dark" <?= ($settings['accent_color'] ?? 'indigo') === 'emerald' ? 'selected' : '' ?>>Emerald Green</option>
                                    <option value="purple" class="bg-dark" <?= ($settings['accent_color'] ?? 'indigo') === 'purple' ? 'selected' : '' ?>>Royal Purple</option>
                                    <option value="orange" class="bg-dark" <?= ($settings['accent_color'] ?? 'indigo') === 'orange' ? 'selected' : '' ?>>Coral Orange</option>
                                    <option value="red" class="bg-dark" <?= ($settings['accent_color'] ?? 'indigo') === 'red' ? 'selected' : '' ?>>Sunset Red</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Card radius selection -->
                            <div class="col-md-6">
                                <label for="radius-picker" class="form-label text-muted small">Card Border Radius</label>
                                <select name="card_radius" id="radius-picker" class="form-select bg-transparent text-white border-secondary">
                                    <option value="4" class="bg-dark" <?= ($settings['card_radius'] ?? 12) == 4 ? 'selected' : '' ?>>Sharp (4px)</option>
                                    <option value="12" class="bg-dark" <?= ($settings['card_radius'] ?? 12) == 12 ? 'selected' : '' ?>>Standard (12px)</option>
                                    <option value="24" class="bg-dark" <?= ($settings['card_radius'] ?? 12) == 24 ? 'selected' : '' ?>>Pillowy (24px)</option>
                                </select>
                            </div>
                            
                            <!-- Sidebar Style -->
                            <div class="col-md-6">
                                <label for="sidebar-picker" class="form-label text-muted small">Sidebar Canvas Theme</label>
                                <select name="sidebar_style" id="sidebar-picker" class="form-select bg-transparent text-white border-secondary">
                                    <option value="glassmorphic" class="bg-dark" <?= ($settings['sidebar_style'] ?? 'glassmorphic') === 'glassmorphic' ? 'selected' : '' ?>>Glassmorphic</option>
                                    <option value="solid_dark" class="bg-dark" <?= ($settings['sidebar_style'] ?? 'glassmorphic') === 'solid_dark' ? 'selected' : '' ?>>Solid Slate</option>
                                </select>
                            </div>
                        </div>

                        <!-- Spacing density (Compact mode) -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="compact_mode" id="compact-toggle" value="1" <?= ($settings['compact_mode'] ?? 0) == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label text-white small" for="compact-toggle">Compact View (reduces layout margins & padding spaces)</label>
                            </div>
                        </div>

                        <!-- Notification toggles -->
                        <h6 class="text-white fw-bold small mb-3">Notification Preferences</h6>
                        <div class="mb-4">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="notification_email" id="notif-email" class="form-check-input" value="1" <?= ($settings['notification_email'] ?? 1) == 1 ? 'checked' : '' ?>>
                                <label for="notif-email" class="form-check-label text-muted small">Email Summaries (Receive daily morning lists at 7:30 AM)</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="notification_browser" id="notif-browser" class="form-check-input" value="1" <?= ($settings['notification_browser'] ?? 1) == 1 ? 'checked' : '' ?>>
                                <label for="notif-browser" class="form-check-label text-muted small">Browser Notifications alerts (Sounds chime on pomodoro timer completion)</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-accent"><i class="fa-solid fa-paintbrush me-2"></i> Save Layout preferences</button>
                    </form>
                </div>
            </div>

            <!-- 3. Security configurations tab -->
            <div class="tab-pane fade" id="tab-security" role="tabpanel" aria-labelledby="tab-security-link">
                <div class="card glass-panel p-4">
                    <h5 class="text-white fw-bold mb-4">Change Account Credentials</h5>
                    
                    <form action="<?= e(APP_URL) ?>/settings" method="POST">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="old-pass" class="form-label text-muted small">Current Password</label>
                            <input type="password" name="old_password" id="old-pass" class="form-control bg-transparent text-white border-secondary" required>
                        </div>
                        <div class="mb-3">
                            <label for="new-pass" class="form-label text-muted small">New Password</label>
                            <input type="password" name="new_password" id="new-pass" class="form-control bg-transparent text-white border-secondary" required>
                        </div>
                        <div class="mb-4">
                            <label for="conf-pass" class="form-label text-muted small">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="conf-pass" class="form-control bg-transparent text-white border-secondary" required>
                        </div>

                        <button type="submit" class="btn btn-accent"><i class="fa-solid fa-key me-2"></i> Update Password</button>
                    </form>
                </div>
            </div>

            <!-- 4. Backup & Data Utilities tab -->
            <div class="tab-pane fade" id="tab-backup" role="tabpanel" aria-labelledby="tab-backup-link">
                <div class="card glass-panel p-4">
                    <h5 class="text-white fw-bold mb-3">Backup & Data Portability</h5>
                    <p class="text-muted small mb-4">Secure your assets. Export your data records as spreadsheet tables, backup files, or JSON logs bundles.</p>
                    
                    <div class="row g-3 mb-4">
                        <!-- Export Actions -->
                        <div class="col-md-4">
                            <form action="<?= e(APP_URL) ?>/settings" method="POST">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="action" value="export_json">
                                <button type="submit" class="btn btn-outline-secondary w-100 py-3 text-white"><i class="fa-solid fa-file-code me-2"></i> Export JSON</button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form action="<?= e(APP_URL) ?>/settings" method="POST">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="action" value="export_csv">
                                <button type="submit" class="btn btn-outline-secondary w-100 py-3 text-white"><i class="fa-solid fa-file-csv me-2"></i> Export Tasks CSV</button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form action="<?= e(APP_URL) ?>/settings" method="POST">
                                <?php csrfInput(); ?>
                                <input type="hidden" name="action" value="backup_db">
                                <button type="submit" class="btn btn-outline-secondary w-100 py-3 text-white"><i class="fa-solid fa-database me-2"></i> Export SQL Backup</button>
                            </form>
                        </div>
                    </div>

                    <!-- Termination warnings -->
                    <h5 class="text-danger fw-bold border-top border-color pt-4 mt-3 mb-3">Danger Zone Options</h5>
                    <div class="p-3 border border-danger border-opacity-25 rounded bg-danger bg-opacity-10 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="text-danger fw-bold mb-0">Permanently Delete Account</h6>
                            <p class="text-muted small mb-0" style="font-size:0.75rem;">This will cascade delete all logs, notes, journals, and credentials. Action cannot be undone.</p>
                        </div>
                        <form action="<?= e(APP_URL) ?>/settings" method="POST" onsubmit="return confirm('WARNING: Are you absolutely sure you want to permanently delete your AetherLife account and purge all databases data?');">
                            <?php csrfInput(); ?>
                            <input type="hidden" name="action" value="delete_account">
                            <button type="submit" class="btn btn-danger btn-sm">Delete Account</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
