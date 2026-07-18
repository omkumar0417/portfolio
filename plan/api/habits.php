<?php
/**
 * AJAX Habits Logs API
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/Habit.php';

// Access protection
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated access.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$habitModel = new Habit();

// Handle GET details request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $habitId = (int)($_GET['id'] ?? 0);
    if ($habitId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid habit ID.']);
        exit;
    }
    
    $habit = $habitModel->findById($habitId, $userId);
    if (!$habit) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Habit not found.']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'habit' => $habit
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
    
    if ($action === 'log') {
        $habitId = (int)($_POST['habit_id'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'completed'; // completed, missed, partial
        $notes = trim($_POST['notes'] ?? '');

        if ($habitId <= 0 || !in_array($status, ['completed', 'missed', 'partial'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid logging parameters.']);
            exit;
        }

        // Verify habit ownership
        $habit = $habitModel->findById($habitId, $userId);
        if (!$habit) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Habit record not found.']);
            exit;
        }

        // Perform status update
        $habitModel->logStatus($habitId, $date, $status, $notes ?: null);

        // Fetch updated streak statistics for this habit
        $stats = $habitModel->getStreakStats($habitId);
        
        // Fetch updated global habits parameters (e.g. today completion rates)
        $globalStats = $habitModel->getGlobalStats($userId);

        echo json_encode([
            'success' => true,
            'message' => 'Habit status updated successfully.',
            'habit_stats' => $stats,
            'global_stats' => $globalStats
        ]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unsupported action.']);
exit;
