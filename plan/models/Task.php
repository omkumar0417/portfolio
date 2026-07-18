<?php
/**
 * Task Model
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Task extends BaseModel {
    
    public function findById(int $taskId, int $userId): ?array {
        return DB::fetch("SELECT * FROM tasks WHERE id = ? AND user_id = ?", [$taskId, $userId]);
    }

    public function getTasksFiltered(int $userId, array $filters = []): array {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color, c.icon as category_icon,
                (SELECT COUNT(*) FROM subtasks WHERE task_id = t.id) as total_subtasks,
                (SELECT COUNT(*) FROM subtasks WHERE task_id = t.id AND is_completed = 1) as completed_subtasks
                FROM tasks t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.status != 'archived'";
        
        $params = [$userId];

        // Search Term (Global Search)
        if (!empty($filters['search'])) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR t.location LIKE ? OR t.notes LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Priority
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }

        // Status
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }

        // Category
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        // Difficulty
        if (!empty($filters['difficulty'])) {
            $sql .= " AND t.difficulty = ?";
            $params[] = $filters['difficulty'];
        }

        // Specific Due Date
        if (!empty($filters['due_date'])) {
            $sql .= " AND DATE(t.due_date) = ?";
            $params[] = $filters['due_date'];
        }

        // Range filters
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $sql .= " AND DATE(t.due_date) BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }

        $sql .= " ORDER BY t.due_date ASC, t.priority DESC";
        return DB::fetchAll($sql, $params);
    }

    public function create(array $data): string {
        $sql = "INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date, estimated_time, reminder_time, repeat_type, repeat_custom, emoji, icon, difficulty, location, notes, progress_percent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return DB::insert($sql, [
            $data['user_id'],
            $data['category_id'] ?: null,
            $data['title'],
            $data['description'] ?? null,
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'pending',
            $data['due_date'] ?: null,
            (int)($data['estimated_time'] ?? 0),
            $data['reminder_time'] ?: null,
            $data['repeat_type'] ?? 'none',
            $data['repeat_custom'] ?? null,
            $data['emoji'] ?? null,
            $data['icon'] ?? null,
            $data['difficulty'] ?? 'medium',
            $data['location'] ?? null,
            $data['notes'] ?? null,
            (int)($data['progress_percent'] ?? 0)
        ]);
    }

    public function update(int $taskId, int $userId, array $data): bool {
        $sql = "UPDATE tasks SET 
                category_id = ?, 
                title = ?, 
                description = ?, 
                priority = ?, 
                status = ?, 
                due_date = ?, 
                estimated_time = ?, 
                actual_time = ?,
                progress_percent = ?, 
                reminder_time = ?, 
                repeat_type = ?, 
                repeat_custom = ?, 
                emoji = ?, 
                icon = ?, 
                difficulty = ?, 
                location = ?, 
                notes = ?,
                completed_at = ?
                WHERE id = ? AND user_id = ?";
        
        $completedAt = null;
        if (($data['status'] ?? '') === 'completed') {
            $completedAt = date('Y-m-d H:i:s');
        }

        DB::query($sql, [
            $data['category_id'] ?: null,
            $data['title'],
            $data['description'] ?? null,
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'pending',
            $data['due_date'] ?: null,
            (int)($data['estimated_time'] ?? 0),
            (int)($data['actual_time'] ?? 0),
            (int)($data['progress_percent'] ?? 0),
            $data['reminder_time'] ?: null,
            $data['repeat_type'] ?? 'none',
            $data['repeat_custom'] ?? null,
            $data['emoji'] ?? null,
            $data['icon'] ?? null,
            $data['difficulty'] ?? 'medium',
            $data['location'] ?? null,
            $data['notes'] ?? null,
            $completedAt,
            $taskId,
            $userId
        ]);
        
        return true;
    }

    public function delete(int $taskId, int $userId): bool {
        DB::query("DELETE FROM tasks WHERE id = ? AND user_id = ?", [$taskId, $userId]);
        return true;
    }

    public function updateStatus(int $taskId, int $userId, string $status): bool {
        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
        $progress = ($status === 'completed') ? 100 : 0;
        
        DB::query("UPDATE tasks SET status = ?, completed_at = ?, progress_percent = ? WHERE id = ? AND user_id = ?", [
            $status, $completedAt, $progress, $taskId, $userId
        ]);
        return true;
    }

    // --- Subtasks (Checklists) ---
    public function getSubtasks(int $taskId): array {
        return DB::fetchAll("SELECT * FROM subtasks WHERE task_id = ? ORDER BY id ASC", [$taskId]);
    }

    public function createSubtask(int $taskId, string $title): string {
        return DB::insert("INSERT INTO subtasks (task_id, title) VALUES (?, ?)", [$taskId, $title]);
    }

    public function toggleSubtask(int $subtaskId, int $taskId, int $isCompleted): bool {
        DB::query("UPDATE subtasks SET is_completed = ? WHERE id = ? AND task_id = ?", [$isCompleted, $subtaskId, $taskId]);
        
        // Auto-calculate parent task progress percent based on checklist completions
        $subtasks = $this->getSubtasks($taskId);
        if (!empty($subtasks)) {
            $total = count($subtasks);
            $done = count(array_filter($subtasks, fn($s) => $s['is_completed'] == 1));
            $pct = (int)round(($done / $total) * 100);
            
            DB::query("UPDATE tasks SET progress_percent = ? WHERE id = ?", [$pct, $taskId]);
        }
        return true;
    }

    public function deleteSubtask(int $subtaskId, int $taskId): bool {
        DB::query("DELETE FROM subtasks WHERE id = ? AND task_id = ?", [$subtaskId, $taskId]);
        return true;
    }

    // --- Dashboard Specific Widgets SQL ---
    public function getTodayTasks(int $userId): array {
        return DB::fetchAll(
            "SELECT t.*, c.name as category_name, c.color as category_color 
             FROM tasks t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND DATE(t.due_date) = CURDATE() AND t.status != 'archived' AND t.status != 'completed'
             ORDER BY t.priority DESC, t.due_date ASC",
            [$userId]
        );
    }

    public function getUpcomingDeadlines(int $userId, int $hours = 48): array {
        return DB::fetchAll(
            "SELECT t.*, c.name as category_name, c.color as category_color 
             FROM tasks t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.status NOT IN ('completed', 'cancelled', 'archived') 
             AND t.due_date >= NOW() AND t.due_date <= DATE_ADD(NOW(), INTERVAL ? HOUR)
             ORDER BY t.due_date ASC LIMIT 5",
            [$userId, $hours]
        );
    }

    public function getRecentlyCompleted(int $userId): array {
        return DB::fetchAll(
            "SELECT t.*, c.name as category_name, c.color as category_color 
             FROM tasks t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.status = 'completed' AND t.completed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY t.completed_at DESC LIMIT 5",
            [$userId]
        );
    }

    public function getLongestRunningPendingTask(int $userId): ?array {
        return DB::fetch(
            "SELECT t.*, DATEDIFF(NOW(), t.created_at) as running_days
             FROM tasks t
             WHERE t.user_id = ? AND t.status IN ('pending', 'in_progress')
             ORDER BY t.created_at ASC LIMIT 1",
            [$userId]
        );
    }

    // --- Attachments ---
    public function addAttachment(int $userId, int $taskId, string $filename, string $filepath, string $filetype, int $filesize): string {
        return DB::insert(
            "INSERT INTO attachments (user_id, task_id, filename, filepath, filetype, filesize) VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $taskId, $filename, $filepath, $filetype, $filesize]
        );
    }

    public function getAttachments(int $taskId): array {
        return DB::fetchAll("SELECT * FROM attachments WHERE task_id = ? ORDER BY uploaded_at DESC", [$taskId]);
    }
}
