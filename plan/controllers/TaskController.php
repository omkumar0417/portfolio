<?php
/**
 * Tasks Management Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';

class TaskController extends BaseController {
    private Task $taskModel;

    public function __construct() {
        $this->taskModel = new Task();
    }

    /**
     * Display list of tasks with filters and handle CRUD requests
     */
    public function index(): void {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];
        
        // Handle POST requests for CRUD actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
                setFlash('error', 'Security token mismatch. Action aborted.');
                $this->redirect('/tasks');
            }

            $action = $_POST['action'] ?? '';
            
            if ($action === 'create') {
                $data = $this->extractTaskPostData($userId);
                try {
                    $taskId = $this->taskModel->create($data);
                    
                    // Create default audit log trace
                    $userModel = new User();
                    $userModel->logActivity($userId, 'TASK_CREATE', "Created task: {$data['title']}.", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

                    // Handle optional file uploads if submitted
                    $this->processTaskUploads($userId, (int)$taskId);

                    setFlash('success', 'Task successfully created.');
                } catch (Exception $e) {
                    error_log("Failed to create task: " . $e->getMessage());
                    setFlash('error', 'Failed to create task. Please verify parameters.');
                }
                $this->redirect('/tasks');
            }

            if ($action === 'edit') {
                $taskId = (int)($_POST['id'] ?? 0);
                if ($taskId <= 0 || !$this->taskModel->findById($taskId, $userId)) {
                    setFlash('error', 'Task not found.');
                    $this->redirect('/tasks');
                }

                $data = $this->extractTaskPostData($userId);
                try {
                    $this->taskModel->update($taskId, $userId, $data);
                    
                    // Handle file uploads
                    $this->processTaskUploads($userId, $taskId);

                    setFlash('success', 'Task details updated.');
                } catch (Exception $e) {
                    error_log("Failed to update task: " . $e->getMessage());
                    setFlash('error', 'Failed to update task.');
                }
                $this->redirect('/tasks');
            }

            if ($action === 'delete') {
                $taskId = (int)($_POST['id'] ?? 0);
                if ($taskId <= 0 || !$this->taskModel->findById($taskId, $userId)) {
                    setFlash('error', 'Task not found.');
                    $this->redirect('/tasks');
                }

                $this->taskModel->delete($taskId, $userId);
                setFlash('success', 'Task removed successfully.');
                $this->redirect('/tasks');
            }
        }

        // Parse Filters for GET view matching
        $filters = [
            'search' => $_GET['search'] ?? null,
            'priority' => $_GET['priority'] ?? null,
            'status' => $_GET['status'] ?? null,
            'category_id' => $_GET['category_id'] ?? null,
            'difficulty' => $_GET['difficulty'] ?? null,
            'due_date' => $_GET['due_date'] ?? null
        ];

        // Fetch user tasks based on filters
        $tasks = $this->taskModel->getTasksFiltered($userId, $filters);
        
        // Fetch all categories for filter options & form bindings
        $categories = DB::fetchAll("SELECT * FROM categories WHERE user_id = ? OR is_system = 1 ORDER BY name ASC", [$userId]);

        $this->render('tasks/index', [
            'pageTitle' => 'Task Management',
            'tasks' => $tasks,
            'categories' => $categories,
            'filters' => $filters
        ]);
    }

    /**
     * Map request arguments to structured Task payload array
     */
    private function extractTaskPostData(int $userId): array {
        return [
            'user_id' => $userId,
            'category_id' => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
            'title' => trim($_POST['title'] ?? 'Untitled Task'),
            'description' => trim($_POST['description'] ?? ''),
            'priority' => $_POST['priority'] ?? 'medium',
            'status' => $_POST['status'] ?? 'pending',
            'due_date' => $_POST['due_date'] !== '' ? $_POST['due_date'] : null,
            'estimated_time' => (int)($_POST['estimated_time'] ?? 0),
            'actual_time' => (int)($_POST['actual_time'] ?? 0),
            'progress_percent' => (int)($_POST['progress_percent'] ?? 0),
            'reminder_time' => $_POST['reminder_time'] !== '' ? $_POST['reminder_time'] : null,
            'repeat_type' => $_POST['repeat_type'] ?? 'none',
            'repeat_custom' => trim($_POST['repeat_custom'] ?? ''),
            'emoji' => trim($_POST['emoji'] ?? ''),
            'icon' => trim($_POST['icon'] ?? ''),
            'difficulty' => $_POST['difficulty'] ?? 'medium',
            'location' => trim($_POST['location'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];
    }

    /**
     * Handle task attachment uploads securely
     */
    private function processTaskUploads(int $userId, int $taskId): void {
        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $file = $_FILES['attachment'];
        if ($file['size'] > MAX_FILE_SIZE) {
            setFlash('error', 'File upload failed. Size limit exceeded (Max 5MB).');
            return;
        }

        // Validate file extension / mime type
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
            'text/plain', 'application/zip', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (!in_array($file['type'], $allowedTypes)) {
            setFlash('error', 'Unsupported file type. Upload rejected.');
            return;
        }

        // Build target uploads directory
        $targetDir = UPLOAD_DIR . 'attachments/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Rename file to prevent collision and protect paths
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $hashedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetFile = $targetDir . $hashedName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $this->taskModel->addAttachment($userId, $taskId, $file['name'], 'attachments/' . $hashedName, $file['type'], (int)$file['size']);
        } else {
            error_log("Failed to move uploaded task file: " . $file['name']);
        }
    }
}
