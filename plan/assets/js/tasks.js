/**
 * Task Management Board Scripting
 */

document.addEventListener("DOMContentLoaded", function() {
    // Load subtasks for all currently visible task cards on the board
    if (typeof TASKS_LIST_IDS !== 'undefined' && Array.isArray(TASKS_LIST_IDS)) {
        TASKS_LIST_IDS.forEach(taskId => {
            loadSubtaskChecklist(taskId);
        });
    }
});

/**
 * Fetch and render checklist items for a specific task
 */
function loadSubtaskChecklist(taskId) {
    const container = document.getElementById(`subtask-container-${taskId}`);
    if (!container) return;

    fetch(`${APP_URL}/api/tasks?id=${taskId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const subtasks = data.subtasks;
                container.innerHTML = "";

                if (subtasks.length === 0) {
                    container.innerHTML = `<p class="text-muted small text-center my-2">Checklist is empty.</p>`;
                    return;
                }

                subtasks.forEach(item => {
                    const div = document.createElement("div");
                    div.className = "d-flex justify-content-between align-items-center mb-1 py-1 px-2 rounded";
                    div.style.background = "rgba(255,255,255,0.01)";
                    
                    const isChecked = item.is_completed == 1;
                    const checkedClass = isChecked ? "text-decoration-line-through text-muted" : "";

                    div.innerHTML = `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sub-${item.id}" ${isChecked ? 'checked' : ''} onclick="toggleSubtaskStatus(${item.id}, ${taskId}, this)">
                            <label class="form-check-label text-white small ${checkedClass}" for="sub-${item.id}" style="font-size:0.8rem;">
                                ${item.title}
                            </label>
                        </div>
                        <button class="btn btn-link text-danger p-0 border-0 fs-6" onclick="deleteSubtaskItem(${item.id}, ${taskId}, this)">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    `;
                    container.appendChild(div);
                });
            }
        })
        .catch(err => console.error(`Failed to load subtasks for task ${taskId}: `, err));
}

/**
 * Check-off subtask state update
 */
function toggleSubtaskStatus(subtaskId, taskId, checkboxElement) {
    const isCompleted = checkboxElement.checked ? 1 : 0;
    
    const formData = new FormData();
    formData.append("action", "toggle_subtask");
    formData.append("subtask_id", subtaskId.toString());
    formData.append("task_id", taskId.toString());
    formData.append("is_completed", isCompleted.toString());
    formData.append("csrf_token", CSRF_TOKEN);

    fetch(`${APP_URL}/api/tasks`, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Update visual progress bars
            const progress = data.progress_percent;
            const bar = document.getElementById(`task-progress-bar-${taskId}`);
            const label = document.getElementById(`task-progress-label-${taskId}`);
            
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
        }
    })
    .catch(err => console.error("Toggle subtask status failed: ", err));
}

/**
 * Add a new checklist item to the task
 */
function addNewSubtaskItem(taskId) {
    const input = document.getElementById(`new-subtask-input-${taskId}`);
    if (!input) return;

    const title = input.value.trim();
    if (title === "") return;

    const formData = new FormData();
    formData.append("action", "create_subtask");
    formData.append("task_id", taskId.toString());
    formData.append("title", title);
    formData.append("csrf_token", CSRF_TOKEN);

    fetch(`${APP_URL}/api/tasks`, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = "";
            loadSubtaskChecklist(taskId);
            
            // Reload progress bar
            const bar = document.getElementById(`task-progress-bar-${taskId}`);
            const label = document.getElementById(`task-progress-label-${taskId}`);
            if (bar && label) {
                // If adding first checklist item, progress resets back
                bar.style.width = `0%`;
                label.textContent = `0%`;
            }
        }
    })
    .catch(err => console.error("Create checklist item failed: ", err));
}

/**
 * Remove a checklist item
 */
function deleteSubtaskItem(subtaskId, taskId, buttonElement) {
    if (!confirm("Remove this checklist item?")) return;

    const formData = new FormData();
    formData.append("action", "delete_subtask");
    formData.append("subtask_id", subtaskId.toString());
    formData.append("task_id", taskId.toString());
    formData.append("csrf_token", CSRF_TOKEN);

    fetch(`${APP_URL}/api/tasks`, {
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
            const bar = document.getElementById(`task-progress-bar-${taskId}`);
            const label = document.getElementById(`task-progress-label-${taskId}`);
            if (bar && label) {
                bar.style.width = `${progress}%`;
                label.textContent = `${progress}%`;
            }
        }
    })
    .catch(err => console.error("Delete subtask item failed: ", err));
}

/**
 * Fetch task specifications and populate editing form modal
 */
function openEditTaskModal(taskId) {
    fetch(`${APP_URL}/api/tasks?id=${taskId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.task) {
                const t = data.task;
                
                document.getElementById("edit-id").value = t.id;
                document.getElementById("edit-title").value = t.title;
                document.getElementById("edit-emoji").value = t.emoji || "";
                document.getElementById("edit-desc").value = t.description || "";
                document.getElementById("edit-category").value = t.category_id || "";
                document.getElementById("edit-priority").value = t.priority || "medium";
                document.getElementById("edit-status").value = t.status || "pending";
                document.getElementById("edit-difficulty").value = t.difficulty || "medium";
                document.getElementById("edit-est").value = t.estimated_time || 0;
                document.getElementById("edit-act").value = t.actual_time || 0;
                document.getElementById("edit-location").value = t.location || "";
                document.getElementById("edit-repeat").value = t.repeat_type || "none";
                
                // Format datetime-local formatting Y-m-d\TH:i
                if (t.due_date) {
                    const due = new Date(t.due_date);
                    const formatted = due.toISOString().slice(0, 16);
                    document.getElementById("edit-due").value = formatted;
                } else {
                    document.getElementById("edit-due").value = "";
                }
                
                if (t.reminder_time) {
                    const rem = new Date(t.reminder_time);
                    const formatted = rem.toISOString().slice(0, 16);
                    document.getElementById("edit-reminder").value = formatted;
                } else {
                    document.getElementById("edit-reminder").value = "";
                }

                // Show Bootstrap modal
                const modal = new bootstrap.Modal(document.getElementById("editTaskModal"));
                modal.show();
            }
        })
        .catch(err => console.error("Edit form loader failed: ", err));
}
