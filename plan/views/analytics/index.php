<?php
/**
 * Analytics Dashboard View Template
 */

declare(strict_types=1);
?>
<!-- 1. Score Gauge cards row with loading skeletons -->
<div class="row g-4 mb-4" id="analytics-scores-row">
    <!-- Productivity Gauge -->
    <div class="col-lg-3 col-sm-6">
        <div class="card glass-panel text-center p-3">
            <h6 class="text-muted small mb-3">Productivity Score</h6>
            <div class="d-inline-flex position-relative justify-content-center align-items-center mb-2">
                <canvas id="gauge-productivity" width="100" height="100" class="skeleton rounded-circle" style="width:100px; height:100px;"></canvas>
                <div class="position-absolute text-white fw-bold fs-4 d-none" id="lbl-score-productivity">--%</div>
            </div>
            <p class="text-muted small mb-0">Goal & Task weight average</p>
        </div>
    </div>
    
    <!-- Consistency Gauge -->
    <div class="col-lg-3 col-sm-6">
        <div class="card glass-panel text-center p-3">
            <h6 class="text-muted small mb-3">Daily Consistency</h6>
            <div class="d-inline-flex position-relative justify-content-center align-items-center mb-2">
                <canvas id="gauge-consistency" width="100" height="100" class="skeleton rounded-circle" style="width:100px; height:100px;"></canvas>
                <div class="position-absolute text-white fw-bold fs-4 d-none" id="lbl-score-consistency">--%</div>
            </div>
            <p class="text-muted small mb-0">Variance in daily actions</p>
        </div>
    </div>

    <!-- Focus Gauge -->
    <div class="col-lg-3 col-sm-6">
        <div class="card glass-panel text-center p-3">
            <h6 class="text-muted small mb-3">Focus Score</h6>
            <div class="d-inline-flex position-relative justify-content-center align-items-center mb-2">
                <canvas id="gauge-focus" width="100" height="100" class="skeleton rounded-circle" style="width:100px; height:100px;"></canvas>
                <div class="position-absolute text-white fw-bold fs-4 d-none" id="lbl-score-focus">--%</div>
            </div>
            <p class="text-muted small mb-0">Pomodoro focus vs target</p>
        </div>
    </div>

    <!-- Health Gauge -->
    <div class="col-lg-3 col-sm-6">
        <div class="card glass-panel text-center p-3">
            <h6 class="text-muted small mb-3">Health Score</h6>
            <div class="d-inline-flex position-relative justify-content-center align-items-center mb-2">
                <canvas id="gauge-health" width="100" height="100" class="skeleton rounded-circle" style="width:100px; height:100px;"></canvas>
                <div class="position-absolute text-white fw-bold fs-4 d-none" id="lbl-score-health">--%</div>
            </div>
            <p class="text-muted small mb-0">Health habits completion rate</p>
        </div>
    </div>
</div>

<!-- 2. Main graph canvases row -->
<div class="row g-4 mb-4">
    <!-- Productivity trend rolling line graph -->
    <div class="col-lg-8">
        <div class="card glass-panel p-4 h-100">
            <h5 class="text-white fw-bold mb-3"><i class="fa-solid fa-chart-line text-primary me-2"></i>Productivity Trend & 7d Rolling Average</h5>
            <div style="height: 320px;">
                <canvas id="chart-productivity-trends" class="w-100 h-100 skeleton"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Category & Priority distributions -->
    <div class="col-lg-4">
        <div class="card glass-panel p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <h5 class="text-white fw-bold mb-3"><i class="fa-solid fa-pie-chart text-success me-2"></i>Category Distribution</h5>
                <div style="height: 200px;" class="position-relative">
                    <canvas id="chart-category-donut" class="w-100 h-100 skeleton"></canvas>
                </div>
            </div>
            
            <div class="border-top border-color pt-3 mt-3 d-flex justify-content-between text-center small text-muted">
                <div>
                    <span class="d-block" id="lbl-tasks-total">--</span>
                    Total Tasks
                </div>
                <div>
                    <span class="d-block text-success" id="lbl-tasks-done">--</span>
                    Completed
                </div>
                <div>
                    <span class="d-block text-danger" id="lbl-tasks-missed">--</span>
                    Missed / Overdue
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Focus Hours trends Area Chart -->
    <div class="col-md-6">
        <div class="card glass-panel p-4 h-100">
            <h5 class="text-white fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>Focus Hours Daily Trend</h5>
            <div style="height: 250px;">
                <canvas id="chart-focus-area" class="w-100 h-100 skeleton"></canvas>
            </div>
        </div>
    </div>

    <!-- Deadline performance chart -->
    <div class="col-md-6">
        <div class="card glass-panel p-4 h-100">
            <h5 class="text-white fw-bold mb-3"><i class="fa-solid fa-list-check text-info me-2"></i>Deadline Performance</h5>
            <div style="height: 250px;">
                <canvas id="chart-deadline-performance" class="w-100 h-100 skeleton"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 3. GitHub style Heatmap card -->
<div class="row g-4 mb-4">
    <div class="col-12" data-aos="fade-up">
        <div class="card glass-panel p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h5 class="text-white fw-bold mb-0"><i class="fa-solid fa-cubes text-success me-2"></i>Consistency grid (Activity Contribution Map)</h5>
                <span class="text-muted small">Daily logged actions (Task checkoffs, Habit completes, Journals)</span>
            </div>
            
            <!-- Grid container -->
            <div class="overflow-auto border border-secondary border-opacity-25 rounded p-3 bg-dark bg-opacity-20">
                <div class="github-heatmap" id="heatmap-contribution-grid">
                    <!-- Loaded dynamically via client JS -->
                </div>
                <div class="d-flex justify-content-end gap-1 mt-2 align-items-center small text-muted">
                    <span>Less</span>
                    <div class="heatmap-day" style="background-color: var(--border-color);"></div>
                    <div class="heatmap-day level-1"></div>
                    <div class="heatmap-day level-2"></div>
                    <div class="heatmap-day level-3"></div>
                    <div class="heatmap-day level-4"></div>
                    <span>More</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. Calculated AI insights & highlights -->
<div class="row g-4 mb-4" data-aos="fade-up">
    <div class="col-12">
        <div class="card glass-panel p-4 border-info">
            <h5 class="text-info fw-bold mb-3"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Performance Insights & Suggestions</h5>
            <div class="row g-3" id="analytics-insights-feed">
                <!-- Populated via API data -->
                <div class="col-md-6 skeleton py-3 rounded"></div>
                <div class="col-md-6 skeleton py-3 rounded"></div>
            </div>
        </div>
    </div>
</div>

<script src="<?= e(APP_URL) ?>/assets/js/analytics.js"></script>
