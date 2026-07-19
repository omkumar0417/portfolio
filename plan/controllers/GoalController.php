<?php
/**
 * Goals Planning Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../models/User.php';

class GoalController extends BaseController {
    private Goal $goalModel;

    public function __construct() {
        $this->goalModel = new Goal();
    }

    /**
     * Renders goals planner dashboard and processes CRUD actions
     */
    public function index(): void {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
                setFlash('error', 'Security token mismatch. Action aborted.');
                $this->redirect('/goals');
            }

            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $data = $this->extractGoalPostData($userId);
                try {
                    $this->goalModel->create($data);
                    
                    $userModel = new User();
                    $userModel->logActivity($userId, 'GOAL_CREATE', "Set up a new goal: {$data['title']}.", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

                    setFlash('success', 'Goal created successfully. Add milestones to track progress.');
                } catch (Exception $e) {
                    error_log("Failed to create goal: " . $e->getMessage());
                    setFlash('error', 'Failed to create goal.');
                }
                $this->redirect('/goals');
            }

            if ($action === 'edit') {
                $goalId = (int)($_POST['id'] ?? 0);
                if ($goalId <= 0 || !$this->goalModel->findById($goalId, $userId)) {
                    setFlash('error', 'Goal not found.');
                    $this->redirect('/goals');
                }

                $data = $this->extractGoalPostData($userId);
                try {
                    $this->goalModel->update($goalId, $userId, $data);
                    setFlash('success', 'Goal parameters updated.');
                } catch (Exception $e) {
                    error_log("Failed to update goal: " . $e->getMessage());
                    setFlash('error', 'Failed to update goal.');
                }
                $this->redirect('/goals');
            }

            if ($action === 'delete') {
                $goalId = (int)($_POST['id'] ?? 0);
                if ($goalId <= 0 || !$this->goalModel->findById($goalId, $userId)) {
                    setFlash('error', 'Goal not found.');
                    $this->redirect('/goals');
                }

                $this->goalModel->delete($goalId, $userId);
                setFlash('success', 'Goal removed successfully.');
                $this->redirect('/goals');
            }
        }

        // Gather filtered goals list
        $filters = [
            'type' => $_GET['type'] ?? null,
            'status' => $_GET['status'] ?? null
        ];
        
        $goals = $this->goalModel->getAll($userId, $filters);
        
        // Populate goals milestones
        $goalsWithMilestones = [];
        foreach ($goals as $goal) {
            $goal['milestones'] = $this->goalModel->getMilestones((int)$goal['id']);
            $goalsWithMilestones[] = $goal;
        }

        $this->render('goals/index', [
            'pageTitle' => 'Goals Planner',
            'goals' => $goalsWithMilestones,
            'filters' => $filters
        ]);
    }

    private function extractGoalPostData(int $userId): array {
        return [
            'user_id' => $userId,
            'title' => trim($_POST['title'] ?? 'New Target'),
            'description' => trim($_POST['description'] ?? ''),
            'type' => $_POST['type'] ?? 'short_term',
            'status' => $_POST['status'] ?? 'pending',
            'deadline' => $_POST['deadline'] !== '' ? $_POST['deadline'] : null,
            'reward' => trim($_POST['reward'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'progress_percent' => (int)($_POST['progress_percent'] ?? 0)
        ];
    }
}
