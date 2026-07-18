<?php
/**
 * AJAX In-App & Browser Notifications API
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/helpers.php';

// Access protection
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated access.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Poll unread notifications
    try {
        $unread = DB::fetchAll(
            "SELECT id, title, message, type, created_at 
             FROM notifications 
             WHERE user_id = ? AND is_read = 0 AND scheduled_at <= NOW()
             ORDER BY created_at DESC LIMIT 15",
            [$userId]
        );
        
        echo json_encode([
            'success' => true,
            'notifications' => $unread
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $headers = getallheaders();
    $csrfToken = $_POST['csrf_token'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
    if (!validateCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF security token.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $notificationId = (int)($_POST['id'] ?? 0);
        
        try {
            if ($notificationId > 0) {
                // Mark single notification as read
                DB::query(
                    "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?",
                    [$notificationId, $userId]
                );
            } else {
                // Mark all notifications for user as read
                DB::query(
                    "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0",
                    [$userId]
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Notification status modified successfully.']);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unsupported action.']);
exit;
