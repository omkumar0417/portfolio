/**
 * Core Application Javascript Bundle
 */

document.addEventListener("DOMContentLoaded", function () {
    // 1. Mobile Sidebar Toggler
    const sidebarToggle = document.getElementById("mobile-sidebar-toggle");
    const sidebar = document.getElementById("sidebar");
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", function (e) {
            e.stopPropagation();
            sidebar.classList.toggle("mobile-open");
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener("click", function (e) {
            if (sidebar.classList.contains("mobile-open") && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove("mobile-open");
            }
        });
    }

    // 2. Real-time Dashboard Ticking Clock
    const clockElement = document.getElementById("dashboard-clock");
    if (clockElement) {
        setInterval(() => {
            const now = new Date();
            clockElement.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }, 1000);
    }

    // 3. Global Instant Search & Autocomplete
    const searchInput = document.getElementById("global-search-input");
    const autocompleteDropdown = document.getElementById("search-autocomplete-dropdown");

    if (searchInput && autocompleteDropdown) {
        let searchTimeout = null;

        searchInput.addEventListener("input", function () {
            clearTimeout(searchTimeout);
            const query = searchInput.value.trim();

            if (query.length < 2) {
                autocompleteDropdown.style.display = "none";
                return;
            }

            searchTimeout = setTimeout(() => {
                // Query tasks through modular API
                fetch(`${APP_URL}/api/tasks?search=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        autocompleteDropdown.innerHTML = "";
                        
                        if (data.success && data.task) {
                            // Singular fetch or list array
                            const tasks = Array.isArray(data.task) ? data.task : [data.task];
                            if (tasks.length > 0) {
                                autocompleteDropdown.style.display = "block";
                                tasks.forEach(task => {
                                    const link = document.createElement("a");
                                    link.href = `${APP_URL}/tasks`;
                                    link.innerHTML = `<i class="fa-solid fa-list-check me-2 text-primary"></i> ${task.title} <span class="badge bg-secondary float-end small">Task</span>`;
                                    autocompleteDropdown.appendChild(link);
                                });
                            }
                        } else {
                            autocompleteDropdown.style.display = "none";
                        }
                    })
                    .catch(err => console.error("Global search query failed: ", err));
            }, 300);
        });

        // Hide search results on click away
        document.addEventListener("click", function (e) {
            if (!searchInput.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
                autocompleteDropdown.style.display = "none";
            }
        });
    }

    // 4. Polling In-App Alerts
    pollNotifications();
    setInterval(pollNotifications, 60000); // Poll notifications every 60s

    const markAllReadBtn = document.getElementById("mark-all-read-btn");
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener("click", function () {
            const formData = new FormData();
            formData.append("action", "mark_read");
            formData.append("csrf_token", CSRF_TOKEN);

            fetch(`${APP_URL}/api/notifications`, {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    pollNotifications();
                }
            })
            .catch(err => console.error("Failed clear alerts action: ", err));
        });
    }

    // 5. Quick note form submission AJAX
    const quickNoteForm = document.getElementById("quick-note-form");
    if (quickNoteForm) {
        quickNoteForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(quickNoteForm);

            // We direct quick notes to NoteController controller action via page post or API endpoint
            fetch(`${APP_URL}/notes`, {
                method: "POST",
                body: formData
            })
            .then(() => {
                Swal.fire({
                    title: "Draft Saved!",
                    text: "Quick note added successfully.",
                    icon: "success",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 2000
                });
                quickNoteForm.reset();
            })
            .catch(err => console.error("Quick note submit failed: ", err));
        });
    }
});

/**
 * Dynamic Notifications Poller
 */
function pollNotifications() {
    const list = document.getElementById("notification-dropdown-list");
    const countBadge = document.getElementById("notification-unread-count");

    if (!list) return;

    fetch(`${APP_URL}/api/notifications`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const unread = data.notifications;
                if (unread.length > 0) {
                    countBadge.textContent = unread.length;
                    countBadge.classList.remove("d-none");
                    
                    list.innerHTML = "";
                    unread.forEach(n => {
                        const item = document.createElement("li");
                        item.className = "px-3 py-2 border-bottom border-color small text-white";
                        item.innerHTML = `
                            <div class="fw-bold">${n.title}</div>
                            <div class="text-muted" style="font-size:0.8rem;">${n.message}</div>
                            <div class="text-end text-muted mt-1" style="font-size:0.7rem;">${n.created_at}</div>
                        `;
                        list.appendChild(item);
                        
                        // Push native browser notifications if allowed
                        triggerBrowserNotification(n.title, n.message);
                    });
                } else {
                    countBadge.classList.add("d-none");
                    list.innerHTML = `<li class="px-3 py-2 text-muted small text-center">No new notifications.</li>`;
                }
            }
        })
        .catch(err => console.error("Poll notifications failed: ", err));
}

/**
 * Browser notifications trigger helper
 */
function triggerBrowserNotification(title, message) {
    if ("Notification" in window) {
        if (Notification.permission === "granted") {
            new Notification(title, { body: message });
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    new Notification(title, { body: message });
                }
            });
        }
    }
}

/**
 * Complete a task directly from the dashboard
 */
function completeTaskDirect(taskId, buttonElement) {
    const formData = new FormData();
    formData.append("action", "status");
    formData.append("id", taskId.toString());
    formData.append("status", "completed");
    formData.append("csrf_token", CSRF_TOKEN);

    fetch(`${APP_URL}/api/tasks`, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Apply visual strike-through and slide away
            const checkIcon = buttonElement.querySelector("i");
            checkIcon.classList.remove("opacity-0");
            
            const taskItem = buttonElement.closest(".list-group-item");
            taskItem.style.transition = "all 0.5s ease";
            taskItem.style.opacity = "0.4";
            taskItem.style.transform = "translateX(20px)";
            
            Swal.fire({
                title: "Task Completed!",
                text: "Productivity scores updated.",
                icon: "success",
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 2000
            });
            
            setTimeout(() => {
                taskItem.remove();
                // Reload dashboard after a second to recalculate circular rings
                window.location.reload();
            }, 1000);
        }
    })
    .catch(err => console.error("Task completion call failed: ", err));
}

/**
 * Check-in habit completion state directly from dashboard
 */
function toggleHabitDirect(habitId, buttonElement) {
    const isChecked = buttonElement.classList.contains("checked");
    const newStatus = isChecked ? "missed" : "completed";

    const formData = new FormData();
    formData.append("action", "log");
    formData.append("habit_id", habitId.toString());
    formData.append("status", newStatus);
    formData.append("csrf_token", CSRF_TOKEN);

    fetch(`${APP_URL}/api/habits`, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            buttonElement.classList.toggle("checked");
            const icon = buttonElement.querySelector("i");
            
            if (newStatus === "completed") {
                buttonElement.style.background = buttonElement.style.borderColor;
                icon.classList.remove("d-none");
            } else {
                buttonElement.style.background = "transparent";
                icon.classList.add("d-none");
            }

            Swal.fire({
                title: newStatus === "completed" ? "Habit Completed!" : "Habit Missed.",
                text: "Streaks logs updated.",
                icon: newStatus === "completed" ? "success" : "info",
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 2000
            });
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
    .catch(err => console.error("Habit check-in failed: ", err));
}
