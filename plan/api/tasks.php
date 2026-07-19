<?php
/**
 * AJAX Tasks Actions & Updates API
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/Task.php';

// Access protection
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated access.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$taskModel = new Task();

// Handle GET details request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $taskId = (int)($_GET['id'] ?? 0);
    if ($taskId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid task ID.']);
        exit;
    }
    
    $task = $taskModel->findById($taskId, $userId);
    if (!$task) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Task not found.']);
        exit;
    }
    
    $subtasks = $taskModel->getSubtasks($taskId);
    $attachments = $taskModel->getAttachments($taskId);
    
    echo json_encode([
        'success' => true,
        'task' => $task,
        'subtasks' => $subtasks,
        'attachments' => $attachments
    ]);
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token for security
    $headers = getallheaders();
    $csrfToken = $_POST['csrf_token'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
    if (!validateCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'move': // Drag and drop update (due_date)
            $taskId = (int)($_POST['id'] ?? 0);
            $newDueDate = $_POST['due_date'] ?? null;
            
            if ($taskId <= 0 || !$newDueDate) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Parameters missing.']);
                exit;
            }
            
            $task = $taskModel->findById($taskId, $userId);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Task not found.']);
                exit;
            }
            
            // Retain original settings but update due date
            $task['due_date'] = $newDueDate;
            $taskModel->update($taskId, $userId, $task);
            
            echo json_encode(['success' => true, 'message' => 'Task rescheduled successfully.']);
            exit;

        case 'status': // Simple status modification
            $taskId = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? 'pending';
            
            $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled', 'missed', 'postponed', 'archived'];
            if ($taskId <= 0 || !in_array($status, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid status parameters.']);
                exit;
            }
            
            $task = $taskModel->findById($taskId, $userId);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Task not found.']);
                exit;
            }
            
            $taskModel->updateStatus($taskId, $userId, $status);
            echo json_encode(['success' => true, 'message' => 'Status changed successfully.', 'status' => $status]);
            exit;

        case 'create_subtask':
            $taskId = (int)($_POST['task_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            
            if ($taskId <= 0 || $title === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid subtask input.']);
                exit;
            }
            
            $task = $taskModel->findById($taskId, $userId);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Parent task not found.']);
                exit;
            }
            
            $subId = $taskModel->createSubtask($taskId, $title);
            echo json_encode(['success' => true, 'subtask_id' => $subId, 'title' => $title]);
            exit;

        case 'toggle_subtask':
            $subtaskId = (int)($_POST['subtask_id'] ?? 0);
            $taskId = (int)($_POST['task_id'] ?? 0);
            $isCompleted = (int)($_POST['is_completed'] ?? 0);
            
            if ($subtaskId <= 0 || $taskId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Parameter error.']);
                exit;
            }
            
            $task = $taskModel->findById($taskId, $userId);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Parent task not found.']);
                exit;
            }
            
            $taskModel->toggleSubtask($subtaskId, $taskId, $isCompleted);
            
            // Get updated progress
            $updatedTask = $taskModel->findById($taskId, $userId);
            echo json_encode([
                'success' => true, 
                'progress_percent' => $updatedTask['progress_percent'] ?? 0,
                'status' => $updatedTask['status']
            ]);
            exit;

        case 'delete_subtask':
            $subtaskId = (int)($_POST['subtask_id'] ?? 0);
            $taskId = (int)($_POST['task_id'] ?? 0);
            
            if ($subtaskId <= 0 || $taskId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Parameter error.']);
                exit;
            }
            
            $task = $taskModel->findById($taskId, $userId);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Parent task not found.']);
                exit;
            }
            
            $taskModel->deleteSubtask($subtaskId, $taskId);
            
            // Get updated progress
            $updatedTask = $taskModel->findById($taskId, $userId);
            echo json_encode([
                'success' => true, 
                'progress_percent' => $updatedTask['progress_percent'] ?? 0
            ]);
            exit;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unsupported action requested.']);
            exit;
    }
}
