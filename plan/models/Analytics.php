<?php
/**
 * Productivity & Behavior Analytics Model
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Analytics extends BaseModel {
    
    /**
     * Calculates the productivity score (0-100) for a user over the last $days days.
     * Weights: Task Completion Rate (50%), Habit Success Rate (30%), Goal Milestone Rate (20%).
     */
    public function getProductivityScore(int $userId, int $days = 30): array {
        // Task completion rate
        $tasks = DB::fetch(
            "SELECT 
             COUNT(*) as total, 
             SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM tasks WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$userId, $days]
        );
        $taskTotal = (int)($tasks['total'] ?? 0);
        $taskCompleted = (int)($tasks['completed'] ?? 0);
        $taskRate = $taskTotal > 0 ? ($taskCompleted / $taskTotal) * 100 : 0.0;

        // Habit success rate
        $habits = DB::fetch(
            "SELECT 
             COUNT(*) as total,
             SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM habit_logs hl
             INNER JOIN habits h ON hl.habit_id = h.id
             WHERE h.user_id = ? AND hl.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$userId, $days]
        );
        $habitTotal = (int)($habits['total'] ?? 0);
        $habitCompleted = (int)($habits['completed'] ?? 0);
        $habitRate = $habitTotal > 0 ? ($habitCompleted / $habitTotal) * 100 : 0.0;

        // Goal Milestones rate
        $goals = DB::fetch(
            "SELECT 
             COUNT(*) as total,
             SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed
             FROM goal_milestones gm
             INNER JOIN goals g ON gm.goal_id = g.id
             WHERE g.user_id = ? AND g.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$userId, $days]
        );
        $goalTotal = (int)($goals['total'] ?? 0);
        $goalCompleted = (int)($goals['completed'] ?? 0);
        $goalRate = $goalTotal > 0 ? ($goalCompleted / $goalTotal) * 100 : 0.0;

        // Fallbacks if no data exists, weight accordingly
        $weights = ['task' => 0.5, 'habit' => 0.3, 'goal' => 0.2];
        $totalWeight = 0.0;
        $scoreSum = 0.0;

        if ($taskTotal > 0) {
            $scoreSum += $taskRate * $weights['task'];
            $totalWeight += $weights['task'];
        }
        if ($habitTotal > 0) {
            $scoreSum += $habitRate * $weights['habit'];
            $totalWeight += $weights['habit'];
        }
        if ($goalTotal > 0) {
            $scoreSum += $goalRate * $weights['goal'];
            $totalWeight += $weights['goal'];
        }

        $overallScore = $totalWeight > 0 ? round($scoreSum / $totalWeight) : 70; // 70 standard default baseline

        // Calculate Focus Score (based on actual pomodoro focus minutes vs standard 120 minutes target per day)
        $focus = DB::fetch(
            "SELECT SUM(duration_minutes) as focus_mins 
             FROM pomodoro_logs WHERE user_id = ? AND type = 'focus' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$userId, $days]
        );
        $focusMins = (int)($focus['focus_mins'] ?? 0);
        $expectedFocusMins = $days * 120; // 2 hours/day
        $focusScore = $expectedFocusMins > 0 ? min(100, round(($focusMins / $expectedFocusMins) * 100)) : 0;

        // Calculate Consistency Score: inverse of variance in daily task completions (standard deviation)
        $dailyCompletions = DB::fetchAll(
            "SELECT DATE(completed_at) as date, COUNT(*) as count 
             FROM tasks 
             WHERE user_id = ? AND status = 'completed' AND completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(completed_at)",
            [$userId, $days]
        );
        
        $counts = array_column($dailyCompletions, 'count');
        $consistencyScore = 75; // Baseline default
        if (count($counts) > 1) {
            // Mean
            $mean = array_sum($counts) / count($counts);
            // Variance
            $variance = 0.0;
            foreach ($counts as $c) {
                $variance += pow($c - $mean, 2);
            }
            $variance = $variance / count($counts);
            $stdDev = sqrt($variance);
            // Smaller standard dev means higher consistency (tasks spread evenly instead of bursts)
            $consistencyScore = max(0, min(100, round(100 - ($stdDev * 15))));
        }

        // Calculate Health Score: Habit completion rates in health category
        $healthHabits = DB::fetch(
            "SELECT 
             COUNT(hl.id) as total,
             SUM(CASE WHEN hl.status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM habit_logs hl
             INNER JOIN habits h ON hl.habit_id = h.id
             INNER JOIN categories c ON h.category_id = c.id
             WHERE h.user_id = ? AND c.name = 'Health' AND hl.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            [$userId, $days]
        );
        $healthTotal = (int)($healthHabits['total'] ?? 0);
        $healthCompleted = (int)($healthHabits['completed'] ?? 0);
        $healthScore = $healthTotal > 0 ? (int)round(($healthCompleted / $healthTotal) * 100) : 80;

        return [
            'overall_score' => $overallScore,
            'task_rate' => round($taskRate),
            'habit_rate' => round($habitRate),
            'goal_rate' => round($goalRate),
            'focus_score' => $focusScore,
            'consistency_score' => $consistencyScore,
            'health_score' => $healthScore
        ];
    }

    /**
     * Fetches Activity heatmap count per day for GitHub-like Contribution Grid
     */
    public function getGithubStyleHeatmapData(int $userId): array {
        // Collects combined count of tasks completed, habits logged as completed, and journals written.
        $sql = "SELECT activity_date, SUM(count) as activity_count FROM (
                    SELECT DATE(completed_at) as activity_date, COUNT(*) as count 
                    FROM tasks 
                    WHERE user_id = ? AND status = 'completed' AND completed_at IS NOT NULL
                    GROUP BY DATE(completed_at)
                    
                    UNION ALL
                    
                    SELECT date as activity_date, COUNT(*) as count 
                    FROM habit_logs hl
                    INNER JOIN habits h ON hl.habit_id = h.id
                    WHERE h.user_id = ? AND hl.status = 'completed'
                    GROUP BY date
                    
                    UNION ALL
                    
                    SELECT date as activity_date, COUNT(*) as count 
                    FROM journal 
                    WHERE user_id = ?
                    GROUP BY date
                ) AS combined
                WHERE activity_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                GROUP BY activity_date
                ORDER BY activity_date ASC";
                
        $results = DB::fetchAll($sql, [$userId, $userId, $userId]);
        
        $heatmap = [];
        foreach ($results as $row) {
            if ($row['activity_date']) {
                $heatmap[$row['activity_date']] = (int)$row['activity_count'];
            }
        }
        return $heatmap;
    }

    /**
     * Category Distribution statistics
     */
    public function getCategoryDistribution(int $userId): array {
        return DB::fetchAll(
            "SELECT c.name, c.color, COUNT(t.id) as task_count 
             FROM tasks t
             INNER JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.status != 'archived'
             GROUP BY c.id
             ORDER BY task_count DESC",
            [$userId]
        );
    }

    /**
     * Priority Distribution statistics
     */
    public function getPriorityDistribution(int $userId): array {
        return DB::fetchAll(
            "SELECT priority, COUNT(*) as count 
             FROM tasks 
             WHERE user_id = ? AND status != 'archived'
             GROUP BY priority
             ORDER BY FIELD(priority, 'low', 'medium', 'high', 'critical')",
            [$userId]
        );
    }

    /**
     * Deadline performance calculations
     */
    public function getDeadlinePerformance(int $userId): array {
        $data = DB::fetch(
            "SELECT 
             COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
             COUNT(CASE WHEN status = 'completed' AND (completed_at <= due_date OR due_date IS NULL) THEN 1 END) as on_time,
             COUNT(CASE WHEN status = 'completed' AND completed_at > due_date AND due_date IS NOT NULL THEN 1 END) as late,
             COUNT(CASE WHEN status = 'missed' OR (status = 'pending' AND due_date < NOW()) THEN 1 END) as missed
             FROM tasks WHERE user_id = ?",
            [$userId]
        );

        $completed = (int)($data['completed_tasks'] ?? 0);
        $onTime = (int)($data['on_time'] ?? 0);
        $late = (int)($data['late'] ?? 0);
        $missed = (int)($data['missed'] ?? 0);

        return [
            'completed' => $completed,
            'on_time' => $onTime,
            'on_time_percent' => $completed > 0 ? round(($onTime / $completed) * 100) : 0,
            'late' => $late,
            'late_percent' => $completed > 0 ? round(($late / $completed) * 100) : 0,
            'missed' => $missed
        ];
    }

    /**
     * Calculated Study and Focus Hours logs comparison
     */
    public function getFocusTimeTrends(int $userId, int $days = 7): array {
        return DB::fetchAll(
            "SELECT DATE(created_at) as date, SUM(duration_minutes) as focus_minutes
             FROM pomodoro_logs 
             WHERE user_id = ? AND type = 'focus' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            [$userId, $days]
        );
    }

    /**
     * Compute Rolling Productivity Averages (7-day and 30-day moving rates)
     */
    public function getRollingAverages(int $userId, int $days = 30): array {
        // Collects count of daily completed tasks
        $completions = DB::fetchAll(
            "SELECT DATE(completed_at) as date, COUNT(*) as count 
             FROM tasks 
             WHERE user_id = ? AND status = 'completed' AND completed_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(completed_at)
             ORDER BY date ASC",
            [$userId, $days]
        );

        $completionsMap = [];
        foreach ($completions as $row) {
            $completionsMap[$row['date']] = (int)$row['count'];
        }

        $results = [];
        $tempList = [];

        // Build continuous array of days to compute accurate rolling averages
        for ($i = $days; $i >= 0; $i--) {
            $dateStr = date('Y-m-d', strtotime("-$i days"));
            $count = $completionsMap[$dateStr] ?? 0;
            $tempList[] = $count;

            // Keep array size to rolling period max
            if (count($tempList) > 7) {
                array_shift($tempList);
            }
            $rollingAvg7 = count($tempList) > 0 ? round(array_sum($tempList) / count($tempList), 2) : 0.0;

            $results[] = [
                'date' => $dateStr,
                'completed_count' => $count,
                'rolling_avg_7d' => $rollingAvg7
            ];
        }

        return $results;
    }

    /**
     * Task completion efficiency: Estimated vs Actual Time
     */
    public function getTaskEfficiency(int $userId): array {
        $data = DB::fetch(
            "SELECT 
             SUM(estimated_time) as est_sum, 
             SUM(actual_time) as act_sum,
             COUNT(*) as total_tasks
             FROM tasks 
             WHERE user_id = ? AND status = 'completed' AND estimated_time > 0 AND actual_time > 0",
            [$userId]
        );

        $est = (int)($data['est_sum'] ?? 0);
        $act = (int)($data['act_sum'] ?? 0);
        
        $efficiencyScore = 100; // default perfect score
        if ($est > 0 && $act > 0) {
            // If actual matches estimated or is less, score remains 100.
            // If actual is greater than estimated, score decreases proportionally.
            $efficiencyScore = (int)max(10, min(100, round(($est / $act) * 100)));
        }

        return [
            'estimated_total_minutes' => $est,
            'actual_total_minutes' => $act,
            'efficiency_score' => $efficiencyScore
        ];
    }

    /**
     * Extracts calculated highlights (Real calculated metrics for highlights panel)
     */
    public function getAnalyticsInsights(int $userId): array {
        // 1. Most productive weekday
        $dayQuery = DB::fetch(
            "SELECT DAYNAME(completed_at) as day_name, COUNT(*) as count 
             FROM tasks 
             WHERE user_id = ? AND status = 'completed' AND completed_at IS NOT NULL
             GROUP BY DAYNAME(completed_at)
             ORDER BY count DESC LIMIT 1",
            [$userId]
        );
        $mostProductiveDay = $dayQuery['day_name'] ?? 'None yet';

        // 2. Average task completion time
        $timeQuery = DB::fetch(
            "SELECT AVG(actual_time) as avg_time 
             FROM tasks 
             WHERE user_id = ? AND status = 'completed' AND actual_time > 0",
            [$userId]
        );
        $avgCompletionMins = round((float)($timeQuery['avg_time'] ?? 0));

        // 3. Procrastination warning: count of tasks modified / completed past 8 PM
        $procrastinationQuery = DB::fetch(
            "SELECT COUNT(*) as count 
             FROM tasks 
             WHERE user_id = ? AND status = 'completed' AND HOUR(completed_at) >= 20",
            [$userId]
        );
        $lateCompletionsCount = (int)($procrastinationQuery['count'] ?? 0);

        // 4. Category-specific completions (Identify weak category)
        $weakQuery = DB::fetch(
            "SELECT c.name, 
             SUM(CASE WHEN t.status = 'missed' THEN 1 ELSE 0 END) as missed_count 
             FROM tasks t
             INNER JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ?
             GROUP BY c.id
             ORDER BY missed_count DESC LIMIT 1",
            [$userId]
        );
        $weakCategory = $weakQuery['name'] ?? 'None';

        return [
            'most_productive_day' => $mostProductiveDay,
            'average_task_completion_minutes' => $avgCompletionMins,
            'late_night_completions' => $lateCompletionsCount,
            'weak_category' => $weakCategory
        ];
    }
}
