<?php
/**
 * Pomodoro Focus Timer Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Pomodoro.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';

class PomodoroController extends BaseController {
    private Pomodoro $pomodoroModel;
    private Task $taskModel;

    public function __construct() {
        $this->pomodoroModel = new Pomodoro();
        $this->taskModel = new Task();
    }

    /**
     * Renders Pomodoro workspace and handles logging actions
     */
    public function index(): void {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];

        // Handle AJAX POST logs
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
                $this->json(['success' => false, 'error' => 'Security token mismatch.'], 403);
            }

            $action = $_POST['action'] ?? '';
            
            if ($action === 'log') {
                $duration = (int)($_POST['duration_minutes'] ?? 25);
                $taskId = $_POST['task_id'] !== '' ? (int)$_POST['task_id'] : null;
                $type = $_POST['type'] ?? 'focus'; // focus, short_break, long_break

                if ($duration <= 0 || !in_array($type, ['focus', 'short_break', 'long_break'])) {
                    $this->json(['success' => false, 'error' => 'Invalid parameters.'], 400);
                }

                try {
                    $logId = $this->pomodoroModel->logSession($userId, $duration, $taskId, $type);
                    
                    // Increment actual_time on the target task if linked
                    if ($taskId > 0 && $type === 'focus') {
                        $task = $this->taskModel->findById($taskId, $userId);
                        if ($task) {
                            $task['actual_time'] = (int)($task['actual_time'] ?? 0) + $duration;
                            // Check if progress needs to be adjusted
                            $this->taskModel->update($taskId, $userId, $task);
                        }
                    }

                    $userModel = new User();
                    $userModel->logActivity($userId, 'POMODORO_COMPLETE', "Completed {$duration} mins focus session.", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

                    $this->json([
                        'success' => true, 
                        'message' => 'Focus session logged successfully.',
                        'log_id' => $logId
                    ]);
                } catch (Exception $e) {
                    error_log("Failed to log pomodoro: " . $e->getMessage());
                    $this->json(['success' => false, 'error' => 'Database error.'], 500);
                }
            }
        }

        // Fetch active tasks list to allow linking
        $activeTasks = $this->taskModel->getTasksFiltered($userId, ['status' => 'in_progress']);
        if (empty($activeTasks)) {
            $activeTasks = $this->taskModel->getTasksFiltered($userId, ['status' => 'pending']);
        }

        // Fetch logs history list
        $recentLogs = $this->pomodoroModel->getRecentLogs($userId, 10);
        
        $todayFocusMinutes = $this->pomodoroModel->getTodayFocusMinutes($userId);

        $this->render('pomodoro/index', [
            'pageTitle' => 'Pomodoro Space',
            'activeTasks' => $activeTasks,
            'recentLogs' => $recentLogs,
            'todayFocusMinutes' => $todayFocusMinutes
        ]);
    }
}
