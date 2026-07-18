<?php
/**
 * Note Model
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Note extends BaseModel {
    
    public function findById(int $noteId, int $userId): ?array {
        return DB::fetch("SELECT n.*, f.name as folder_name FROM notes n LEFT JOIN folders f ON n.folder_id = f.id WHERE n.id = ? AND n.user_id = ?", [$noteId, $userId]);
    }

    public function getAll(int $userId, array $filters = []): array {
        $sql = "SELECT n.*, f.name as folder_name 
                FROM notes n 
                LEFT JOIN folders f ON n.folder_id = f.id 
                WHERE n.user_id = ?";
        
        $params = [$userId];

        if (!empty($filters['folder_id'])) {
            $sql .= " AND n.folder_id = ?";
            $params[] = (int)$filters['folder_id'];
        }

        if (isset($filters['is_pinned'])) {
            $sql .= " AND n.is_pinned = ?";
            $params[] = (int)$filters['is_pinned'];
        }

        if (isset($filters['is_archived'])) {
            $sql .= " AND n.is_archived = ?";
            $params[] = (int)$filters['is_archived'];
        } else {
            $sql .= " AND n.is_archived = 0"; // Exclude archived by default
        }

        if (isset($filters['is_favorite'])) {
            $sql .= " AND n.is_favorite = ?";
            $params[] = (int)$filters['is_favorite'];
        }

        if (!empty($filters['tag'])) {
            $sql .= " AND FIND_IN_SET(?, n.tags)";
            $params[] = $filters['tag'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (n.title LIKE ? OR n.content LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY n.is_pinned DESC, n.updated_at DESC";
        return DB::fetchAll($sql, $params);
    }

    public function create(array $data): string {
        $sql = "INSERT INTO notes (user_id, folder_id, title, content, is_pinned, is_favorite, tags)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        return DB::insert($sql, [
            $data['user_id'],
            $data['folder_id'] ?: null,
            $data['title'],
            $data['content'] ?? null,
            (int)($data['is_pinned'] ?? 0),
            (int)($data['is_favorite'] ?? 0),
            $data['tags'] ?? null
        ]);
    }

    public function update(int $noteId, int $userId, array $data): bool {
        $sql = "UPDATE notes SET 
                folder_id = ?, 
                title = ?, 
                content = ?, 
                is_pinned = ?, 
                is_favorite = ?, 
                tags = ?
                WHERE id = ? AND user_id = ?";
        DB::query($sql, [
            $data['folder_id'] ?: null,
            $data['title'],
            $data['content'] ?? null,
            (int)($data['is_pinned'] ?? 0),
            (int)($data['is_favorite'] ?? 0),
            $data['tags'] ?? null,
            $noteId,
            $userId
        ]);
        return true;
    }

    public function delete(int $noteId, int $userId): bool {
        DB::query("DELETE FROM notes WHERE id = ? AND user_id = ?", [$noteId, $userId]);
        return true;
    }

    public function togglePin(int $noteId, int $userId): bool {
        DB::query("UPDATE notes SET is_pinned = NOT is_pinned WHERE id = ? AND user_id = ?", [$noteId, $userId]);
        return true;
    }

    public function toggleFavorite(int $noteId, int $userId): bool {
        DB::query("UPDATE notes SET is_favorite = NOT is_favorite WHERE id = ? AND user_id = ?", [$noteId, $userId]);
        return true;
    }

    public function toggleArchive(int $noteId, int $userId): bool {
        DB::query("UPDATE notes SET is_archived = NOT is_archived WHERE id = ? AND user_id = ?", [$noteId, $userId]);
        return true;
    }

    // --- Folders ---
    public function getFolders(int $userId): array {
        return DB::fetchAll("SELECT * FROM folders WHERE user_id = ? ORDER BY name ASC", [$userId]);
    }

    public function createFolder(int $userId, string $name): string {
        return DB::insert("INSERT INTO folders (user_id, name) VALUES (?, ?)", [$userId, $name]);
    }

    public function deleteFolder(int $folderId, int $userId): bool {
        DB::query("DELETE FROM folders WHERE id = ? AND user_id = ?", [$folderId, $userId]);
        return true;
    }
}
