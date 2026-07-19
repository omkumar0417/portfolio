<?php
/**
 * API Endpoint: Safely purge the demo sandbox user and demo email records from the database.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$key = $_GET['key'] ?? '';
$configured_key = 'aether_cron_secret_123'; // Matches the secure key used in cron jobs

if ($key !== $configured_key) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access to Database Cleaner.']);
    exit;
}

try {
    $db = DB::getConnection();
    
    // 1. Delete user 'demo@aetherlife.com'
    // Foreign key constraints on tables (tasks, habits, folders, settings, etc.) are set to ON DELETE CASCADE
    // so deleting the user automatically cleans up all associated records.
    $stmt = $db->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute(['demo@aetherlife.com']);
    $userRows = $stmt->rowCount();

    // 2. Clean up email queue records associated with the demo email
    $stmt2 = $db->prepare("DELETE FROM email_queue WHERE recipient = ?");
    $stmt2->execute(['demo@aetherlife.com']);
    $emailRows = $stmt2->rowCount();

    echo json_encode([
        'success' => true,
        'message' => 'Database Cleanup Successful.',
        'details' => [
            'removed_users' => $userRows,
            'deleted_emails' => $emailRows
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Cleanup Error: ' . $e->getMessage()]);
}
