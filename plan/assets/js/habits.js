/**
 * Habit Tracker Board Scripting
 */

/**
 * Toggles a habit log checkbox for a specific date
 */
function toggleHabitLog(habitId, dateStr, buttonElement) {
    const isChecked = buttonElement.classList.contains("checked");
    const newStatus = isChecked ? "missed" : "completed";

    const formData = new FormData();
    formData.append("action", "log");
    formData.append("habit_id", habitId.toString());
    formData.append("date", dateStr);
    formData.append("status", newStatus);
    formData.append("csrf_token", CSRF_TOKEN);

    fetch(`${APP_URL}/api/habits`, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Apply visual classes
            buttonElement.classList.toggle("checked");
            const icon = buttonElement.querySelector("i");

            if (newStatus === "completed") {
                buttonElement.style.background = buttonElement.style.borderColor;
                icon.classList.remove("d-none");
            } else {
                buttonElement.style.background = "transparent";
                icon.classList.add("d-none");
            }

            // Update row streak metrics in DOM
            const stats = data.habit_stats;
            const row = document.getElementById(`habit-row-${habitId}`);
            if (row && stats) {
                const fireSpan = row.querySelector(".fa-fire").parentElement;
                fireSpan.innerHTML = `<i class="fa-solid fa-fire text-warning me-1"></i>${stats.current_streak}d`;
                
                const statsSpan = row.querySelector(".fa-square-poll-vertical").parentElement;
                statsSpan.innerHTML = `<i class="fa-solid fa-square-poll-vertical text-primary me-1"></i>${stats.success_rate}%`;
            }

            Swal.fire({
                title: newStatus === "completed" ? "Habit Logged!" : "Log Removed.",
                text: "Productivity calculations updated.",
                icon: "success",
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 2000
            });
        }
    })
    .catch(err => console.error("Toggle habit status failed: ", err));
}

/**
 * Retrieve habit rules and load editing form
 */
function openEditHabitModal(habitId) {
    fetch(`${APP_URL}/api/habits?id=${habitId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.habit) {
                const h = data.habit;
                
                document.getElementById("edit-h-id").value = h.id;
                document.getElementById("edit-h-name").value = h.name;
                document.getElementById("edit-h-desc").value = h.description || "";
                document.getElementById("edit-h-cat").value = h.category_id || "";
                document.getElementById("edit-h-freq").value = h.frequency || "daily";
                document.getElementById("edit-h-color").value = h.color || "#6366f1";
                document.getElementById("edit-h-icon").value = h.icon || "fa-circle";

                const modal = new bootstrap.Modal(document.getElementById("editHabitModal"));
                modal.show();
            }
        })
        .catch(err => console.error("Failed to load habit details: ", err));
}
