<?php
/**
 * Daily Journal Model
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Journal extends BaseModel {
    
    public function findByDate(int $userId, string $date): ?array {
        return DB::fetch("SELECT * FROM journal WHERE user_id = ? AND date = ?", [$userId, $date]);
    }

    public function save(int $userId, string $date, array $data): bool {
        $sql = "INSERT INTO journal (user_id, date, morning_journal, night_journal, mood, energy_level, productivity_score, gratitude, reflection, learning, problems, achievements)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                morning_journal = VALUES(morning_journal),
                night_journal = VALUES(night_journal),
                mood = VALUES(mood),
                energy_level = VALUES(energy_level),
                productivity_score = VALUES(productivity_score),
                gratitude = VALUES(gratitude),
                reflection = VALUES(reflection),
                learning = VALUES(learning),
                problems = VALUES(problems),
                achievements = VALUES(achievements)";
        
        DB::query($sql, [
            $userId,
            $date,
            $data['morning_journal'] ?? null,
            $data['night_journal'] ?? null,
            $data['mood'] ?? null,
            (int)($data['energy_level'] ?? 3),
            (int)($data['productivity_score'] ?? 3),
            $data['gratitude'] ?? null,
            $data['reflection'] ?? null,
            $data['learning'] ?? null,
            $data['problems'] ?? null,
            $data['achievements'] ?? null
        ]);
        return true;
    }

    public function getJournalHistory(int $userId, int $limit = 30): array {
        return DB::fetchAll(
            "SELECT * FROM journal WHERE user_id = ? ORDER BY date DESC LIMIT ?",
            [$userId, $limit]
        );
    }
}
