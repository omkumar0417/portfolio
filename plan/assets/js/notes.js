/**
 * Personal Notes Canvas Scripting
 */

/**
 * Compiles raw markdown text and toggles the preview element display
 */
function toggleMarkdownPreview(noteId) {
    const view = document.getElementById(`markdown-view-${noteId}`);
    if (!view) return;

    if (!view.classList.contains("d-none")) {
        view.classList.add("d-none");
        return;
    }

    // Retrieve raw specs to compile
    fetch(`${APP_URL}/api/notes?id=${noteId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.note) {
                const rawContent = data.note.content || "*No content written.*";
                
                // Parse markdown utilizing CDN marked parser
                if (typeof marked !== 'undefined') {
                    view.innerHTML = marked.parse(rawContent);
                } else {
                    view.textContent = rawContent; // Fallback plain text
                }
                view.classList.remove("d-none");
            }
        })
        .catch(err => console.error("Failed to parse note markdown: ", err));
}

/**
 * Retrieve note details and load edit modal
 */
function openEditNoteModal(noteId) {
    fetch(`${APP_URL}/api/notes?id=${noteId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.note) {
                const n = data.note;

                document.getElementById("edit-n-id").value = n.id;
                document.getElementById("edit-n-title").value = n.title;
                document.getElementById("edit-n-folder").value = n.folder_id || "";
                document.getElementById("edit-n-tags").value = n.tags || "";
                document.getElementById("edit-n-content").value = n.content || "";
                document.getElementById("edit-n-pin").checked = n.is_pinned == 1;
                document.getElementById("edit-n-fav").checked = n.is_favorite == 1;

                const modal = new bootstrap.Modal(document.getElementById("editNoteModal"));
                modal.show();
            }
        })
        .catch(err => console.error("Failed to load note specs: ", err));
}
