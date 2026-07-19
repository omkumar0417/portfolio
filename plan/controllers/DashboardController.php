<?php
/**
 * Dashboard Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Habit.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Pomodoro.php';
require_once __DIR__ . '/../models/Analytics.php';

class DashboardController extends BaseController {
    private Task $taskModel;
    private Habit $habitModel;
    private Goal $goalModel;
    private Note $noteModel;
    private Pomodoro $pomodoroModel;
    private Analytics $analyticsModel;

    public function __construct() {
        $this->taskModel = new Task();
        $this->habitModel = new Habit();
        $this->goalModel = new Goal();
        $this->noteModel = new Note();
        $this->pomodoroModel = new Pomodoro();
        $this->analyticsModel = new Analytics();
    }

    /**
     * Renders the primary dashboard
     */
    public function index(): void {
        $this->requireAuth();
        
        $userId = (int)$_SESSION['user_id'];
        
        // 1. Gather Basic Greetings Data
        $greeting = getGreetingMessage();
        $todayDate = date('l, d F Y');
        $quote = getDailyQuote();
        
        // 2. Fetch Aggregated Statistics
        $todayTasks = $this->taskModel->getTodayTasks($userId);
        $deadlines = $this->taskModel->getUpcomingDeadlines($userId);
        $recentCompleted = $this->taskModel->getRecentlyCompleted($userId);
        $longestTask = $this->taskModel->getLongestRunningPendingTask($userId);
        
        $habitStats = $this->habitModel->getGlobalStats($userId);
        $habits = $this->habitModel->getAll($userId);
        
        // Today's specific habit logs checklist mapping
        $todayLogs = $this->habitModel->getHabitLogsByDate($userId, date('Y-m-d'));
        $todayLogsMap = array_column($todayLogs, 'status', 'habit_id');

        // Fetch active goal
        $activeGoals = $this->goalModel->getAll($userId, ['status' => 'in_progress']);
        $currentGoal = !empty($activeGoals) ? $activeGoals[0] : null;
        
        // Fetch recent pinned or quick notes
        $recentNotes = $this->noteModel->getAll($userId, ['is_pinned' => 1]);
        if (empty($recentNotes)) {
            // If no pinned notes, show recent notes
            $recentNotes = array_slice($this->noteModel->getAll($userId), 0, 3);
        }
        
        // Focus Session Metrics
        $todayFocusMinutes = $this->pomodoroModel->getTodayFocusMinutes($userId);
        
        // Dynamic Weather mock values (based on user country if configured)
        $userCountry = $_SESSION['user_country'] ?? 'Berlin'; // Default location name
        $weatherTemp = 24; // Static premium temperature baseline
        $weatherDesc = 'Partly Cloudy';
        $weatherIcon = 'fa-cloud-sun';

        // Calculate productivity scores
        $scores = $this->analyticsModel->getProductivityScore($userId, 30);

        // Fetch all categories for quick task add modals
        $categories = DB::fetchAll("SELECT * FROM categories WHERE user_id = ? OR is_system = 1 ORDER BY name ASC", [$userId]);

        $this->render('dashboard/index', [
            'pageTitle' => 'Workspace Dashboard',
            'greeting' => $greeting,
            'todayDate' => $todayDate,
            'quote' => $quote,
            'todayTasks' => $todayTasks,
            'deadlines' => $deadlines,
            'recentCompleted' => $recentCompleted,
            'longestTask' => $longestTask,
            'habitStats' => $habitStats,
            'habits' => $habits,
            'todayLogsMap' => $todayLogsMap,
            'currentGoal' => $currentGoal,
            'recentNotes' => $recentNotes,
            'todayFocusMinutes' => $todayFocusMinutes,
            'weatherTemp' => $weatherTemp,
            'weatherDesc' => $weatherDesc,
            'weatherIcon' => $weatherIcon,
            'scores' => $scores,
            'categories' => $categories
        ]);
    }
}
