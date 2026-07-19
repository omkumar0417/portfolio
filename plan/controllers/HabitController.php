<?php
/**
 * Habit Tracker Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Habit.php';
require_once __DIR__ . '/../models/User.php';

class HabitController extends BaseController {
    private Habit $habitModel;

    public function __construct() {
        $this->habitModel = new Habit();
    }

    /**
     * Renders habits tracking dashboard and manages operations
     */
    public function index(): void {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];

        // Handle operations via POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
                setFlash('error', 'Security token mismatch. Action aborted.');
                $this->redirect('/habits');
            }

            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $data = $this->extractHabitPostData($userId);
                try {
                    $this->habitModel->create($data);
                    
                    $userModel = new User();
                    $userModel->logActivity($userId, 'HABIT_CREATE', "Started tracking habit: {$data['name']}.", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

                    setFlash('success', 'New habit created successfully.');
                } catch (Exception $e) {
                    error_log("Failed to create habit: " . $e->getMessage());
                    setFlash('error', 'Failed to create habit.');
                }
                $this->redirect('/habits');
            }

            if ($action === 'edit') {
                $habitId = (int)($_POST['id'] ?? 0);
                if ($habitId <= 0 || !$this->habitModel->findById($habitId, $userId)) {
                    setFlash('error', 'Habit not found.');
                    $this->redirect('/habits');
                }

                $data = $this->extractHabitPostData($userId);
                try {
                    $this->habitModel->update($habitId, $userId, $data);
                    setFlash('success', 'Habit rules updated.');
                } catch (Exception $e) {
                    error_log("Failed to update habit: " . $e->getMessage());
                    setFlash('error', 'Failed to update habit.');
                }
                $this->redirect('/habits');
            }

            if ($action === 'delete') {
                $habitId = (int)($_POST['id'] ?? 0);
                if ($habitId <= 0 || !$this->habitModel->findById($habitId, $userId)) {
                    setFlash('error', 'Habit not found.');
                    $this->redirect('/habits');
                }

                $this->habitModel->delete($habitId, $userId);
                setFlash('success', 'Habit removed successfully.');
                $this->redirect('/habits');
            }
        }

        // Gather habits list
        $habits = $this->habitModel->getAll($userId);
        
        // Calculate streaks and achievements for each habit
        $habitsWithStats = [];
        foreach ($habits as $h) {
            $stats = $this->habitModel->getStreakStats((int)$h['id']);
            $h['current_streak'] = $stats['current_streak'];
            $h['longest_streak'] = $stats['longest_streak'];
            $h['success_rate'] = $stats['success_rate'];
            $habitsWithStats[] = $h;
        }

        // Fetch 30 days history dates range for habit log checkboxes display
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = date('Y-m-d', strtotime("-$i days"));
        }

        // Fetch logs map for these days
        $logsMap = [];
        foreach ($habits as $h) {
            $logs = $this->habitModel->getLogs((int)$h['id']);
            $hLogsMap = [];
            foreach ($logs as $l) {
                $hLogsMap[$l['date']] = $l['status'];
            }
            $logsMap[$h['id']] = $hLogsMap;
        }

        // Fetch categories for forms
        $categories = DB::fetchAll("SELECT * FROM categories WHERE user_id = ? OR is_system = 1 ORDER BY name ASC", [$userId]);

        $this->render('habits/index', [
            'pageTitle' => 'Habit Tracker',
            'habits' => $habitsWithStats,
            'dates' => $dates,
            'logsMap' => $logsMap,
            'categories' => $categories
        ]);
    }

    private function extractHabitPostData(int $userId): array {
        return [
            'user_id' => $userId,
            'name' => trim($_POST['name'] ?? 'New Habit'),
            'description' => trim($_POST['description'] ?? ''),
            'category_id' => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
            'color' => $_POST['color'] ?? '#6366f1',
            'icon' => $_POST['icon'] ?? 'fa-circle',
            'frequency' => $_POST['frequency'] ?? 'daily',
            'custom_days' => isset($_POST['custom_days']) ? json_encode($_POST['custom_days']) : null,
            'repeat_time' => $_POST['repeat_time'] !== '' ? $_POST['repeat_time'] : null
        ];
    }
}
