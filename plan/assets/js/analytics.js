/**
 * Productivity Analytics Dashboards Renderings
 */

document.addEventListener("DOMContentLoaded", function() {
    loadProductivityAnalytics();
});

function loadProductivityAnalytics() {
    fetch(`${APP_URL}/api/analytics`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Clear skeleton animation loaders
                clearSkeletonLoaders();

                // 1. Render Gauge Widgets
                renderScoreGauges(data.scores);

                // 2. Render Line Chart (Productivity trends & moving average)
                renderTrendsLineChart(data.rolling_averages);

                // 3. Render Donut Chart (Category Distribution)
                renderCategoryDonut(data.categories, data.deadlines);

                // 4. Render Focus Area Chart
                renderFocusArea(data.focus_trends);

                // 5. Render Deadline Stacked Bar
                renderDeadlineBar(data.deadlines);

                // 6. Draw 53-Week GitHub Contribution Grid
                drawContributionHeatmap(data.heatmap);

                // 7. Render Text Insights Feed
                renderInsightsFeed(data.insights, data.efficiency);
            }
        })
        .catch(err => console.error("Failed to parse analytics records: ", err));
}

function clearSkeletonLoaders() {
    const skeletons = document.querySelectorAll(".skeleton");
    skeletons.forEach(s => {
        s.classList.remove("skeleton");
    });
    
    // Display labels
    const labels = ["productivity", "consistency", "focus", "health"];
    labels.forEach(l => {
        const lbl = document.getElementById(`lbl-score-${l}`);
        if (lbl) lbl.classList.remove("d-none");
    });
}

/**
 * Renders the score progress indicators
 */
function renderScoreGauges(scores) {
    const items = [
        { id: "gauge-productivity", score: scores.overall_score, color: "#6366f1" },
        { id: "gauge-consistency", score: scores.consistency_score, color: "#3b82f6" },
        { id: "gauge-focus", score: scores.focus_score, color: "#f59e0b" },
        { id: "gauge-health", score: scores.health_score, color: "#10b981" }
    ];

    items.forEach(item => {
        const ctx = document.getElementById(item.id).getContext("2d");
        const score = item.score || 0;
        
        // Update label text
        const lblId = item.id.replace("gauge-", "lbl-score-");
        const label = document.getElementById(lblId);
        if (label) label.textContent = `${score}%`;

        new Chart(ctx, {
            type: "doughnut",
            data: {
                datasets: [{
                    data: [score, 100 - score],
                    backgroundColor: [item.color, "rgba(255,255,255,0.03)"],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: "82%",
                responsive: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } }
            }
        });
    });
}

/**
 * Line Chart rendering
 */
function renderTrendsLineChart(averages) {
    const ctx = document.getElementById("chart-productivity-trends").getContext("2d");
    
    const labels = averages.map(a => {
        // Format YYYY-MM-DD to DD MMM
        const parts = a.date.split("-");
        if (parts.length === 3) {
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            return `${parts[2]} ${months[parseInt(parts[1]) - 1]}`;
        }
        return a.date;
    });
    
    const completedData = averages.map(a => a.completed_count);
    const rollingAvgData = averages.map(a => a.rolling_avg_7d);

    new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Tasks Completed",
                    data: completedData,
                    borderColor: "rgba(99, 102, 241, 0.4)",
                    backgroundColor: "transparent",
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 1
                },
                {
                    label: "7-Day Moving Avg",
                    data: rollingAvgData,
                    borderColor: "#6366f1",
                    backgroundColor: "rgba(99, 102, 241, 0.05)",
                    fill: true,
                    borderWidth: 3,
                    tension: 0.3,
                    pointRadius: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: "#94a3b8" } }
            },
            scales: {
                x: { grid: { color: "rgba(255,255,255,0.05)" }, ticks: { color: "#94a3b8" } },
                y: { grid: { color: "rgba(255,255,255,0.05)" }, ticks: { color: "#94a3b8" } }
            }
        }
    });
}

/**
 * Donut Chart rendering
 */
function renderCategoryDonut(categories, deadlines) {
    const ctx = document.getElementById("chart-category-donut").getContext("2d");
    
    const labels = categories.map(c => c.name);
    const data = categories.map(c => parseInt(c.task_count));
    const colors = categories.map(c => c.color || "#6366f1");

    // Also populate text counts footer
    document.getElementById("lbl-tasks-total").textContent = (categories.reduce((acc, c) => acc + parseInt(c.task_count), 0) + deadlines.missed).toString();
    document.getElementById("lbl-tasks-done").textContent = deadlines.completed.toString();
    document.getElementById("lbl-tasks-missed").textContent = deadlines.missed.toString();

    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 1,
                borderColor: "rgba(0,0,0,0.2)"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "right", labels: { color: "#94a3b8", font: { size: 11 } } }
            },
            cutout: "70%"
        }
    });
}

/**
 * Area Focus Hours rendering
 */
function renderFocusArea(trends) {
    const ctx = document.getElementById("chart-focus-area").getContext("2d");
    
    const labels = trends.map(t => {
        const parts = t.date.split("-");
        return parts.length === 3 ? `${parts[2]}/${parts[1]}` : t.date;
    });
    
    const focusHours = trends.map(t => parseFloat((t.focus_minutes / 60).toFixed(1)));

    new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                label: "Focus Hours",
                data: focusHours,
                borderColor: "#f59e0b",
                backgroundColor: "rgba(245, 158, 11, 0.1)",
                fill: true,
                borderWidth: 3,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: "rgba(255,255,255,0.03)" }, ticks: { color: "#94a3b8" } },
                y: { grid: { color: "rgba(255,255,255,0.03)" }, ticks: { color: "#94a3b8" } }
            }
        }
    });
}

/**
 * Stacked Deadline Bar rendering
 */
function renderDeadlineBar(dl) {
    const ctx = document.getElementById("chart-deadline-performance").getContext("2d");

    new Chart(ctx, {
        type: "bar",
        data: {
            labels: ["On Time", "Late Completed", "Missed / Pending Overdue"],
            datasets: [{
                data: [dl.on_time, dl.late, dl.missed],
                backgroundColor: ["#10b981", "#f59e0b", "#ef4444"],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: "rgba(255,255,255,0.03)" }, ticks: { color: "#94a3b8" } },
                y: { grid: { color: "rgba(255,255,255,0.03)" }, ticks: { color: "#94a3b8" } }
            }
        }
    });
}

/**
 * Renders GitHub Activity Heatmap Grid
 */
function drawContributionHeatmap(heatmapData) {
    const container = document.getElementById("heatmap-contribution-grid");
    if (!container) return;

    container.innerHTML = "";

    // Generate continuous range of 365 days working backwards from today
    const today = new Date();
    const startDate = new Date();
    startDate.setDate(today.getDate() - 365);

    // Adjust startDate to start on Sunday to line grid rows correctly (7 rows)
    const dayOfWeek = startDate.getDay();
    startDate.setDate(startDate.getDate() - dayOfWeek);

    const tempDate = new Date(startDate.getTime());
    
    // Draw cells
    while (tempDate <= today) {
        const dateStr = tempDate.toISOString().split("T")[0];
        const count = heatmapData[dateStr] || 0;
        
        const cell = document.createElement("div");
        cell.className = "heatmap-day";
        cell.title = `${dateStr}: ${count} activities logged`;

        // Apply visual levels based on activity count
        if (count > 0 && count <= 2) {
            cell.classList.add("level-1");
        } else if (count > 2 && count <= 4) {
            cell.classList.add("level-2");
        } else if (count > 4 && count <= 6) {
            cell.classList.add("level-3");
        } else if (count > 6) {
            cell.classList.add("level-4");
        }

        container.appendChild(cell);
        tempDate.setDate(tempDate.getDate() + 1); // Increment day
    }
}

/**
 * Renders calculated textual insights and efficiency reviews
 */
function renderInsightsFeed(insights, efficiency) {
    const feed = document.getElementById("analytics-insights-feed");
    if (!feed) return;

    feed.innerHTML = "";

    const items = [
        {
            title: "Peak Weekday Productivity",
            desc: `Your highest count of completed tasks occur on <strong>${insights.most_productive_day}</strong>. Keep your schedules clean on this day for deep execution work.`,
            icon: "fa-rocket",
            color: "text-success"
        },
        {
            title: "Task Chunking Density",
            desc: `Your completed tasks average <strong>${insights.average_task_completion_minutes} minutes</strong> in duration. ${insights.average_task_completion_minutes > 90 ? 'High duration. Try to chunk down tasks to smaller checklists.' : 'Good duration density.'}`,
            icon: "fa-scissors",
            color: "text-primary"
        },
        {
            title: "Late Night Procrastinations",
            desc: `You finished <strong>${insights.late_night_completions} tasks</strong> after 8:00 PM. High late completions might cause stress. Try shifting workload forward.`,
            icon: "fa-moon",
            color: "text-warning"
        },
        {
            title: "Task Completion Efficiency",
            desc: `Your task execution efficiency rating is <strong>${efficiency.efficiency_score}%</strong> (Ratio of estimated vs actual time taken). Keep estimates tight!`,
            icon: "fa-bullseye",
            color: "text-info"
        }
    ];

    items.forEach(item => {
        const col = document.createElement("div");
        col.className = "col-md-6";
        col.innerHTML = `
            <div class="p-3 rounded glass-panel h-100 d-flex gap-3 align-items-start" style="background: rgba(255,255,255,0.01);">
                <div class="p-2 rounded bg-dark bg-opacity-25 ${item.color}">
                    <i class="fa-solid ${item.icon} fs-4"></i>
                </div>
                <div>
                    <h6 class="text-white fw-bold mb-1">${item.title}</h6>
                    <p class="text-muted small mb-0" style="line-height: 1.45;">${item.desc}</p>
                </div>
            </div>
        `;
        feed.appendChild(col);
    });
}
