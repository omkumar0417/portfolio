<?php
/**
 * Goal Model
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Goal extends BaseModel {
    
    public function findById(int $goalId, int $userId): ?array {
        return DB::fetch("SELECT * FROM goals WHERE id = ? AND user_id = ?", [$goalId, $userId]);
    }

    public function getAll(int $userId, array $filters = []): array {
        $sql = "SELECT * FROM goals WHERE user_id = ?";
        $params = [$userId];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $sql .= " AND type = ?";
            $params[] = $filters['type'];
        }

        $sql .= " ORDER BY deadline ASC";
        return DB::fetchAll($sql, $params);
    }

    public function create(array $data): string {
        $sql = "INSERT INTO goals (user_id, title, description, type, status, deadline, reward, progress_percent, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return DB::insert($sql, [
            $data['user_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['type'] ?? 'short_term',
            $data['status'] ?? 'pending',
            $data['deadline'] ?: null,
            $data['reward'] ?? null,
            (int)($data['progress_percent'] ?? 0),
            $data['notes'] ?? null
        ]);
    }

    public function update(int $goalId, int $userId, array $data): bool {
        $sql = "UPDATE goals SET 
                title = ?, 
                description = ?, 
                type = ?, 
                status = ?, 
                deadline = ?, 
                reward = ?, 
                progress_percent = ?, 
                notes = ?
                WHERE id = ? AND user_id = ?";
        DB::query($sql, [
            $data['title'],
            $data['description'] ?? null,
            $data['type'] ?? 'short_term',
            $data['status'] ?? 'pending',
            $data['deadline'] ?: null,
            $data['reward'] ?? null,
            (int)($data['progress_percent'] ?? 0),
            $data['notes'] ?? null,
            $goalId,
            $userId
        ]);
        return true;
    }

    public function delete(int $goalId, int $userId): bool {
        DB::query("DELETE FROM goals WHERE id = ? AND user_id = ?", [$goalId, $userId]);
        return true;
    }

    // --- Milestones ---
    public function getMilestones(int $goalId): array {
        return DB::fetchAll("SELECT * FROM goal_milestones WHERE goal_id = ? ORDER BY id ASC", [$goalId]);
    }

    public function createMilestone(int $goalId, string $title, ?string $deadline = null): string {
        $id = DB::insert(
            "INSERT INTO goal_milestones (goal_id, title, deadline) VALUES (?, ?, ?)",
            [$goalId, $title, $deadline ?: null]
        );
        $this->recalculateProgress($goalId);
        return $id;
    }

    public function toggleMilestone(int $milestoneId, int $goalId, int $isCompleted): bool {
        DB::query("UPDATE goal_milestones SET is_completed = ? WHERE id = ? AND goal_id = ?", [$isCompleted, $milestoneId, $goalId]);
        $this->recalculateProgress($goalId);
        return true;
    }

    public function deleteMilestone(int $milestoneId, int $goalId): bool {
        DB::query("DELETE FROM goal_milestones WHERE id = ? AND goal_id = ?", [$milestoneId, $goalId]);
        $this->recalculateProgress($goalId);
        return true;
    }

    private function recalculateProgress(int $goalId): void {
        $milestones = $this->getMilestones($goalId);
        if (empty($milestones)) {
            return;
        }
        $total = count($milestones);
        $completed = count(array_filter($milestones, fn($m) => $m['is_completed'] == 1));
        $progress = (int)round(($completed / $total) * 100);
        
        $status = 'in_progress';
        if ($progress === 100) {
            $status = 'completed';
        }
        
        DB::query("UPDATE goals SET progress_percent = ?, status = ? WHERE id = ?", [$progress, $status, $goalId]);
    }
}
