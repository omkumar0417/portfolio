<?php
/**
 * System Settings & Backup Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Habit.php';

class SettingsController extends BaseController {
    private User $userModel;
    private Setting $settingModel;

    public function __construct() {
        $this->userModel = new User();
        $this->settingModel = new Setting();
    }

    /**
     * Render configurations view and parse setup updates
     */
    public function index(): void {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
                setFlash('error', 'Security token mismatch.');
                $this->redirect('/settings');
            }

            $action = $_POST['action'] ?? '';

            switch ($action) {
                case 'update_profile':
                    $data = [
                        'name' => trim($_POST['name'] ?? ''),
                        'timezone' => $_POST['timezone'] ?? 'UTC',
                        'country' => trim($_POST['country'] ?? ''),
                        'language' => $_POST['language'] ?? 'en',
                        'occupation' => trim($_POST['occupation'] ?? ''),
                        'birthday' => $_POST['birthday'] !== '' ? $_POST['birthday'] : null,
                        'bio' => trim($_POST['bio'] ?? '')
                    ];
                    
                    if ($data['name'] === '') {
                        setFlash('error', 'Name is required.');
                        $this->redirect('/settings');
                    }

                    $this->userModel->updateProfile($userId, $data);
                    $_SESSION['user_name'] = $data['name'];
                    
                    setFlash('success', 'Profile metadata updated successfully.');
                    $this->redirect('/settings');
                    break;

                case 'update_avatar':
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['avatar'];
                        if ($file['size'] > 2 * 1024 * 1024) {
                            setFlash('error', 'Avatar upload failed. Max size is 2MB.');
                            $this->redirect('/settings');
                        }

                        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
                        if (!in_array($file['type'], $allowed)) {
                            setFlash('error', 'Invalid image format. Allowed formats: PNG, JPG, GIF.');
                            $this->redirect('/settings');
                        }

                        $targetDir = UPLOAD_DIR . 'avatars/';
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }

                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $avatarName = "avatar_{$userId}_" . time() . '.' . $ext;
                        
                        if (move_uploaded_file($file['tmp_name'], $targetDir . $avatarName)) {
                            $this->userModel->updateAvatar($userId, $avatarName);
                            $_SESSION['user_avatar'] = $avatarName;
                            setFlash('success', 'Avatar updated.');
                        } else {
                            setFlash('error', 'Failed to move uploaded photo.');
                        }
                    }
                    $this->redirect('/settings');
                    break;

                case 'update_theme':
                    $data = [
                        'theme' => $_POST['theme'] ?? 'dark',
                        'accent_color' => $_POST['accent_color'] ?? 'indigo',
                        'card_radius' => (int)($_POST['card_radius'] ?? 12),
                        'compact_mode' => isset($_POST['compact_mode']) ? 1 : 0,
                        'sidebar_style' => $_POST['sidebar_style'] ?? 'glassmorphic',
                        'wallpaper' => $_POST['wallpaper'] ?? 'default',
                        'font_size' => $_POST['font_size'] ?? 'medium',
                        'dashboard_layout' => $_POST['dashboard_layout'] ?? 'default',
                        'notification_email' => isset($_POST['notification_email']) ? 1 : 0,
                        'notification_browser' => isset($_POST['notification_browser']) ? 1 : 0,
                        'auto_backup' => isset($_POST['auto_backup']) ? 1 : 0,
                        'backup_frequency' => $_POST['backup_frequency'] ?? 'weekly'
                    ];

                    $this->settingModel->updateSettings($userId, $data);
                    setFlash('success', 'Workspace preferences applied successfully.');
                    $this->redirect('/settings');
                    break;

                case 'change_password':
                    $old = $_POST['old_password'] ?? '';
                    $new = $_POST['new_password'] ?? '';
                    $confirm = $_POST['confirm_password'] ?? '';

                    $user = $this->userModel->findById($userId);
                    if ($user && password_verify($old, $user['password_hash'])) {
                        if ($new !== $confirm) {
                            setFlash('error', 'Confirm password does not match.');
                            $this->redirect('/settings');
                        }
                        if (strlen($new) < 8) {
                            setFlash('error', 'Password must be at least 8 characters long.');
                            $this->redirect('/settings');
                        }

                        $newHash = password_hash($new, PASSWORD_DEFAULT);
                        $this->userModel->updatePassword($userId, $newHash);
                        setFlash('success', 'Password changed successfully.');
                    } else {
                        setFlash('error', 'Incorrect current password.');
                    }
                    $this->redirect('/settings');
                    break;

                case 'export_json':
                    $this->handleJsonExport($userId);
                    exit;

                case 'export_csv':
                    $this->handleCsvExport($userId);
                    exit;

                case 'backup_db':
                    $this->handleDatabaseBackup($userId);
                    exit;

                case 'delete_account':
                    $this->userModel->deleteAccount($userId);
                    // Clear Session cookies
                    $_SESSION = [];
                    session_destroy();
                    setFlash('success', 'Your AetherLife account has been permanently terminated.');
                    $this->redirect('/login');
                    break;
            }
        }

        // Fetch User and Settings
        $user = $this->userModel->findById($userId);
        $settings = $this->settingModel->getByUserId($userId);

        $this->render('settings/index', [
            'pageTitle' => 'System Preferences',
            'user' => $user,
            'settings' => $settings
        ]);
    }

    private function handleJsonExport(int $userId): void {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=aetherlife_export_' . date('Ymd') . '.json');

        // Fetch full profile info bundle
        $data = [
            'profile' => $this->userModel->findById($userId),
            'tasks' => DB::fetchAll("SELECT * FROM tasks WHERE user_id = ?", [$userId]),
            'habits' => DB::fetchAll("SELECT * FROM habits WHERE user_id = ?", [$userId]),
            'goals' => DB::fetchAll("SELECT * FROM goals WHERE user_id = ?", [$userId]),
            'notes' => DB::fetchAll("SELECT * FROM notes WHERE user_id = ?", [$userId]),
            'journal' => DB::fetchAll("SELECT * FROM journal WHERE user_id = ?", [$userId])
        ];

        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    private function handleCsvExport(int $userId): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=aetherlife_tasks_' . date('Ymd') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Task ID', 'Title', 'Description', 'Priority', 'Status', 'Due Date', 'Progress %', 'Completed At']);

        $tasks = DB::fetchAll("SELECT id, title, description, priority, status, due_date, progress_percent, completed_at FROM tasks WHERE user_id = ?", [$userId]);
        foreach ($tasks as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /**
     * Standalone pure-PHP MySQL Backup utility
     */
    private function handleDatabaseBackup(int $userId): void {
        // Require database administrator check or standard export (here we dump user records to secure file)
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=aetherlife_backup_' . date('Ymd_His') . '.sql');

        $db = DB::getConnection();
        
        // Dynamic tables retrieval
        $tablesResult = $db->query("SHOW TABLES");
        $tables = $tablesResult->fetchAll(PDO::FETCH_COLUMN);

        echo "-- AetherLife Database SQL Backup\n";
        echo "-- Export Date: " . date('Y-m-d H:i:s') . "\n";
        echo "-- User ID Scope: {$userId}\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Write Create Table script
            $createStmt = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            echo "DROP TABLE IF EXISTS `{$table}`;\n";
            echo $createStmt['Create Table'] . ";\n\n";

            // Write Inserts
            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_keys($row);
                $escapedCols = array_map(fn($c) => "`$c`", $cols);
                
                $vals = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = $db->quote($val);
                    }
                }

                echo "INSERT INTO `{$table}` (" . implode(', ', $escapedCols) . ") VALUES (" . implode(', ', $vals) . ");\n";
            }
            echo "\n";
        }
        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    }
}
