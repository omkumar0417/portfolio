<?php
/**
 * Pomodoro Focus Timer Model
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Pomodoro extends BaseModel {
    
    public function logSession(int $userId, int $duration, ?int $taskId, string $type): string {
        return DB::insert(
            "INSERT INTO pomodoro_logs (user_id, duration_minutes, task_id, type) VALUES (?, ?, ?, ?)",
            [$userId, $duration, $taskId ?: null, $type]
        );
    }

    public function getTodayFocusMinutes(int $userId): int {
        $result = DB::fetch(
            "SELECT SUM(duration_minutes) as total FROM pomodoro_logs 
             WHERE user_id = ? AND type = 'focus' AND DATE(created_at) = CURDATE()",
            [$userId]
        );
        return (int)($result['total'] ?? 0);
    }

    public function getRecentLogs(int $userId, int $limit = 10): array {
        return DB::fetchAll(
            "SELECT p.*, t.title as task_title 
             FROM pomodoro_logs p
             LEFT JOIN tasks t ON p.task_id = t.id
             WHERE p.user_id = ? 
             ORDER BY p.created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }
}
