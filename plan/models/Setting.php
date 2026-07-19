<?php
/**
 * User Settings Model
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Setting extends BaseModel {
    
    public function getByUserId(int $userId): array {
        $settings = DB::fetch("SELECT * FROM settings WHERE user_id = ?", [$userId]);
        if (!$settings) {
            // Create default settings if not exists
            DB::insert("INSERT INTO settings (user_id) VALUES (?)", [$userId]);
            $settings = DB::fetch("SELECT * FROM settings WHERE user_id = ?", [$userId]);
        }
        return $settings;
    }

    public function updateSettings(int $userId, array $data): bool {
        $sql = "UPDATE settings SET 
                theme = ?, 
                accent_color = ?, 
                card_radius = ?, 
                compact_mode = ?, 
                sidebar_style = ?, 
                wallpaper = ?, 
                font_size = ?, 
                dashboard_layout = ?, 
                notification_email = ?, 
                notification_browser = ?,
                auto_backup = ?,
                backup_frequency = ?
                WHERE user_id = ?";
        
        DB::query($sql, [
            $data['theme'] ?? 'dark',
            $data['accent_color'] ?? 'indigo',
            (int)($data['card_radius'] ?? 12),
            (int)($data['compact_mode'] ?? 0),
            $data['sidebar_style'] ?? 'glassmorphic',
            $data['wallpaper'] ?? 'default',
            $data['font_size'] ?? 'medium',
            $data['dashboard_layout'] ?? 'default',
            (int)($data['notification_email'] ?? 1),
            (int)($data['notification_browser'] ?? 1),
            (int)($data['auto_backup'] ?? 1),
            $data['backup_frequency'] ?? 'weekly',
            $userId
        ]);
        
        // Update Session cache values immediately
        $_SESSION['theme'] = $data['theme'] ?? 'dark';
        $_SESSION['accent_color'] = $data['accent_color'] ?? 'indigo';
        $_SESSION['card_radius'] = (int)($data['card_radius'] ?? 12);
        $_SESSION['compact_mode'] = (int)($data['compact_mode'] ?? 0);
        $_SESSION['sidebar_style'] = $data['sidebar_style'] ?? 'glassmorphic';
        $_SESSION['wallpaper'] = $data['wallpaper'] ?? 'default';
        $_SESSION['font_size'] = $data['font_size'] ?? 'medium';
        
        return true;
    }
}
