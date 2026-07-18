<?php
/**
 * Tasks Management View Template
 */

declare(strict_types=1);

$csrfTokenStr = generateCsrfToken();
?>
<!-- Filters Bar Panel -->
<div class="card glass-panel mb-4 p-3" data-aos="fade-down">
    <form action="<?= e(APP_URL) ?>/tasks" method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label for="filter-search" class="form-label text-muted small">Global Search</label>
            <input type="text" name="search" id="filter-search" class="form-control bg-transparent text-white border-secondary small" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search keywords...">
        </div>
        
        <div class="col-md-2">
            <label for="filter-category" class="form-label text-muted small">Category</label>
            <select name="category_id" id="filter-category" class="form-select bg-transparent text-white border-secondary small">
                <option value="" class="bg-dark">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" class="bg-dark" <?= (isset($filters['category_id']) && $filters['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filter-priority" class="form-label text-muted small">Priority</label>
            <select name="priority" id="filter-priority" class="form-select bg-transparent text-white border-secondary small">
                <option value="" class="bg-dark">All Priorities</option>
                <option value="low" class="bg-dark" <?= ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" class="bg-dark" <?= ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" class="bg-dark" <?= ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                <option value="critical" class="bg-dark" <?= ($filters['priority'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filter-status" class="form-label text-muted small">Status</label>
            <select name="status" id="filter-status" class="form-select bg-transparent text-white border-secondary small">
                <option value="" class="bg-dark">All Statuses</option>
                <option value="pending" class="bg-dark" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" class="bg-dark" <?= ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" class="bg-dark" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="missed" class="bg-dark" <?= ($filters['status'] ?? '') === 'missed' ? 'selected' : '' ?>>Missed</option>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-accent w-50 small"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <a href="<?= e(APP_URL) ?>/tasks" class="btn btn-outline-secondary w-50 small"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
        </div>
    </form>
</div>

<!-- Grid Layout Content -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-white fw-bold mb-0">Task Board Directory</h4>
    <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addTaskModal">
        <i class="fa-solid fa-plus me-1"></i> Create Task
    </button>
</div>

<div class="row g-4" id="tasks-board-grid">
    <?php if (empty($tasks)): ?>
        <div class="col-12 text-center py-5" data-aos="zoom-in">
            <i class="fa-solid fa-paste text-muted display-4 mb-3"></i>
            <h5 class="text-white">No tasks matched your criteria</h5>
            <p class="text-muted small">Try adjusting your filters or create a new task to get started.</p>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): 
            $cardBorder = $task['priority'] === 'critical' ? 'border-danger' : '';
            $statusColors = [
                'pending' => 'bg-secondary',
                'in_progress' => 'bg-warning text-dark',
                'completed' => 'bg-success',
                'missed' => 'bg-danger',
                'cancelled' => 'bg-dark'
            ];
            $statusLabel = $statusColors[$task['status']] ?? 'bg-secondary';
        ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" id="task-card-<?= $task['id'] ?>">
                <div class="card glass-panel h-100 p-3 d-flex flex-column justify-content-between <?= $cardBorder ?>">
                    <!-- Card Header -->
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge <?= $statusLabel ?> text-uppercase small"><?= e($task['status']) ?></span>
                            <div class="dropdown">
                                <button class="btn btn-link text-muted p-0 border-0" type="button" id="taskOptDropdown<?= $task['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical fs-5"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end glass-panel" aria-labelledby="taskOptDropdown<?= $task['id'] ?>">
                                    <li><button class="dropdown-item text-white small" onclick="openEditTaskModal(<?= $task['id'] ?>)"><i class="fa-regular fa-pen-to-square me-2"></i> Edit</button></li>
                                    <li>
                                        <form action="<?= e(APP_URL) ?>/tasks" method="POST" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                            <button type="submit" class="dropdown-item text-danger small"><i class="fa-regular fa-trash-can me-2"></i> Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <h5 class="text-white fw-bold mb-2">
                            <?= $task['emoji'] ? e($task['emoji']) . ' ' : '' ?>
                            <?= e($task['title']) ?>
                        </h5>
                        <p class="text-muted small mb-3 text-truncate-3" style="line-height: 1.5;"><?= e($task['description']) ?></p>

                        <!-- Tags, Categories & Difficulty Row -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge bg-secondary" style="background-color: <?= $task['category_color'] ?: '#64748b' ?> !important;">
                                <i class="fa-solid <?= $task['category_icon'] ?: 'fa-tag' ?> me-1"></i> <?= e($task['category_name'] ?: 'General') ?>
                            </span>
                            <span class="badge bg-dark border border-secondary text-muted">
                                <i class="fa-solid fa-puzzle-piece me-1"></i> <?= e($task['difficulty']) ?>
                            </span>
                            <?php if ($task['due_date']): ?>
                                <span class="badge bg-dark border border-secondary text-warning">
                                    <i class="fa-regular fa-clock me-1"></i> <?= date('d M, h:i A', strtotime($task['due_date'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Progress Checklist -->
                    <div class="border-top border-color pt-3 mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-1 text-muted small">
                            <span>Checklist Completeness</span>
                            <span id="task-progress-label-<?= $task['id'] ?>"><?= (int)$task['progress_percent'] ?>%</span>
                        </div>
                        <div class="progress bg-dark mb-3" style="height: 6px;">
                            <div class="progress-bar bg-primary" id="task-progress-bar-<?= $task['id'] ?>" role="progressbar" style="width: <?= (int)$task['progress_percent'] ?>%"></div>
                        </div>

                        <!-- Mini checklist checklist items wrapper -->
                        <div class="subtask-wrapper mb-3" id="subtask-container-<?= $task['id'] ?>">
                            <!-- Checklist rows loaded via JS / ajax dynamic checkins -->
                        </div>

                        <!-- Quick Checklist item add field -->
                        <div class="input-group input-group-sm mb-2">
                            <input type="text" class="form-control bg-transparent text-white border-secondary" id="new-subtask-input-<?= $task['id'] ?>" placeholder="Add subtask item..." autocomplete="off">
                            <button class="btn btn-outline-secondary text-white border-secondary" onclick="addNewSubtaskItem(<?= $task['id'] ?>)"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- 6. Full Modals for Add & Edit Task Actions -->
<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-panel text-white p-3" style="background-color: var(--bg-card) !important;">
            <div class="modal-header border-color">
                <h5 class="modal-title fw-bold" id="addTaskModalLabel">Create Detailed Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= e(APP_URL) ?>/tasks" method="POST" enctype="multipart/form-data">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-9">
                            <label for="add-title" class="form-label small">Task Title</label>
                            <input type="text" name="title" id="add-title" class="form-control bg-transparent text-white border-secondary" placeholder="Read book chapter, study Spring Boot..." required>
                        </div>
                        <div class="col-md-3">
                            <label for="add-emoji" class="form-label small">Emoji / Icon</label>
                            <input type="text" name="emoji" id="add-emoji" class="form-control bg-transparent text-white border-secondary text-center" placeholder="🎯">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add-desc" class="form-label small">Description</label>
                        <textarea name="description" id="add-desc" rows="3" class="form-control bg-transparent text-white border-secondary" placeholder="Provide notes or steps details..."></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="add-category" class="form-label small">Category</label>
                            <select name="category_id" id="add-category" class="form-select bg-transparent text-white border-secondary">
                                <option value="" class="bg-dark">General</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" class="bg-dark"><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="add-priority" class="form-label small">Priority</label>
                            <select name="priority" id="add-priority" class="form-select bg-transparent text-white border-secondary">
                                <option value="low" class="bg-dark">Low</option>
                                <option value="medium" class="bg-dark" selected>Medium</option>
                                <option value="high" class="bg-dark">High</option>
                                <option value="critical" class="bg-dark">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="add-difficulty" class="form-label small">Difficulty</label>
                            <select name="difficulty" id="add-difficulty" class="form-select bg-transparent text-white border-secondary">
                                <option value="easy" class="bg-dark">Easy</option>
                                <option value="medium" class="bg-dark" selected>Medium</option>
                                <option value="hard" class="bg-dark">Hard</option>
                                <option value="expert" class="bg-dark">Expert</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="add-due" class="form-label small">Due Date & Time</label>
                            <input type="datetime-local" name="due_date" id="add-due" class="form-control bg-transparent text-white border-secondary">
                        </div>
                        <div class="col-md-6">
                            <label for="add-reminder" class="form-label small">Reminder Time</label>
                            <input type="datetime-local" name="reminder_time" id="add-reminder" class="form-control bg-transparent text-white border-secondary">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="add-est" class="form-label small">Est. Time (Mins)</label>
                            <input type="number" name="estimated_time" id="add-est" class="form-control bg-transparent text-white border-secondary" placeholder="60">
                        </div>
                        <div class="col-md-4">
                            <label for="add-location" class="form-label small">Location</label>
                            <input type="text" name="location" id="add-location" class="form-control bg-transparent text-white border-secondary" placeholder="Room 21, Online">
                        </div>
                        <div class="col-md-4">
                            <label for="add-repeat" class="form-label small">Recurrence Type</label>
                            <select name="repeat_type" id="add-repeat" class="form-select bg-transparent text-white border-secondary">
                                <option value="none" class="bg-dark" selected>None</option>
                                <option value="daily" class="bg-dark">Daily</option>
                                <option value="weekdays" class="bg-dark">Weekdays</option>
                                <option value="weekends" class="bg-dark">Weekends</option>
                                <option value="weekly" class="bg-dark">Weekly</option>
                                <option value="monthly" class="bg-dark">Monthly</option>
                                <option value="yearly" class="bg-dark">Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add-file" class="form-label small">Attach Document (Max 5MB)</label>
                        <input type="file" name="attachment" id="add-file" class="form-control bg-transparent text-white border-secondary">
                    </div>
                </div>
                <div class="modal-footer border-color">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-panel text-white p-3" style="background-color: var(--bg-card) !important;">
            <div class="modal-header border-color">
                <h5 class="modal-title fw-bold" id="editTaskModalLabel">Modify Task Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= e(APP_URL) ?>/tasks" method="POST" enctype="multipart/form-data" id="edit-task-form">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-9">
                            <label for="edit-title" class="form-label small">Task Title</label>
                            <input type="text" name="title" id="edit-title" class="form-control bg-transparent text-white border-secondary" required>
                        </div>
                        <div class="col-md-3">
                            <label for="edit-emoji" class="form-label small">Emoji</label>
                            <input type="text" name="emoji" id="edit-emoji" class="form-control bg-transparent text-white border-secondary text-center">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-desc" class="form-label small">Description</label>
                        <textarea name="description" id="edit-desc" rows="3" class="form-control bg-transparent text-white border-secondary"></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="edit-category" class="form-label small">Category</label>
                            <select name="category_id" id="edit-category" class="form-select bg-transparent text-white border-secondary">
                                <option value="" class="bg-dark">General</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" class="bg-dark"><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit-priority" class="form-label small">Priority</label>
                            <select name="priority" id="edit-priority" class="form-select bg-transparent text-white border-secondary">
                                <option value="low" class="bg-dark">Low</option>
                                <option value="medium" class="bg-dark">Medium</option>
                                <option value="high" class="bg-dark">High</option>
                                <option value="critical" class="bg-dark">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit-status" class="form-label small">Status</label>
                            <select name="status" id="edit-status" class="form-select bg-transparent text-white border-secondary">
                                <option value="pending" class="bg-dark">Pending</option>
                                <option value="in_progress" class="bg-dark">In Progress</option>
                                <option value="completed" class="bg-dark">Completed</option>
                                <option value="missed" class="bg-dark">Missed</option>
                                <option value="cancelled" class="bg-dark">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit-due" class="form-label small">Due Date</label>
                            <input type="datetime-local" name="due_date" id="edit-due" class="form-control bg-transparent text-white border-secondary">
                        </div>
                        <div class="col-md-6">
                            <label for="edit-reminder" class="form-label small">Reminder</label>
                            <input type="datetime-local" name="reminder_time" id="edit-reminder" class="form-control bg-transparent text-white border-secondary">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="edit-est" class="form-label small">Est. Time (Mins)</label>
                            <input type="number" name="estimated_time" id="edit-est" class="form-control bg-transparent text-white border-secondary">
                        </div>
                        <div class="col-md-4">
                            <label for="edit-act" class="form-label small">Actual Time (Mins)</label>
                            <input type="number" name="actual_time" id="edit-act" class="form-control bg-transparent text-white border-secondary">
                        </div>
                        <div class="col-md-4">
                            <label for="edit-difficulty" class="form-label small">Difficulty</label>
                            <select name="difficulty" id="edit-difficulty" class="form-select bg-transparent text-white border-secondary">
                                <option value="easy" class="bg-dark">Easy</option>
                                <option value="medium" class="bg-dark">Medium</option>
                                <option value="hard" class="bg-dark">Hard</option>
                                <option value="expert" class="bg-dark">Expert</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit-location" class="form-label small">Location</label>
                            <input type="text" name="location" id="edit-location" class="form-control bg-transparent text-white border-secondary">
                        </div>
                        <div class="col-md-6">
                            <label for="edit-repeat" class="form-label small">Repeat Type</label>
                            <select name="repeat_type" id="edit-repeat" class="form-select bg-transparent text-white border-secondary">
                                <option value="none" class="bg-dark">None</option>
                                <option value="daily" class="bg-dark">Daily</option>
                                <option value="weekdays" class="bg-dark">Weekdays</option>
                                <option value="weekends" class="bg-dark">Weekends</option>
                                <option value="weekly" class="bg-dark">Weekly</option>
                                <option value="monthly" class="bg-dark">Monthly</option>
                                <option value="yearly" class="bg-dark">Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-file" class="form-label small">Upload New Attachment</label>
                        <input type="file" name="attachment" id="edit-file" class="form-control bg-transparent text-white border-secondary">
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

<!-- Load Task Manager Custom script -->
<script>
    // Embed inline task mappings for initial loader
    const TASKS_LIST_IDS = <?= json_encode(array_column($tasks, 'id')) ?>;
</script>
<script src="<?= e(APP_URL) ?>/assets/js/tasks.js"></script>
