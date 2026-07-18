<?php
/**
 * Cron Task: Email Queue Dispatcher (Recommended: run every 5 minutes)
 */

declare(strict_types=1);

$is_cli = (php_sapi_name() === 'cli');
$key = $_GET['key'] ?? '';
$configured_key = 'aether_cron_secret_123';

if (!$is_cli && $key !== $configured_key) {
    http_response_code(403);
    die("Unauthorized access to Cron Runner.");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = DB::getConnection();

    // Fetch up to 10 pending emails
    $emails = DB::fetchAll(
        "SELECT * FROM email_queue 
         WHERE status = 'pending' AND attempts < 3 AND scheduled_at <= NOW()
         ORDER BY id ASC LIMIT 10"
    );

    if (empty($emails)) {
        echo "Email queue is empty. No actions required.\n";
        exit(0);
    }

    $sentCount = 0;
    $failedCount = 0;

    foreach ($emails as $email) {
        $emailId = (int)$email['id'];
        $recipient = $email['recipient'];
        $subject = $email['subject'];
        $body = $email['body'];
        $attempts = (int)$email['attempts'] + 1;

        // Increment attempts count immediately to prevent race conditions
        DB::query("UPDATE email_queue SET attempts = ? WHERE id = ?", [$attempts, $emailId]);

        // Attempt dispatching
        $success = sendEmail($recipient, $subject, $body);

        if ($success) {
            DB::query("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?", [$emailId]);
            $sentCount++;
        } else {
            $newStatus = ($attempts >= 3) ? 'failed' : 'pending';
            DB::query("UPDATE email_queue SET status = ? WHERE id = ?", [$newStatus, $emailId]);
            $failedCount++;
        }
    }

    echo "Queue Dispatch Run Finished. Sent: {$sentCount}, Failed/Postponed: {$failedCount}.\n";

} catch (Exception $e) {
    error_log("Email queue dispatcher Cron failure: " . $e->getMessage());
    echo "Dispatcher error: " . $e->getMessage() . "\n";
    exit(1);
}
