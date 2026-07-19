<?php
/**
 * AJAX Goals & Milestones API
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/Goal.php';

// Access protection
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated access.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$goalModel = new Goal();

// Handle GET details request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $goalId = (int)($_GET['id'] ?? 0);
    if ($goalId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid goal ID.']);
        exit;
    }
    
    $goal = $goalModel->findById($goalId, $userId);
    if (!$goal) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Goal not found.']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'goal' => $goal
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF security token
    $headers = getallheaders();
    $csrfToken = $_POST['csrf_token'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
    if (!validateCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_milestone':
            $goalId = (int)($_POST['goal_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $deadline = $_POST['deadline'] ?? null;

            if ($goalId <= 0 || $title === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid milestone parameters.']);
                exit;
            }

            // Verify goal ownership
            $goal = $goalModel->findById($goalId, $userId);
            if (!$goal) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Goal not found.']);
                exit;
            }

            $msId = $goalModel->createMilestone($goalId, $title, $deadline);
            
            // Fetch updated goal progress
            $updatedGoal = $goalModel->findById($goalId, $userId);

            echo json_encode([
                'success' => true,
                'milestone_id' => $msId,
                'progress_percent' => $updatedGoal['progress_percent'] ?? 0,
                'status' => $updatedGoal['status']
            ]);
            exit;

        case 'toggle_milestone':
            $milestoneId = (int)($_POST['milestone_id'] ?? 0);
            $goalId = (int)($_POST['goal_id'] ?? 0);
            $isCompleted = (int)($_POST['is_completed'] ?? 0);

            if ($milestoneId <= 0 || $goalId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
                exit;
            }

            // Verify goal ownership
            $goal = $goalModel->findById($goalId, $userId);
            if (!$goal) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Goal not found.']);
                exit;
            }

            $goalModel->toggleMilestone($milestoneId, $goalId, $isCompleted);
            
            // Fetch updated goal progress
            $updatedGoal = $goalModel->findById($goalId, $userId);

            echo json_encode([
                'success' => true,
                'progress_percent' => $updatedGoal['progress_percent'] ?? 0,
                'status' => $updatedGoal['status']
            ]);
            exit;

        case 'delete_milestone':
            $milestoneId = (int)($_POST['milestone_id'] ?? 0);
            $goalId = (int)($_POST['goal_id'] ?? 0);

            if ($milestoneId <= 0 || $goalId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
                exit;
            }

            // Verify goal ownership
            $goal = $goalModel->findById($goalId, $userId);
            if (!$goal) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Goal not found.']);
                exit;
            }

            $goalModel->deleteMilestone($milestoneId, $goalId);
            
            // Fetch updated goal progress
            $updatedGoal = $goalModel->findById($goalId, $userId);

            echo json_encode([
                'success' => true,
                'progress_percent' => $updatedGoal['progress_percent'] ?? 0,
                'status' => $updatedGoal['status']
            ]);
            exit;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unsupported action.']);
            exit;
    }
}
