<?php
/**
 * Pomodoro Focus Space View Template
 */

declare(strict_types=1);

$csrfTokenStr = generateCsrfToken();
?>
<div class="row g-4 justify-content-center">
    <!-- 1. Central Big Timer Widget -->
    <div class="col-lg-6 col-md-8 text-center" data-aos="zoom-in">
        <div class="card glass-panel p-5 border-color-glow position-relative overflow-hidden" style="background: radial-gradient(circle at center, rgba(var(--accent-hue), 90%, 65%, 0.08) 0%, transparent 70%), var(--bg-card);">
            <!-- Mode selectors -->
            <div class="d-flex justify-content-center gap-2 mb-4">
                <button class="btn btn-sm btn-outline-secondary px-3 active" id="mode-focus" onclick="setTimerMode('focus', 25)">Focus</button>
                <button class="btn btn-sm btn-outline-secondary px-3" id="mode-short" onclick="setTimerMode('short_break', 5)">Short Break</button>
                <button class="btn btn-sm btn-outline-secondary px-3" id="mode-long" onclick="setTimerMode('long_break', 15)">Long Break</button>
            </div>
            
            <!-- Timer Clock numbers -->
            <div class="my-4">
                <h1 class="display-1 fw-extrabold text-white font-monospace" id="timer-display" style="font-size: 6rem; letter-spacing: 2px;">25:00</h1>
            </div>

            <!-- Task binding selection dropdown -->
            <div class="mb-4 text-start">
                <label for="link-task-select" class="form-label text-muted small"><i class="fa-solid fa-link me-1"></i> Associate with pending task</label>
                <select id="link-task-select" class="form-select bg-transparent text-white border-secondary small">
                    <option value="" class="bg-dark" selected>General Focus (No specific task)</option>
                    <?php foreach ($activeTasks as $task): ?>
                        <option value="<?= $task['id'] ?>" class="bg-dark"><?= e($task['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action buttons -->
            <div class="d-flex justify-content-center gap-3">
                <button class="btn btn-lg btn-accent px-4" id="timer-start-btn">Start Session</button>
                <button class="btn btn-lg btn-outline-secondary px-4 d-none" id="timer-pause-btn">Pause</button>
                <button class="btn btn-lg btn-outline-danger px-4" id="timer-reset-btn">Reset</button>
            </div>

            <!-- Auto-start checklist option -->
            <div class="form-check justify-content-center d-flex mt-4 gap-2 align-items-center">
                <input type="checkbox" id="auto-start-next" class="form-check-input">
                <label class="form-check-label text-muted small" for="auto-start-next">Auto-start breaks & focus blocks</label>
            </div>
        </div>
    </div>

    <!-- 2. Daily focus stats & history sidebar -->
    <div class="col-lg-4 col-md-8" data-aos="fade-left">
        <div class="card glass-panel h-100 p-4 d-flex flex-column justify-content-between">
            <div>
                <h5 class="text-white fw-bold mb-3"><i class="fa-solid fa-chart-column text-primary me-2"></i>Daily Focus Metrics</h5>
                <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded bg-dark bg-opacity-20 border border-secondary">
                    <div>
                        <span class="text-muted small d-block">Today's Focus Time</span>
                        <h3 class="text-white fw-extrabold mb-0" id="total-focus-minutes-display"><?= (int)$todayFocusMinutes ?> mins</h3>
                    </div>
                    <i class="fa-solid fa-clock-pulse text-warning display-6"></i>
                </div>

                <h6 class="text-white fw-bold small mb-2">Focus Logs History</h6>
                <div class="list-group list-group-flush" id="pomodoro-logs-list">
                    <?php if (empty($recentLogs)): ?>
                        <p class="text-muted small text-center my-3">No focus logs saved for today.</p>
                    <?php else: ?>
                        <?php foreach ($recentLogs as $log): 
                            $iconColor = $log['type'] === 'focus' ? 'text-warning' : 'text-success';
                            $typeLabel = $log['type'] === 'focus' ? 'Focus Block' : 'Break Block';
                        ?>
                            <div class="list-group-item bg-transparent text-white border-color px-0 py-2 d-flex justify-content-between align-items-center small">
                                <div class="text-truncate" style="max-width:70%;">
                                    <h6 class="mb-0 text-white small fw-medium"><i class="fa-regular fa-circle-dot me-2 <?= $iconColor ?>"></i> <?= e($log['task_title'] ?: $typeLabel) ?></h6>
                                    <span class="text-muted" style="font-size:0.75rem;"><?= date('h:i A', strtotime($log['created_at'])) ?></span>
                                </div>
                                <span class="badge bg-secondary"><?= (int)$log['duration_minutes'] ?>m</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= e(APP_URL) ?>/assets/js/pomodoro.js"></script>
