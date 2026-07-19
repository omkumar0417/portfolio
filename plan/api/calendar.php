<?php
/**
 * AJAX Calendar Events API
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';

// Access protection
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated access.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// FullCalendar requests start and end dates via GET params
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-1 month'));
$endDate = $_GET['end'] ?? date('Y-m-d', strtotime('+1 month'));

try {
    $events = [];

    // 1. Fetch Tasks (including Due Date Tasks)
    $tasksSql = "SELECT t.id, t.title, t.due_date, t.priority, t.status, c.color, c.icon, t.emoji 
                 FROM tasks t 
                 LEFT JOIN categories c ON t.category_id = c.id
                 WHERE t.user_id = ? AND t.due_date IS NOT NULL 
                 AND DATE(t.due_date) BETWEEN ? AND ?";
    
    $tasks = DB::fetchAll($tasksSql, [$userId, $startDate, $endDate]);

    foreach ($tasks as $task) {
        // Build customized layout configurations
        $titlePrefix = '';
        if ($task['emoji']) {
            $titlePrefix .= $task['emoji'] . ' ';
        }
        
        $color = $task['color'] ?: '#6366f1';
        if ($task['status'] === 'completed') {
            $color = '#94a3b8'; // Grey out completed tasks
        }

        $events[] = [
            'id' => 'task_' . $task['id'],
            'title' => $titlePrefix . $task['title'],
            'start' => $task['due_date'],
            'color' => $color,
            'allDay' => false,
            'extendedProps' => [
                'type' => 'task',
                'status' => $task['status'],
                'priority' => $task['priority'],
                'dbId' => $task['id']
            ]
        ];
    }

    // 2. Fetch Goal Deadlines
    $goalsSql = "SELECT id, title, deadline, status FROM goals 
                 WHERE user_id = ? AND deadline IS NOT NULL 
                 AND deadline BETWEEN ? AND ?";
    
    $goals = DB::fetchAll($goalsSql, [$userId, $startDate, $endDate]);

    foreach ($goals as $goal) {
        $events[] = [
            'id' => 'goal_' . $goal['id'],
            'title' => '🎯 GOAL: ' . $goal['title'],
            'start' => $goal['deadline'] . 'T23:59:00',
            'color' => '#8b5cf6', // Violet
            'allDay' => true,
            'extendedProps' => [
                'type' => 'goal',
                'status' => $goal['status'],
                'dbId' => $goal['id']
            ]
        ];
    }

    // 3. Fetch Milestones
    $milestonesSql = "SELECT gm.id, gm.title, gm.deadline, gm.is_completed, g.title as goal_title 
                      FROM goal_milestones gm
                      INNER JOIN goals g ON gm.goal_id = g.id
                      WHERE g.user_id = ? AND gm.deadline IS NOT NULL 
                      AND gm.deadline BETWEEN ? AND ?";
    
    $milestones = DB::fetchAll($milestonesSql, [$userId, $startDate, $endDate]);

    foreach ($milestones as $ms) {
        $events[] = [
            'id' => 'milestone_' . $ms['id'],
            'title' => '🏁 MS: ' . $ms['title'] . ' (' . $ms['goal_title'] . ')',
            'start' => $ms['deadline'] . 'T12:00:00',
            'color' => $ms['is_completed'] ? '#94a3b8' : '#ec4899', // Pink / Slate
            'allDay' => true,
            'extendedProps' => [
                'type' => 'milestone',
                'dbId' => $ms['id']
            ]
        ];
    }

    // 4. User Birthday / Exams (System Categories Highlight)
    // Pull User Birthday
    $user = DB::fetch("SELECT birthday FROM users WHERE id = ?", [$userId]);
    if ($user && $user['birthday']) {
        $bdayObj = new DateTime($user['birthday']);
        $bdayMonthDay = $bdayObj->format('m-d');
        
        // Loop years in request range
        $startYear = (int)date('Y', strtotime($startDate));
        $endYear = (int)date('Y', strtotime($endDate));
        for ($yr = $startYear; $yr <= $endYear; $yr++) {
            $events[] = [
                'id' => 'birthday_' . $yr,
                'title' => '🎂 Your Birthday!',
                'start' => "$yr-$bdayMonthDay",
                'color' => '#f43f5e',
                'allDay' => true,
                'extendedProps' => [
                    'type' => 'birthday'
                ]
            ];
        }
    }

    echo json_encode($events);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
