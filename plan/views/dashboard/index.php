<?php
/**
 * Core Workspace Dashboard View Template
 */

declare(strict_types=1);

$name = $_SESSION['user_name'] ?? 'Alex';
?>
<!-- 1. Header Greeting Panel -->
<div class="row g-4 mb-4">
    <div class="col-lg-8" data-aos="fade-right">
        <div class="card glass-panel h-100 p-4 border-0 position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(236, 72, 153, 0.12) 100%), var(--bg-card);">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge bg-primary mb-2 px-3 py-2 fw-semibold rounded-pill">WORKSPACE INSIGHTS</span>
                    <h2 class="text-white fw-bold mb-2"><?= e($greeting) ?>, <?= e(explode(' ', $name)[0]) ?>!</h2>
                    <p class="text-muted small mb-3"><i class="fa-regular fa-clock me-1"></i> Local Server Time: <span id="dashboard-clock" class="text-white fw-medium"><?= date('h:i:s A') ?></span></p>
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center text-success fw-bold">
                            <i class="fa-solid fa-fire me-1 fs-5"></i> <span><?= (int)($habitStats['current_streak'] ?? 0) ?> Day Streak</span>
                        </div>
                        <div class="vr bg-secondary" style="height: 20px;"></div>
                        <div class="text-muted small">
                            <span class="text-white fw-semibold"><?= (int)($scores['overall_score'] ?? 70) ?>%</span> Productivity Score
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center mt-3 mt-md-0 position-relative">
                    <!-- Dynamic Weather widget stub -->
                    <div class="glass-panel p-3 d-inline-block text-center border-color-glow" style="min-width: 140px; background: rgba(0,0,0,0.15);">
                        <i class="fa-solid <?= e($weatherIcon) ?> text-warning fs-1 mb-2"></i>
                        <h4 class="text-white fw-bold mb-0"><?= (int)$weatherTemp ?>°C</h4>
                        <span class="text-muted small"><?= e($weatherDesc) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 2. Motivational quote block -->
    <div class="col-lg-4" data-aos="fade-left">
        <div class="card glass-panel h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <i class="fa-solid fa-quote-left text-primary fs-2 mb-2"></i>
                <p class="text-white fw-medium mb-3" style="font-size: 1.05rem; line-height: 1.6;">
                    "<?= e($quote['quote']) ?>"
                </p>
            </div>
            <h6 class="text-muted mb-0">— <?= e($quote['author']) ?></h6>
        </div>
    </div>
</div>

<!-- 3. Key Stat Indicators row -->
<div class="row g-4 mb-4" data-aos="fade-up">
    <!-- Productivity Circular Ring -->
    <div class="col-xl-3 col-sm-6">
        <div class="card glass-panel text-center p-3">
            <h6 class="text-muted small mb-3">Productivity Score</h6>
            <div class="d-inline-flex position-relative justify-content-center align-items-center mb-2">
                <svg class="progress-ring" width="100" height="100">
                    <circle class="progress-ring-track" stroke="rgba(255,255,255,0.05)" stroke-width="8" fill="transparent" r="42" cx="50" cy="50"/>
                    <circle class="progress-ring-circle" stroke="var(--clr-accent)" stroke-width="8" stroke-dasharray="263.89" stroke-dashoffset="<?= 263.89 - (263.89 * ($scores['overall_score'] ?? 70) / 100) ?>" fill="transparent" r="42" cx="50" cy="50"/>
                </svg>
                <div class="position-absolute text-white fw-bold fs-4"><?= (int)($scores['overall_score'] ?? 70) ?>%</div>
            </div>
            <p class="text-muted small mb-0">Calculated composite index</p>
        </div>
    </div>
    
    <!-- Habits Radial Ring -->
    <div class="col-xl-3 col-sm-6">
        <div class="card glass-panel text-center p-3">
            <h6 class="text-muted small mb-3">Today's Habits %</h6>
            <div class="d-inline-flex position-relative justify-content-center align-items-center mb-2">
                <svg class="progress-ring" width="100" height="100">
                    <circle class="progress-ring-track" stroke="rgba(255,255,255,0.05)" stroke-width="8" fill="transparent" r="42" cx="50" cy="50"/>
                    <circle class="progress-ring-circle" stroke="#10b981" stroke-width="8" stroke-dasharray="263.89" stroke-dashoffset="<?= 263.89 - (263.89 * ($habitStats['today_completion_percent'] ?? 0) / 100) ?>" fill="transparent" r="42" cx="50" cy="50"/>
                </svg>
                <div class="position-absolute text-white fw-bold fs-4"><?= (int)($habitStats['today_completion_percent'] ?? 0) ?>%</div>
            </div>
            <p class="text-muted small mb-0"><?= (int)($habitStats['current_streak'] ?? 0) ?> days streak active</p>
        </div>
    </div>

    <!-- Pomodoro Focus Hours Gauge -->
    <div class="col-xl-3 col-sm-6">
        <div class="card glass-panel p-3 d-flex flex-column justify-content-between text-center">
            <h6 class="text-muted small mb-2">Today's Focus Time</h6>
            <div>
                <h1 class="display-5 text-white fw-extrabold mb-1"><?= (int)$todayFocusMinutes ?></h1>
                <span class="text-muted small">minutes focused today</span>
            </div>
            <div class="progress bg-dark mt-3" style="height: 6px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= min(100, ($todayFocusMinutes / 120) * 100) ?>%"></div>
            </div>
            <span class="text-muted small mt-2">Target: 120 mins</span>
        </div>
    </div>
    
    <!-- Longest Pending Task Warning -->
    <div class="col-xl-3 col-sm-6">
        <div class="card glass-panel p-3 d-flex flex-column justify-content-between text-center border-danger">
            <h6 class="text-muted small mb-2">Longest Pending Task</h6>
            <?php if ($longestTask): ?>
                <div>
                    <h5 class="text-white text-truncate mb-1"><?= e($longestTask['title']) ?></h5>
                    <span class="text-danger fw-semibold small"><?= (int)$longestTask['running_days'] ?> days active</span>
                </div>
                <p class="text-muted small mb-0 mt-2">Created: <?= date('d M Y', strtotime($longestTask['created_at'])) ?></p>
            <?php else: ?>
                <div>
                    <h5 class="text-white mb-1">Clean slate!</h5>
                    <span class="text-success small">No pending backlogs</span>
                </div>
                <p class="text-muted small mb-0 mt-2">Good job keeping tasks clear.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 4. Dashboard Main Body Layout Grid -->
<div class="row g-4 mb-4">
    <!-- Tasks Lists Section -->
    <div class="col-lg-6" data-aos="fade-right">
        <div class="card glass-panel h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white fw-bold mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i>Today's Agenda</h5>
                    <button class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#quickAddTaskModal">
                        <i class="fa-solid fa-plus"></i> Add Task
                    </button>
                </div>
                
                <div class="list-group list-group-flush" id="dashboard-tasks-list">
                    <?php if (empty($todayTasks)): ?>
                        <div class="text-center py-4">
                            <i class="fa-regular fa-calendar-check text-muted fs-1 mb-2"></i>
                            <p class="text-muted mb-0">No tasks due today. Create a quick task or take a rest!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todayTasks as $task): ?>
                            <div class="list-group-item bg-transparent text-white border-color px-0 py-3 d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <button class="btn btn-outline-secondary rounded-circle btn-sm p-1 d-flex align-items-center justify-content-center" onclick="completeTaskDirect(<?= $task['id'] ?>, this)" style="width: 22px; height: 22px;">
                                        <i class="fa-solid fa-check fs-6 opacity-0"></i>
                                    </button>
                                    <div>
                                        <h6 class="mb-1 text-white fw-semibold"><?= e($task['title']) ?></h6>
                                        <span class="badge bg-secondary" style="font-size: 0.75rem; background-color: <?= $task['category_color'] ?: '#6c757d' ?> !important;"><?= e($task['category_name'] ?: 'General') ?></span>
                                        <?php if ($task['priority'] === 'critical' || $task['priority'] === 'high'): ?>
                                            <span class="badge bg-danger text-uppercase" style="font-size: 0.7rem;"><?= e($task['priority']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="text-muted small"><?= date('h:i A', strtotime($task['due_date'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="border-top border-color pt-3 mt-3">
                <a href="<?= e(APP_URL) ?>/tasks" class="text-decoration-none text-primary fw-semibold small">Manage Tasks Directory <i class="fa-solid fa-chevron-right ms-1"></i></a>
            </div>
        </div>
    </div>
    
    <!-- Deadlines and Streak check-in column -->
    <div class="col-lg-6" data-aos="fade-left">
        <div class="card glass-panel h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <h5 class="text-white fw-bold mb-3"><i class="fa-solid fa-circle-exclamation me-2 text-danger"></i>Upcoming Deadlines</h5>
                
                <div class="list-group list-group-flush">
                    <?php if (empty($deadlines)): ?>
                        <div class="text-center py-4">
                            <i class="fa-regular fa-circle-check text-muted fs-1 mb-2"></i>
                            <p class="text-muted mb-0">No critical deadlines in the next 48 hours.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($deadlines as $dl): ?>
                            <div class="list-group-item bg-transparent text-white border-color px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 text-white fw-medium"><?= e($dl['title']) ?></h6>
                                    <span class="text-muted small">Due in <?= round((strtotime($dl['due_date']) - time()) / 3600) ?> hours</span>
                                </div>
                                <span class="badge bg-danger text-uppercase"><?= e($dl['priority']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="border-top border-color pt-3 mt-4">
                <h6 class="text-white fw-bold mb-3"><i class="fa-solid fa-calendar-check text-success me-2"></i>Daily Habits Checklist</h6>
                <div class="d-flex flex-wrap gap-3">
                    <?php if (empty($habits)): ?>
                        <p class="text-muted small mb-0">No habits tracked. Start tracking from the Habits tab.</p>
                    <?php else: ?>
                        <?php foreach ($habits as $habit): 
                            $loggedToday = isset($todayLogsMap[$habit['id']]) && $todayLogsMap[$habit['id']] === 'completed';
                        ?>
                            <div class="d-flex align-items-center gap-2 p-2 rounded glass-panel" style="background: rgba(255,255,255,0.02);">
                                <button class="habit-check-btn <?= $loggedToday ? 'checked' : '' ?>" 
                                        onclick="toggleHabitDirect(<?= $habit['id'] ?>, this)"
                                        style="width:30px; height:30px; border-color: <?= e($habit['color']) ?>; background: <?= $loggedToday ? e($habit['color']) : 'transparent' ?>;">
                                    <i class="fa-solid fa-check text-white fs-6 <?= $loggedToday ? '' : 'd-none' ?>"></i>
                                </button>
                                <span class="text-white small fw-medium"><?= e($habit['name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. Bottom Quick Widgets Row: Goals, Note additions, Recent Completed feed -->
<div class="row g-4 mb-4">
    <!-- Active Goal card -->
    <div class="col-md-4" data-aos="fade-right">
        <div class="card glass-panel p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="text-white fw-bold mb-3"><i class="fa-solid fa-bullseye text-warning me-2"></i>Current Goal Target</h5>
                <?php if ($currentGoal): ?>
                    <h6 class="text-white fw-semibold mb-2"><?= e($currentGoal['title']) ?></h6>
                    <p class="text-muted small mb-3"><?= e($currentGoal['description']) ?></p>
                    
                    <div class="d-flex align-items-center justify-content-between small text-white mb-2">
                        <span>Milestones progress</span>
                        <span><?= (int)$currentGoal['progress_percent'] ?>%</span>
                    </div>
                    <div class="progress bg-dark" style="height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= (int)$currentGoal['progress_percent'] ?>%"></div>
                    </div>
                    <?php if ($currentGoal['reward']): ?>
                        <div class="mt-3 p-2 rounded border border-warning border-opacity-25 bg-warning bg-opacity-10 text-warning small">
                            <i class="fa-solid fa-gift me-1"></i> Reward: <?= e($currentGoal['reward']) ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-trophy fs-1 mb-2"></i>
                        <p class="small mb-0">No active goals found. Set up a short-term focus target!</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="border-top border-color pt-3 mt-3">
                <a href="<?= e(APP_URL) ?>/goals" class="text-decoration-none text-primary small fw-semibold">View Goals planner <i class="fa-solid fa-chevron-right ms-1"></i></a>
            </div>
        </div>
    </div>

    <!-- Quick Notes capture form -->
    <div class="col-md-4" data-aos="fade-up">
        <div class="card glass-panel p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="text-white fw-bold mb-3"><i class="fa-regular fa-note-sticky text-info me-2"></i>Quick Note</h5>
                <form action="<?= e(APP_URL) ?>/api/notes" method="POST" id="quick-note-form">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="action" value="quick_add">
                    <div class="mb-3">
                        <input type="text" name="title" class="form-control bg-transparent text-white border-secondary small" placeholder="Note Title" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="content" rows="4" class="form-control bg-transparent text-white border-secondary small" placeholder="Write down draft thoughts or copy links here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-accent w-100">Save draft note</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Recently Completed task feed -->
    <div class="col-md-4" data-aos="fade-left">
        <div class="card glass-panel p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="text-white fw-bold mb-3"><i class="fa-regular fa-circle-check text-success me-2"></i>Recently Completed</h5>
                <div class="list-group list-group-flush">
                    <?php if (empty($recentCompleted)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fa-solid fa-list-check fs-1 mb-2"></i>
                            <p class="small mb-0">No tasks completed in the last 24 hours.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentCompleted as $rc): ?>
                            <div class="list-group-item bg-transparent text-white border-color px-0 py-2 d-flex justify-content-between align-items-center">
                                <div class="text-truncate" style="max-width: 70%;">
                                    <h6 class="mb-0 text-white small fw-semibold text-decoration-line-through text-muted"><?= e($rc['title']) ?></h6>
                                    <span class="text-muted" style="font-size: 0.75rem;">Done at <?= date('h:i A', strtotime($rc['completed_at'])) ?></span>
                                </div>
                                <span class="badge bg-success small" style="font-size: 0.65rem;">DONE</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Task Add Modal -->
<div class="modal fade" id="quickAddTaskModal" tabindex="-1" aria-labelledby="quickAddTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel text-white p-3" style="background-color: var(--bg-card) !important;">
            <div class="modal-header border-color">
                <h5 class="modal-title fw-bold" id="quickAddTaskModalLabel">Create New Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= e(APP_URL) ?>/tasks" method="POST">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="task-title" class="form-label small">Task Title</label>
                        <input type="text" name="title" id="task-title" class="form-control bg-transparent text-white border-secondary" placeholder="What needs to be done?" required>
                    </div>
                    <div class="mb-3">
                        <label for="task-desc" class="form-label small">Description</label>
                        <textarea name="description" id="task-desc" rows="2" class="form-control bg-transparent text-white border-secondary" placeholder="Optional details..."></textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="task-category" class="form-label small">Category</label>
                            <select name="category_id" id="task-category" class="form-select bg-transparent text-white border-secondary">
                                <option value="" class="bg-dark">General</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" class="bg-dark"><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="task-priority" class="form-label small">Priority</label>
                            <select name="priority" id="task-priority" class="form-select bg-transparent text-white border-secondary">
                                <option value="low" class="bg-dark">Low</option>
                                <option value="medium" class="bg-dark" selected>Medium</option>
                                <option value="high" class="bg-dark">High</option>
                                <option value="critical" class="bg-dark">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="task-due" class="form-label small">Due Date & Time</label>
                        <input type="datetime-local" name="due_date" id="task-due" class="form-control bg-transparent text-white border-secondary" value="<?= date('Y-m-d\T18:00') ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-color">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
