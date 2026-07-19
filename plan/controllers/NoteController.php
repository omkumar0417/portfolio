<?php
/**
 * Notes Management Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/User.php';

class NoteController extends BaseController {
    private Note $noteModel;

    public function __construct() {
        $this->noteModel = new Note();
    }

    /**
     * Renders note manager dashboard and processes CRUD actions
     */
    public function index(): void {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
                setFlash('error', 'Security token mismatch. Action aborted.');
                $this->redirect('/notes');
            }

            $action = $_POST['action'] ?? '';

            // Handle Quick Add from Dashboard
            if ($action === 'quick_add') {
                $title = trim($_POST['title'] ?? 'Quick Draft');
                $content = trim($_POST['content'] ?? '');
                try {
                    $this->noteModel->create([
                        'user_id' => $userId,
                        'folder_id' => null,
                        'title' => $title,
                        'content' => $content,
                        'is_pinned' => 0,
                        'is_favorite' => 0,
                        'tags' => 'quick-draft'
                    ]);
                    // Emitters respond with standard redirect or allow AJAX to catch
                    if (isAjaxRequest()) {
                        $this->json(['success' => true]);
                    }
                } catch (Exception $e) {
                    error_log("Failed to save quick note: " . $e->getMessage());
                }
                $this->redirect('/dashboard');
            }

            if ($action === 'create') {
                $data = $this->extractNotePostData($userId);
                try {
                    $this->noteModel->create($data);
                    
                    $userModel = new User();
                    $userModel->logActivity($userId, 'NOTE_CREATE', "Created note: {$data['title']}.", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

                    setFlash('success', 'Note saved successfully.');
                } catch (Exception $e) {
                    error_log("Failed to create note: " . $e->getMessage());
                    setFlash('error', 'Failed to save note.');
                }
                $this->redirect('/notes');
            }

            if ($action === 'edit') {
                $noteId = (int)($_POST['id'] ?? 0);
                if ($noteId <= 0 || !$this->noteModel->findById($noteId, $userId)) {
                    setFlash('error', 'Note not found.');
                    $this->redirect('/notes');
                }

                $data = $this->extractNotePostData($userId);
                try {
                    $this->noteModel->update($noteId, $userId, $data);
                    setFlash('success', 'Note updated.');
                } catch (Exception $e) {
                    error_log("Failed to update note: " . $e->getMessage());
                    setFlash('error', 'Failed to update note.');
                }
                $this->redirect('/notes');
            }

            if ($action === 'delete') {
                $noteId = (int)($_POST['id'] ?? 0);
                if ($noteId <= 0 || !$this->noteModel->findById($noteId, $userId)) {
                    setFlash('error', 'Note not found.');
                    $this->redirect('/notes');
                }

                $this->noteModel->delete($noteId, $userId);
                setFlash('success', 'Note deleted.');
                $this->redirect('/notes');
            }

            // --- Folder Operations ---
            if ($action === 'create_folder') {
                $name = trim($_POST['name'] ?? '');
                if ($name !== '') {
                    $this->noteModel->createFolder($userId, $name);
                    setFlash('success', "Folder '{$name}' created.");
                } else {
                    setFlash('error', 'Folder name cannot be empty.');
                }
                $this->redirect('/notes');
            }

            if ($action === 'delete_folder') {
                $folderId = (int)($_POST['folder_id'] ?? 0);
                if ($folderId > 0 && $this->noteModel->deleteFolder($folderId, $userId)) {
                    setFlash('success', 'Folder removed successfully.');
                } else {
                    setFlash('error', 'Folder not found.');
                }
                $this->redirect('/notes');
            }

            // --- Simple Toggle Operations (PIN, FAV, ARCHIVE) ---
            if ($action === 'toggle_pin') {
                $noteId = (int)($_POST['id'] ?? 0);
                $this->noteModel->togglePin($noteId, $userId);
                $this->redirect('/notes');
            }

            if ($action === 'toggle_fav') {
                $noteId = (int)($_POST['id'] ?? 0);
                $this->noteModel->toggleFavorite($noteId, $userId);
                $this->redirect('/notes');
            }
        }

        // Handle GET views listing and filters
        $filters = [
            'folder_id' => $_GET['folder_id'] ?? null,
            'is_favorite' => isset($_GET['favorite']) ? 1 : null,
            'tag' => $_GET['tag'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $notes = $this->noteModel->getAll($userId, $filters);
        $folders = $this->noteModel->getFolders($userId);

        $this->render('notes/index', [
            'pageTitle' => 'Personal Notes',
            'notes' => $notes,
            'folders' => $folders,
            'filters' => $filters
        ]);
    }

    private function extractNotePostData(int $userId): array {
        return [
            'user_id' => $userId,
            'folder_id' => $_POST['folder_id'] !== '' ? (int)$_POST['folder_id'] : null,
            'title' => trim($_POST['title'] ?? 'Untitled Note'),
            'content' => $_POST['content'] ?? '',
            'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
            'is_favorite' => isset($_POST['is_favorite']) ? 1 : 0,
            'tags' => trim($_POST['tags'] ?? '')
        ];
    }
}
