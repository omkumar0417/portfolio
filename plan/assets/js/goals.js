/**
 * Goals Planning Board Scripting
 */

/**
 * Check off milestone item status
 */
function toggleMilestoneStatus(milestoneId, goalId, checkboxElement) {
    const isCompleted = checkboxElement.checked ? 1 : 0;

    const formData = new FormData();
    formData.append("action", "toggle_milestone");
    formData.append("milestone_id", milestoneId.toString());
    formData.append("goal_id", goalId.toString());
    formData.append("is_completed", isCompleted.toString());
    formData.append("csrf_token", CSRF_TOKEN);

    fetch(`${APP_URL}/api/goals`, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update progress bars
            const progress = data.progress_percent;
            const bar = document.getElementById(`goal-progress-bar-${goalId}`);
            const label = document.getElementById(`goal-progress-label-${goalId}`);
            
            if (bar && label) {
                bar.style.width = `${progress}%`;
                label.textContent = `${progress}%`;
            }

            // Strikethrough checkbox label
            const labelEl = checkboxElement.nextElementSibling;
            if (isCompleted === 1) {
                labelEl.classList.add("text-decoration-line-through", "text-muted");
            } else {
                labelEl.classList.remove("text-decoration-line-through", "text-muted");
            }

            // Update badge status if goal completed
            const card = document.getElementById(`goal-card-${goalId}`);
            if (card && data.status) {
                const badge = card.querySelector(".badge");
                badge.className = `badge ${data.status === 'completed' ? 'bg-success' : 'bg-warning text-dark'} text-uppercase small mb-1`;
                badge.textContent = data.status;
            }
        }
    })
    .catch(err => console.error("Toggle milestone status failed: ", err));
}

/**
 * Add a new milestone key result to goal
 */
function addNewMilestoneItem(goalId) {
    const input = document.getElementById(`new-ms-input-${goalId}`);
    if (!input) return;

    const title = input.value.trim();
    if (title === "") return;

    const formData = new FormData();
    formData.append("action", "create_ms"); // triggers default add milestone
    formData.append("action", "create_milestone");
    formData.append("goal_id", goalId.toString());
    formData.append("title", title);
    formData.append("csrf_token", CSRF_TOKEN);

    fetch(`${APP_URL}/api/goals`, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = "";
            
            // Re-render checklist item in DOM
            const container = document.getElementById(`milestones-container-${goalId}`);
            const emptyLabel = document.getElementById(`empty-ms-${goalId}`);
            if (emptyLabel) emptyLabel.remove();

            const div = document.createElement("div");
            div.className = "d-flex justify-content-between align-items-center mb-1 py-1 px-2 rounded";
            div.style.background = "rgba(255,255,255,0.01)";
            
            div.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="ms-${data.milestone_id}" onclick="toggleMilestoneStatus(${data.milestone_id}, ${goalId}, this)">
                    <label class="form-check-label text-white small" for="ms-${data.milestone_id}" style="font-size: 0.8rem;">
                        ${title}
                    </label>
                </div>
                <button class="btn btn-link text-danger p-0 border-0 fs-6" onclick="deleteMilestoneItem(${data.milestone_id}, ${goalId}, this)">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            `;
            container.appendChild(div);

            // Reset progress bars
            const progress = data.progress_percent;
            const bar = document.getElementById(`goal-progress-bar-${goalId}`);
            const label = document.getElementById(`goal-progress-label-${goalId}`);
            if (bar && label) {
                bar.style.width = `${progress}%`;
                label.textContent = `${progress}%`;
            }
        }
    })
    .catch(err => console.error("Create milestone failed: ", err));
}

/**
 * Remove milestone
 */
function deleteMilestoneItem(milestoneId, goalId, buttonElement) {
    if (!confirm("Permanently delete this key result milestone?")) return;

    const formData = new FormData();
    formData.append("action", "delete_milestone");
    formData.append("milestone_id", milestoneId.toString());
    formData.append("goal_id", goalId.toString());
    formData.append("csrf_token", CSRF_TOKEN);

    fetch(`${APP_URL}/api/goals`, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = buttonElement.closest(".d-flex");
            row.remove();

            // Recalculate progress bars
            const progress = data.progress_percent;
            const bar = document.getElementById(`goal-progress-bar-${goalId}`);
            const label = document.getElementById(`goal-progress-label-${goalId}`);
            if (bar && label) {
                bar.style.width = `${progress}%`;
                label.textContent = `${progress}%`;
            }
        }
    })
    .catch(err => console.error("Delete milestone failed: ", err));
}

/**
 * Fetch goal details and populate edit modal form
 */
function openEditGoalModal(goalId) {
    fetch(`${APP_URL}/api/goals?id=${goalId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.goal) {
                const g = data.goal;

                document.getElementById("edit-g-id").value = g.id;
                document.getElementById("edit-g-title").value = g.title;
                document.getElementById("edit-g-desc").value = g.description || "";
                document.getElementById("edit-g-type").value = g.type || "short_term";
                document.getElementById("edit-g-status").value = g.status || "pending";
                document.getElementById("edit-g-due").value = g.deadline || "";
                document.getElementById("edit-g-reward").value = g.reward || "";
                document.getElementById("edit-g-notes").value = g.notes || "";
                document.getElementById("edit-g-progress").value = g.progress_percent || 0;

                const modal = new bootstrap.Modal(document.getElementById("editGoalModal"));
                modal.show();
            }
        })
        .catch(err => console.error("Failed to load goal specifications: ", err));
}
