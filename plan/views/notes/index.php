<?php
/**
 * Notes Canvas View Template
 */

declare(strict_types=1);

$csrfTokenStr = generateCsrfToken();
?>
<div class="row g-4">
    <!-- 1. Notes left sidebar filters -->
    <div class="col-lg-3" data-aos="fade-right">
        <div class="card glass-panel h-100 p-3">
            <h6 class="text-white fw-bold mb-3"><i class="fa-regular fa-folder-open text-primary me-2"></i>Folders directory</h6>
            
            <div class="list-group list-group-flush mb-4">
                <a href="<?= e(APP_URL) ?>/notes" class="list-group-item bg-transparent text-white border-0 py-2 small <?= empty($filters['folder_id']) ? 'fw-bold text-primary' : 'text-muted' ?>">
                    <i class="fa-solid fa-note-sticky me-2"></i> All Notes
                </a>
                
                <?php foreach ($folders as $folder): ?>
                    <div class="d-flex justify-content-between align-items-center list-group-item bg-transparent border-0 p-0">
                        <a href="<?= e(APP_URL) ?>/notes?folder_id=<?= $folder['id'] ?>" class="text-truncate text-decoration-none py-2 small flex-grow-1 <?= (isset($filters['folder_id']) && $filters['folder_id'] == $folder['id']) ? 'fw-bold text-primary' : 'text-muted' ?>">
                            <i class="fa-regular fa-folder me-2"></i> <?= e($folder['name']) ?>
                        </a>
                        <form action="<?= e(APP_URL) ?>/notes" method="POST" onsubmit="return confirm('Remove folder? This will move all notes inside to general notes directory.');">
                            <?php csrfInput(); ?>
                            <input type="hidden" name="action" value="delete_folder">
                            <input type="hidden" name="folder_id" value="<?= $folder['id'] ?>">
                            <button type="submit" class="btn btn-link text-danger p-1 border-0" style="font-size:0.75rem;"><i class="fa-regular fa-trash-can"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Create Folder Inline form -->
            <form action="<?= e(APP_URL) ?>/notes" method="POST" class="mb-4">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="create_folder">
                <div class="input-group input-group-sm">
                    <input type="text" name="name" class="form-control bg-transparent text-white border-secondary small" placeholder="New folder name..." required>
                    <button type="submit" class="btn btn-outline-secondary border-secondary text-white"><i class="fa-solid fa-plus"></i></button>
                </div>
            </form>
            
            <div class="border-top border-color pt-3">
                <a href="<?= e(APP_URL) ?>/notes?favorite=1" class="text-decoration-none d-block small mb-2 <?= isset($filters['is_favorite']) ? 'text-warning fw-bold' : 'text-muted' ?>">
                    <i class="fa-regular fa-star me-2 text-warning"></i> Starred Favorites
                </a>
            </div>
        </div>
    </div>

    <!-- 2. Notes Content Cards Grid -->
    <div class="col-lg-9" data-aos="fade-left">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div class="search-wrapper" style="width: 250px;">
                <form action="<?= e(APP_URL) ?>/notes" method="GET">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" class="small" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search notes..." autocomplete="off">
                </form>
            </div>
            
            <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                <i class="fa-solid fa-pencil me-1"></i> Compose Note
            </button>
        </div>

        <div class="row g-4" id="notes-canvas-grid">
            <?php if (empty($notes)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fa-regular fa-note-sticky text-muted display-4 mb-3"></i>
                    <h5 class="text-white">Note canvas empty</h5>
                    <p class="text-muted small">Write specs, draft ideas, or plan code. Supports simple markdown layouts.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="col-md-6" id="note-card-<?= $note['id'] ?>">
                        <div class="card glass-panel h-100 p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-dark border border-secondary text-muted small"><i class="fa-regular fa-folder me-1"></i> <?= e($note['folder_name'] ?: 'General') ?></span>
                                    
                                    <div class="d-flex gap-2">
                                        <!-- Favorite Button -->
                                        <form action="<?= e(APP_URL) ?>/notes" method="POST">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="toggle_fav">
                                            <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                            <button type="submit" class="btn btn-link p-0 border-0" style="color: <?= $note['is_favorite'] ? '#eab308' : '#64748b' ?>;">
                                                <i class="<?= $note['is_favorite'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                                            </button>
                                        </form>
                                        
                                        <!-- Pin Button -->
                                        <form action="<?= e(APP_URL) ?>/notes" method="POST">
                                            <?php csrfInput(); ?>
                                            <input type="hidden" name="action" value="toggle_pin">
                                            <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                            <button type="submit" class="btn btn-link p-0 border-0" style="color: <?= $note['is_pinned'] ? '#6366f1' : '#64748b' ?>;">
                                                <i class="fa-solid fa-thumbtack"></i>
                                            </button>
                                        </form>

                                        <!-- Options Menu -->
                                        <div class="dropdown">
                                            <button class="btn btn-link text-muted p-0 border-0" type="button" id="noteOpt<?= $note['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end glass-panel" aria-labelledby="noteOpt<?= $note['id'] ?>">
                                                <li><button class="dropdown-item text-white small" onclick="openEditNoteModal(<?= $note['id'] ?>)"><i class="fa-regular fa-pen-to-square me-2"></i> Edit</button></li>
                                                <li>
                                                    <form action="<?= e(APP_URL) ?>/notes" method="POST" onsubmit="return confirm('Permanently delete this note?');">
                                                        <?php csrfInput(); ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                                        <button type="submit" class="dropdown-item text-danger small"><i class="fa-regular fa-trash-can me-2"></i> Delete</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="text-white fw-bold mb-2"><?= e($note['title']) ?></h5>
                                
                                <!-- Content markdown preview trigger button -->
                                <div class="note-summary-text text-muted small text-truncate-3 mb-3 cursor-pointer" onclick="toggleMarkdownPreview(<?= $note['id'] ?>)">
                                    <?= nl2br(e(substr($note['content'], 0, 150))) ?>...
                                    <div class="text-primary mt-1" style="font-size:0.75rem;"><i class="fa-solid fa-angles-down me-1"></i> Expand preview</div>
                                </div>
                                
                                <!-- Expanded preview element -->
                                <div class="note-markdown-view d-none p-2 rounded bg-dark bg-opacity-20 border border-secondary mb-3 small text-white" id="markdown-view-<?= $note['id'] ?>">
                                    <!-- Populated dynamically via JS markdown compiler -->
                                </div>
                            </div>

                            <!-- Footer Tag attributes -->
                            <?php if ($note['tags']): ?>
                                <div class="border-top border-color pt-2 mt-2">
                                    <?php foreach (explode(',', $note['tags']) as $tag): ?>
                                        <span class="badge bg-secondary rounded-pill me-1" style="font-size:0.7rem;">#<?= e(trim($tag)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Compose Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-panel text-white p-3" style="background-color: var(--bg-card) !important;">
            <div class="modal-header border-color">
                <h5 class="modal-title fw-bold" id="addNoteModalLabel">Compose New Note</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= e(APP_URL) ?>/notes" method="POST">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add-n-title" class="form-label small">Note Title</label>
                        <input type="text" name="title" id="add-n-title" class="form-control bg-transparent text-white border-secondary" placeholder="Spring Boot architecture reviews, specs..." required>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="add-n-folder" class="form-label small">Folder</label>
                            <select name="folder_id" id="add-n-folder" class="form-select bg-transparent text-white border-secondary">
                                <option value="" class="bg-dark">General</option>
                                <?php foreach ($folders as $f): ?>
                                    <option value="<?= $f['id'] ?>" class="bg-dark"><?= e($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="add-n-tags" class="form-label small">Tags (comma-separated)</label>
                            <input type="text" name="tags" id="add-n-tags" class="form-control bg-transparent text-white border-secondary" placeholder="study, programming, ideas">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add-n-content" class="form-label small">Note Content (Markdown supported)</label>
                        <textarea name="content" id="add-n-content" rows="12" class="form-control bg-transparent text-white border-secondary font-monospace" placeholder="# Heading&#10;&#10;- Item 1&#10;- Item 2&#10;&#10;Write clean markdown logs here..."></textarea>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_pinned" id="add-n-pin" class="form-check-input">
                            <label for="add-n-pin" class="form-check-label text-muted small">Pin to top</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_favorite" id="add-n-fav" class="form-check-input">
                            <label for="add-n-fav" class="form-check-label text-muted small">Add to favorites</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-color">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Note Modal -->
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-labelledby="editNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-panel text-white p-3" style="background-color: var(--bg-card) !important;">
            <div class="modal-header border-color">
                <h5 class="modal-title fw-bold" id="editNoteModalLabel">Modify Note</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= e(APP_URL) ?>/notes" method="POST" id="edit-note-form">
                <?php csrfInput(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-n-id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit-n-title" class="form-label small">Note Title</label>
                        <input type="text" name="title" id="edit-n-title" class="form-control bg-transparent text-white border-secondary" required>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit-n-folder" class="form-label small">Folder</label>
                            <select name="folder_id" id="edit-n-folder" class="form-select bg-transparent text-white border-secondary">
                                <option value="" class="bg-dark">General</option>
                                <?php foreach ($folders as $f): ?>
                                    <option value="<?= $f['id'] ?>" class="bg-dark"><?= e($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-n-tags" class="form-label small">Tags</label>
                            <input type="text" name="tags" id="edit-n-tags" class="form-control bg-transparent text-white border-secondary">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-n-content" class="form-label small">Content</label>
                        <textarea name="content" id="edit-n-content" rows="12" class="form-control bg-transparent text-white border-secondary font-monospace"></textarea>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_pinned" id="edit-n-pin" class="form-check-input">
                            <label for="edit-n-pin" class="form-check-label text-muted small">Pin to top</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_favorite" id="edit-n-fav" class="form-check-input">
                            <label for="edit-n-fav" class="form-check-label text-muted small">Add to favorites</label>
                        </div>
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

<!-- Standalone marked.js parser integration for clean markdown preview -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="<?= e(APP_URL) ?>/assets/js/notes.js"></script>
