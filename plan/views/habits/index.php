<?php
/**
 * Habit Tracker View Template
 */

declare(strict_types=1);

$csrfTokenStr = generateCsrfToken();
?>
<div class="row g-4 mb-4">
    <!-- Header Summary Stats -->
    <div class="col-12" data-aos="fade-down">
        <div class="card glass-panel p-4 d-flex flex-row justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="text-white fw-bold mb-1">Consistency Tracker</h4>
                <p class="text-muted small mb-0">Build compounding habits. Tap to check off items for the past week.</p>
            </div>
            <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addHabitModal">
                <i class="fa-solid fa-plus me-1"></i> Add Habit
            </button>
        </div>
    </div>
</div>

<!-- Habits Weekly Checklist Board Grid -->
<div class="row g-4 mb-4" id="habits-board-grid">
    <?php if (empty($habits)): ?>
        <div class="col-12 text-center py-5" data-aos="zoom-in">
            <i class="fa-solid fa-calendar-check text-muted display-4 mb-3"></i>
            <h5 class="text-white">No habits tracked yet</h5>
            <p class="text-muted small">Establish daily routines like Drinking Water, Reading, or DSA coding.</p>
            <button class="btn btn-accent mt-2" data-bs-toggle="modal" data-bs-target="#addHabitModal">Start Seeding Habits</button>
        </div>
    <?php else: ?>
        <?php foreach ($habits as $h): ?>
            <div class="col-12" data-aos="fade-up" id="habit-row-<?= $h['id'] ?>">
                <div class="card glass-panel p-3">
                    <div class="row align-items-center g-3">
                        <!-- Habit metadata details -->
                        <div class="col-lg-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 48px; height: 48px; background-color: <?= e($h['color']) ?>20; color: <?= e($h['color']) ?>;">
                                    <i class="fa-solid <?= e($h['icon']) ?> fs-5"></i>
                                </div>
                                <div class="text-truncate" style="max-width: 80%;">
                                    <h5 class="text-white fw-bold mb-0 fs-6"><?= e($h['name']) ?></h5>
                                    <span class="text-muted small"><?= e(ucfirst($h['frequency'])) ?></span>
                                    <span class="badge bg-secondary ms-1 small" style="font-size:0.7rem; background-color: <?= $h['category_color'] ?: '#6c757d' ?> !important;"><?= e($h['category_name'] ?: 'General') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Weekly completion calendar block -->
                        <div class="col-lg-6">
                            <div class="d-flex justify-content-around text-center">
                                <?php foreach ($dates as $date): 
                                    $dayName = date('D', strtotime($date));
                                    $dayNum = date('d', strtotime($date));
                                    $status = $logsMap[$h['id']][$date] ?? 'missed';
                                    $checked = $status === 'completed';
                                ?>
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="text-muted small mb-1" style="font-size: 0.75rem;"><?= $dayName ?></span>
                                        <button class="habit-check-btn <?= $checked ? 'checked' : '' ?>" 
                                                onclick="toggleHabitLog(<?= $h['id'] ?>, '<?= $date ?>', this)"
                                                style="width: 32px; height: 32px; border-color: <?= e($h['color']) ?>; background: <?= $checked ? e($h['color']) : 'transparent' ?>;"
                                                title="Mark log for <?= $date ?>">
                                            <i class="fa-solid fa-check text-white fs-6 <?= $checked ? '' : 'd-none' ?>"></i>
                                        </button>
                                        <span class="text-muted small mt-1" style="font-size: 0.7rem;"><?= $dayNum ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Streaks statistics metrics -->
                        <div class="col-lg-3">
                            <div class="d-flex justify-content-between align-items-center border-start border-color ps-3">
                                <div>
                                    <div class="small text-muted mb-0">Streak</div>
                                    <span class="text-white fw-bold fs-5"><i class="fa-solid fa-fire text-warning me-1"></i><?= (int)$h['current_streak'] ?>d</span>
                                </div>
                                <div>
                                    <div class="small text-muted mb-0">Success Rate</div>
                                    <span class="text-white fw-bold fs-5"><i class="fa-solid fa-square-poll-vertical text-primary me-1"></i><?= (float)$h['success_rate'] ?>%</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0 border-0" type="button" id="habitOptDropdown<?= $h['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-vertical fs-5"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end glass-panel" aria-labelledby="habitOptDropdown<?= $h['id'] ?>">
                                        <li><button class="dropdown-item text-white small" onclick="openEditHabitModal(<?= $h['id'] ?>)"><i class="fa-regular fa-pen-to-square me-2"></i> Edit</button></li>
                                        <li>
                                            <form action="<?= e(APP_URL) ?>/habits" method="POST" onsubmit="return confirm('Delete this habit tracker? All logs history will be permanently deleted.');">
                                                <?php csrfInput(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                                <button type="submit" class="dropdown-item text-danger small"><i class="fa-regular fa-trash-can me-2"></i> Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modals for Add & Edit Habit Actions -->
<!-- Add Habit Modal -->
<div class="modal fade" id="addHabitModal" tabindex="-1" aria-labelledby="addHabitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel text-white p-3" style="background-color: var(--bg-card) !important;">
            <div class="modal-header border-color">
                <h5 class="modal-title fw-bold" id="addHabitModalLabel">Create Habit tracker</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= e(APP_URL) ?>/habits" method="POST">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add-h-name" class="form-label small">Habit Name</label>
                        <input type="text" name="name" id="add-h-name" class="form-control bg-transparent text-white border-secondary" placeholder="Drink water, workout..." required>
                    </div>
                    <div class="mb-3">
                        <label for="add-h-desc" class="form-label small">Description</label>
                        <textarea name="description" id="add-h-desc" rows="2" class="form-control bg-transparent text-white border-secondary" placeholder="Optional routine steps details..."></textarea>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="add-h-cat" class="form-label small">Category</label>
                            <select name="category_id" id="add-h-cat" class="form-select bg-transparent text-white border-secondary">
                                <option value="" class="bg-dark">General</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" class="bg-dark"><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add-h-freq" class="form-label small">Frequency</label>
                            <select name="frequency" id="add-h-freq" class="form-select bg-transparent text-white border-secondary">
                                <option value="daily" class="bg-dark" selected>Daily</option>
                                <option value="weekdays" class="bg-dark">Weekdays</option>
                                <option value="weekends" class="bg-dark">Weekends</option>
                                <option value="weekly" class="bg-dark">Weekly</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="add-h-color" class="form-label small">Theme Color</label>
                            <input type="color" name="color" id="add-h-color" class="form-control form-control-color bg-transparent border-secondary w-100" value="#6366f1">
                        </div>
                        <div class="col-md-6">
                            <label for="add-h-icon" class="form-label small">FontAwesome Icon</label>
                            <input type="text" name="icon" id="add-h-icon" class="form-control bg-transparent text-white border-secondary" value="fa-circle" placeholder="fa-tint">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-color">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">Save Habit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Habit Modal -->
<div class="modal fade" id="editHabitModal" tabindex="-1" aria-labelledby="editHabitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel text-white p-3" style="background-color: var(--bg-card) !important;">
            <div class="modal-header border-color">
                <h5 class="modal-title fw-bold" id="editHabitModalLabel">Modify Habit Options</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= e(APP_URL) ?>/habits" method="POST" id="edit-habit-form">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-h-id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-h-name" class="form-label small">Habit Name</label>
                        <input type="text" name="name" id="edit-h-name" class="form-control bg-transparent text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-h-desc" class="form-label small">Description</label>
                        <textarea name="description" id="edit-h-desc" rows="2" class="form-control bg-transparent text-white border-secondary"></textarea>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit-h-cat" class="form-label small">Category</label>
                            <select name="category_id" id="edit-h-cat" class="form-select bg-transparent text-white border-secondary">
                                <option value="" class="bg-dark">General</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" class="bg-dark"><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-h-freq" class="form-label small">Frequency</label>
                            <select name="frequency" id="edit-h-freq" class="form-select bg-transparent text-white border-secondary">
                                <option value="daily" class="bg-dark">Daily</option>
                                <option value="weekdays" class="bg-dark">Weekdays</option>
                                <option value="weekends" class="bg-dark">Weekends</option>
                                <option value="weekly" class="bg-dark">Weekly</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit-h-color" class="form-label small">Theme Color</label>
                            <input type="color" name="color" id="edit-h-color" class="form-control form-control-color bg-transparent border-secondary w-100">
                        </div>
                        <div class="col-md-6">
                            <label for="edit-h-icon" class="form-label small">FontAwesome Icon</label>
                            <input type="text" name="icon" id="edit-h-icon" class="form-control bg-transparent text-white border-secondary">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-color">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= e(APP_URL) ?>/assets/js/habits.js"></script>
