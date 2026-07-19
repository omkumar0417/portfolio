<?php
/**
 * Habit Model
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Habit extends BaseModel {
    
    public function findById(int $habitId, int $userId): ?array {
        return DB::fetch("SELECT * FROM habits WHERE id = ? AND user_id = ?", [$habitId, $userId]);
    }

    public function getAll(int $userId): array {
        return DB::fetchAll(
            "SELECT h.*, c.name as category_name, c.color as category_color, c.icon as category_icon
             FROM habits h
             LEFT JOIN categories c ON h.category_id = c.id
             WHERE h.user_id = ?
             ORDER BY h.created_at DESC",
            [$userId]
        );
    }

    public function create(array $data): string {
        $sql = "INSERT INTO habits (user_id, name, description, category_id, color, icon, frequency, custom_days, repeat_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return DB::insert($sql, [
            $data['user_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['category_id'] ?: null,
            $data['color'] ?? '#6366f1',
            $data['icon'] ?? 'fa-circle',
            $data['frequency'] ?? 'daily',
            $data['custom_days'] ?? null,
            $data['repeat_time'] ?? null
        ]);
    }

    public function update(int $habitId, int $userId, array $data): bool {
        $sql = "UPDATE habits SET 
                name = ?, 
                description = ?, 
                category_id = ?, 
                color = ?, 
                icon = ?, 
                frequency = ?, 
                custom_days = ?, 
                repeat_time = ?
                WHERE id = ? AND user_id = ?";
        DB::query($sql, [
            $data['name'],
            $data['description'] ?? null,
            $data['category_id'] ?: null,
            $data['color'] ?? '#6366f1',
            $data['icon'] ?? 'fa-circle',
            $data['frequency'] ?? 'daily',
            $data['custom_days'] ?? null,
            $data['repeat_time'] ?? null,
            $habitId,
            $userId
        ]);
        return true;
    }

    public function delete(int $habitId, int $userId): bool {
        DB::query("DELETE FROM habits WHERE id = ? AND user_id = ?", [$habitId, $userId]);
        return true;
    }

    // --- Daily Logging ---
    public function logStatus(int $habitId, string $date, string $status, ?string $notes = null): bool {
        // Since there is a unique key habit_id + date, we use ON DUPLICATE KEY UPDATE
        $sql = "INSERT INTO habit_logs (habit_id, date, status, notes) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)";
        DB::query($sql, [$habitId, $date, $status, $notes]);
        return true;
    }

    public function getLogs(int $habitId): array {
        return DB::fetchAll(
            "SELECT date, status, notes FROM habit_logs WHERE habit_id = ? ORDER BY date ASC",
            [$habitId]
        );
    }

    /**
     * Aggregates logs for all user habits on a specific date
     */
    public function getHabitLogsByDate(int $userId, string $date): array {
        return DB::fetchAll(
            "SELECT hl.*, h.name, h.frequency, h.color, h.icon
             FROM habit_logs hl
             INNER JOIN habits h ON hl.habit_id = h.id
             WHERE h.user_id = ? AND hl.date = ?",
            [$userId, $date]
        );
    }

    /**
     * Compute streak statistics for a single habit
     */
    public function getStreakStats(int $habitId): array {
        $habit = DB::fetch("SELECT id, frequency FROM habits WHERE id = ?", [$habitId]);
        if (!$habit) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'success_rate'   => 0.0
            ];
        }

        $logs = $this->getLogs($habitId);
        return calculateHabitStreaks($logs, $habit['frequency']);
    }

    /**
     * Aggregated statistics across all habits for a user
     */
    public function getGlobalStats(int $userId): array {
        $habits = $this->getAll($userId);
        if (empty($habits)) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'success_rate'   => 0.0,
                'today_completion_percent' => 0
            ];
        }

        $longest = 0;
        $activeMax = 0;
        $totalSuccessRateSum = 0;
        $habitsCount = count($habits);

        foreach ($habits as $h) {
            $stats = $this->getStreakStats((int)$h['id']);
            if ($stats['longest_streak'] > $longest) {
                $longest = $stats['longest_streak'];
            }
            if ($stats['current_streak'] > $activeMax) {
                $activeMax = $stats['current_streak'];
            }
            $totalSuccessRateSum += $stats['success_rate'];
        }

        // Today completion percentage calculation
        $today = date('Y-m-d');
        $todayLogs = DB::fetchAll(
            "SELECT status FROM habit_logs hl 
             INNER JOIN habits h ON hl.habit_id = h.id 
             WHERE h.user_id = ? AND hl.date = ?",
            [$userId, $today]
        );

        $todayDone = count(array_filter($todayLogs, fn($l) => $l['status'] === 'completed'));
        $todayCompletion = $habitsCount > 0 ? (int)round(($todayDone / $habitsCount) * 100) : 0;

        return [
            'current_streak' => $activeMax,
            'longest_streak' => $longest,
            'success_rate'   => $habitsCount > 0 ? round($totalSuccessRateSum / $habitsCount, 1) : 0.0,
            'today_completion_percent' => $todayCompletion
        ];
    }
}
