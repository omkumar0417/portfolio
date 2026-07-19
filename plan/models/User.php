<?php
/**
 * User Model
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    
    public function findByEmail(string $email): ?array {
        return DB::fetch("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public function findById(int $id): ?array {
        return DB::fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public function create(string $name, string $email, string $passwordHash, ?string $verificationToken = null): string {
        $userId = DB::insert(
            "INSERT INTO users (name, email, password_hash, verification_token, is_verified) VALUES (?, ?, ?, ?, ?)",
            [$name, $email, $passwordHash, $verificationToken, $verificationToken ? 0 : 1]
        );
        
        // Initialize default user settings as well
        DB::insert(
            "INSERT INTO settings (user_id) VALUES (?)",
            [(int)$userId]
        );
        
        // Log audit event
        $this->logActivity((int)$userId, 'USER_SIGNUP', 'Completed registration process.', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        return $userId;
    }

    public function verifyEmail(string $token): bool {
        $user = DB::fetch("SELECT id FROM users WHERE verification_token = ?", [$token]);
        if ($user) {
            DB::query("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?", [$user['id']]);
            $this->logActivity((int)$user['id'], 'EMAIL_VERIFICATION', 'Email successfully verified.', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            return true;
        }
        return false;
    }

    public function setResetToken(int $id, string $token, string $expires): void {
        DB::query("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?", [$token, $expires, $id]);
    }

    public function resetPassword(string $token, string $passwordHash): bool {
        $user = DB::fetch("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()", [$token]);
        if ($user) {
            DB::query("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?", [$passwordHash, $user['id']]);
            $this->logActivity((int)$user['id'], 'PASSWORD_RESET', 'Password successfully reset.', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            return true;
        }
        return false;
    }

    public function updateProfile(int $id, array $data): bool {
        $sql = "UPDATE users SET 
                name = ?, 
                timezone = ?, 
                country = ?, 
                language = ?, 
                occupation = ?, 
                birthday = ?, 
                bio = ? 
                WHERE id = ?";
        
        DB::query($sql, [
            $data['name'],
            $data['timezone'],
            $data['country'],
            $data['language'],
            $data['occupation'],
            $data['birthday'] ?: null,
            $data['bio'],
            $id
        ]);
        
        $this->logActivity($id, 'PROFILE_UPDATE', 'Updated user profile metadata details.', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        return true;
    }

    public function updateAvatar(int $id, string $avatarFileName): void {
        DB::query("UPDATE users SET avatar = ? WHERE id = ?", [$avatarFileName, $id]);
        $this->logActivity($id, 'AVATAR_UPDATE', "Uploaded new profile photo: $avatarFileName.", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function updatePassword(int $id, string $passwordHash): void {
        DB::query("UPDATE users SET password_hash = ? WHERE id = ?", [$passwordHash, $id]);
        $this->logActivity($id, 'PASSWORD_CHANGE', 'Password changed from settings panel.', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function deleteAccount(int $id): void {
        // Log event before cascade deletion deletes record
        $this->logActivity($id, 'ACCOUNT_DELETION', 'User terminated account and deleted all database records.', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        DB::query("DELETE FROM users WHERE id = ?", [$id]);
    }

    // Logging helpers
    public function logActivity(?int $userId, string $action, string $description, string $ipAddress): void {
        DB::insert(
            "INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)",
            [$userId, $action, $description, $ipAddress]
        );
    }

    public function logLogin(int $userId, string $ipAddress, string $userAgent, string $status): void {
        DB::insert(
            "INSERT INTO login_history (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, ?)",
            [$userId, $ipAddress, $userAgent, $status]
        );
    }
}
