<?php
/**
 * Goals Planning View Template
 */

declare(strict_types=1);

$csrfTokenStr = generateCsrfToken();
?>
<div class="row g-4 mb-4">
    <!-- Filters Header -->
    <div class="col-12" data-aos="fade-down">
        <div class="card glass-panel p-3 d-flex flex-row justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex flex-wrap gap-2" id="goal-tabs-filters">
                <a href="<?= e(APP_URL) ?>/goals" class="btn btn-sm btn-outline-secondary px-3 <?= empty($filters['type']) ? 'active' : '' ?>">All Objectives</a>
                <a href="<?= e(APP_URL) ?>/goals?type=short_term" class="btn btn-sm btn-outline-secondary px-3 <?= ($filters['type'] ?? '') === 'short_term' ? 'active' : '' ?>">Short Term</a>
                <a href="<?= e(APP_URL) ?>/goals?type=quarterly" class="btn btn-sm btn-outline-secondary px-3 <?= ($filters['type'] ?? '') === 'quarterly' ? 'active' : '' ?>">Quarterly</a>
                <a href="<?= e(APP_URL) ?>/goals?type=yearly" class="btn btn-sm btn-outline-secondary px-3 <?= ($filters['type'] ?? '') === 'yearly' ? 'active' : '' ?>">Yearly</a>
                <a href="<?= e(APP_URL) ?>/goals?type=vision" class="btn btn-sm btn-outline-secondary px-3 <?= ($filters['type'] ?? '') === 'vision' ? 'active' : '' ?>">Vision Board</a>
            </div>
            
            <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                <i class="fa-solid fa-plus me-1"></i> Add Goal
            </button>
        </div>
    </div>
</div>

<!-- Goals list grid mapping -->
<div class="row g-4" id="goals-board-grid">
    <?php if (empty($goals)): ?>
        <div class="col-12 text-center py-5" data-aos="zoom-in">
            <i class="fa-solid fa-bullseye text-muted display-4 mb-3"></i>
            <h5 class="text-white">No goals tracked in this scope</h5>
            <p class="text-muted small">Establish big achievements targets. Add milestones to chunk down execution plans.</p>
        </div>
    <?php else: ?>
        <?php foreach ($goals as $g): 
            $statusColors = [
                'pending' => 'bg-secondary',
                'in_progress' => 'bg-warning text-dark',
                'completed' => 'bg-success',
                'abandoned' => 'bg-danger'
            ];
            $statusBadge = $statusColors[$g['status']] ?? 'bg-secondary';
        ?>
            <div class="col-lg-6" data-aos="fade-up" id="goal-card-<?= $g['id'] ?>">
                <div class="card glass-panel p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge <?= $statusBadge ?> text-uppercase small mb-1"><?= e($g['status']) ?></span>
                                <span class="badge bg-dark border border-secondary text-muted text-uppercase small mb-1"><?= e(str_replace('_', ' ', $g['type'])) ?></span>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link text-muted p-0 border-0" type="button" id="goalOptDropdown<?= $g['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical fs-5"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end glass-panel" aria-labelledby="goalOptDropdown<?= $g['id'] ?>">
                                    <li><button class="dropdown-item text-white small" onclick="openEditGoalModal(<?= $g['id'] ?>)"><i class="fa-regular fa-pen-to-square me-2"></i> Edit</button></li>
                                    <li>
                                        <form action="<?= e(APP_URL) ?>/goals" method="POST" onsubmit="return confirm('Permanently delete this goal and its milestones?');">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                            <button type="submit" class="dropdown-item text-danger small"><i class="fa-regular fa-trash-can me-2"></i> Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <h4 class="text-white fw-bold mb-2"><?= e($g['title']) ?></h4>
                        <p class="text-muted small mb-3"><?= e($g['description']) ?></p>

                        <?php if ($g['deadline']): ?>
                            <div class="text-warning small mb-3"><i class="fa-regular fa-calendar me-1"></i> Target Deadline: <?= date('d M Y', strtotime($g['deadline'])) ?></div>
                        <?php endif; ?>

                        <!-- Milestones Checklist wrapper -->
                        <h6 class="text-white fw-bold small mb-2"><i class="fa-solid fa-circle-nodes text-primary me-2"></i>Key Results Milestones</h6>
                        <div class="milestones-list-group mb-3" id="milestones-container-<?= $g['id'] ?>">
                            <?php if (empty($g['milestones'])): ?>
                                <p class="text-muted small my-2 text-center" id="empty-ms-<?= $g['id'] ?>">No milestones logged yet.</p>
                            <?php else: ?>
                                <?php foreach ($g['milestones'] as $ms): 
                                    $msChecked = $ms['is_completed'] == 1;
                                    $msTextClass = $msChecked ? 'text-decoration-line-through text-muted' : '';
                                ?>
                                    <div class="d-flex justify-content-between align-items-center mb-1 py-1 px-2 rounded" style="background: rgba(255,255,255,0.01);">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="ms-<?= $ms['id'] ?>" <?= $msChecked ? 'checked' : '' ?> onclick="toggleMilestoneStatus(<?= $ms['id'] ?>, <?= $g['id'] ?>, this)">
                                            <label class="form-check-label text-white small <?= $msTextClass ?>" for="ms-<?= $ms['id'] ?>" style="font-size: 0.8rem;">
                                                <?= e($ms['title']) ?>
                                            </label>
                                        </div>
                                        <button class="btn btn-link text-danger p-0 border-0 fs-6" onclick="deleteMilestoneItem(<?= $ms['id'] ?>, <?= $g['id'] ?>, this)">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Add Milestone input -->
                        <div class="input-group input-group-sm mb-3">
                            <input type="text" class="form-control bg-transparent text-white border-secondary" id="new-ms-input-<?= $g['id'] ?>" placeholder="Add key result milestone..." autocomplete="off">
                            <button class="btn btn-outline-secondary text-white border-secondary" onclick="addNewMilestoneItem(<?= $g['id'] ?>)"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>

                    <!-- Progress calculations footer -->
                    <div class="border-top border-color pt-3 mt-2">
                        <div class="d-flex align-items-center justify-content-between small text-muted mb-2">
                            <span>Milestones Progress</span>
                            <span id="goal-progress-label-<?= $g['id'] ?>"><?= (int)$g['progress_percent'] ?>%</span>
                        </div>
                        <div class="progress bg-dark mb-3" style="height: 8px;">
                            <div class="progress-bar bg-warning" id="goal-progress-bar-<?= $g['id'] ?>" role="progressbar" style="width: <?= (int)$g['progress_percent'] ?>%"></div>
                        </div>

                        <?php if ($g['reward']): ?>
                            <div class="p-2 rounded border border-warning border-opacity-25 bg-warning bg-opacity-10 text-warning small">
                                <i class="fa-solid fa-gift me-1"></i> Reward: <?= e($g['reward']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1" aria-labelledby="addGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel text-white p-3" style="background-color: var(--bg-card) !important;">
            <div class="modal-header border-color">
                <h5 class="modal-title fw-bold" id="addGoalModalLabel">Define Goal Target</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= e(APP_URL) ?>/goals" method="POST">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add-g-title" class="form-label small">Goal Title</label>
                        <input type="text" name="title" id="add-g-title" class="form-control bg-transparent text-white border-secondary" placeholder="Acquire Senior Developer cert, buy a home..." required>
                    </div>
                    <div class="mb-3">
                        <label for="add-g-desc" class="form-label small">Description / Focus</label>
                        <textarea name="description" id="add-g-desc" rows="3" class="form-control bg-transparent text-white border-secondary" placeholder="Define boundaries and metrics of achievements..."></textarea>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="add-g-type" class="form-label small">Target Type</label>
                            <select name="type" id="add-g-type" class="form-select bg-transparent text-white border-secondary">
                                <option value="short_term" class="bg-dark" selected>Short Term (Weeks)</option>
                                <option value="quarterly" class="bg-dark">Quarterly (3 Months)</option>
                                <option value="yearly" class="bg-dark">Yearly (12 Months)</option>
                                <option value="vision" class="bg-dark">Vision Board (Long term)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add-g-due" class="form-label small">Deadline</label>
                            <input type="date" name="deadline" id="add-g-due" class="form-control bg-transparent text-white border-secondary">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add-g-reward" class="form-label small">Completion Reward</label>
                        <input type="text" name="reward" id="add-g-reward" class="form-control bg-transparent text-white border-secondary" placeholder="Tattoo, rest trip, keyboard...">
                    </div>
                </div>
                <div class="modal-footer border-color">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">Save Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Goal Modal -->
<div class="modal fade" id="editGoalModal" tabindex="-1" aria-labelledby="editGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel text-white p-3" style="background-color: var(--bg-card) !important;">
            <div class="modal-header border-color">
                <h5 class="modal-title fw-bold" id="editGoalModalLabel">Modify Goal Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= e(APP_URL) ?>/goals" method="POST" id="edit-goal-form">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-g-id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-g-title" class="form-label small">Goal Title</label>
                        <input type="text" name="title" id="edit-g-title" class="form-control bg-transparent text-white border-secondary" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-g-desc" class="form-label small">Description</label>
                        <textarea name="description" id="edit-g-desc" rows="3" class="form-control bg-transparent text-white border-secondary"></textarea>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit-g-type" class="form-label small">Target Type</label>
                            <select name="type" id="edit-g-type" class="form-select bg-transparent text-white border-secondary">
                                <option value="short_term" class="bg-dark">Short Term</option>
                                <option value="quarterly" class="bg-dark">Quarterly</option>
                                <option value="yearly" class="bg-dark">Yearly</option>
                                <option value="vision" class="bg-dark">Vision Board</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-g-status" class="form-label small">Status</label>
                            <select name="status" id="edit-g-status" class="form-select bg-transparent text-white border-secondary">
                                <option value="pending" class="bg-dark">Pending</option>
                                <option value="in_progress" class="bg-dark">In Progress</option>
                                <option value="completed" class="bg-dark">Completed</option>
                                <option value="abandoned" class="bg-dark">Abandoned</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit-g-due" class="form-label small">Deadline</label>
                            <input type="date" name="deadline" id="edit-g-due" class="form-control bg-transparent text-white border-secondary">
                        </div>
                        <div class="col-md-6">
                            <label for="edit-g-reward" class="form-label small">Reward</label>
                            <input type="text" name="reward" id="edit-g-reward" class="form-control bg-transparent text-white border-secondary">
                        </div>
                    </div>
                    
                    <!-- Hidden input to preserve progress when editing -->
                    <input type="hidden" name="progress_percent" id="edit-g-progress">
                    <div class="mb-3">
                        <label for="edit-g-notes" class="form-label small">Planner Notes</label>
                        <textarea name="notes" id="edit-g-notes" rows="2" class="form-control bg-transparent text-white border-secondary"></textarea>
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

<script src="<?= e(APP_URL) ?>/assets/js/goals.js"></script>
