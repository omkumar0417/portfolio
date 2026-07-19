<?php
/**
 * Cron Task: Weekly Sunday Recap Report Generator
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

    $users = DB::fetchAll(
        "SELECT u.id, u.email, u.name, u.timezone 
         FROM users u
         INNER JOIN settings s ON u.id = s.user_id
         WHERE s.notification_email = 1 AND u.is_verified = 1"
    );

    $queuedCount = 0;
    foreach ($users as $user) {
        $userId = (int)$user['id'];
        $userName = $user['name'];
        $userEmail = $user['email'];
        $userTimezone = $user['timezone'] ?? 'UTC';

        // Match PHP date helpers with user's local timezone
        date_default_timezone_set($userTimezone);

        // Calculate and set the MySQL session connection offset to match user's local timezone
        try {
            $tz = new DateTimeZone($userTimezone);
            $transition = $tz->getTransitions(time(), time());
            $offsetSeconds = $transition[0]['offset'] ?? 0;
            $offsetPrefix = $offsetSeconds >= 0 ? '+' : '-';
            $offsetHours = floor(abs($offsetSeconds) / 3600);
            $offsetMins = floor((abs($offsetSeconds) % 3600) / 60);
            $offsetString = sprintf("%s%02d:%02d", $offsetPrefix, $offsetHours, $offsetMins);
            DB::query("SET time_zone = ?", [$offsetString]);
        } catch (Exception $tzEx) {
            DB::query("SET time_zone = '+00:00'");
        }

        // 1. Weekly Tasks stats
        $tasks = DB::fetch(
            "SELECT 
             COUNT(*) as total,
             SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
             SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed
             FROM tasks 
             WHERE user_id = ? AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            [$userId]
        );
        $total = (int)($tasks['total'] ?? 0);
        $completed = (int)($tasks['completed'] ?? 0);
        $missed = (int)($tasks['missed'] ?? 0);
        
        $completionRate = $total > 0 ? round(($completed / $total) * 100) : 0;

        // 2. Extract weekly achievements from journals
        $journals = DB::fetchAll(
            "SELECT date, achievements FROM journal 
             WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND achievements IS NOT NULL",
            [$userId]
        );
        
        $achievementsLi = '';
        if (empty($journals)) {
            $achievementsLi = "<li style='color:#94a3b8;'>No reflection entries logged in the journal this week.</li>";
        } else {
            foreach (array_slice($journals, 0, 5) as $j) {
                $dateFormatted = date('l', strtotime($j['date']));
                $achievementsLi .= "<li style='color:#f8fafc;margin-bottom:8px;'><strong>{$dateFormatted}</strong>: {$j['achievements']}</li>";
            }
        }

        // 3. Build HTML Weekly recap
        $emailBody = "
        <div style='background-color:#0b0f19;font-family:sans-serif;padding:30px;color:#f8fafc;border-radius:10px;max-width:600px;margin:0 auto;border:1px solid #1e293b;'>
            <div style='text-align:center;border-bottom:1px solid #1e293b;padding-bottom:15px;margin-bottom:20px;'>
                <h2 style='color:#8b5cf6;margin:0;letter-spacing:1px;'>AETHERLIFE PLANNER</h2>
                <p style='color:#94a3b8;font-size:12px;margin:5px 0 0 0;'>Weekly Sunday Recap Report</p>
            </div>
            
            <p style='font-size:16px;color:#f8fafc;'>Hello {$userName},</p>
            <p style='font-size:14px;color:#94a3b8;line-height:1.5;'>Congratulations on completing another week! Let's review your calculated productivity numbers for the past 7 days:</p>
            
            <div style='background:rgba(255,255,255,0.02);border:1px solid #1e293b;padding:15px;border-radius:8px;margin-bottom:20px;text-align:center;'>
                <span style='color:#94a3b8;font-size:12px;display:block;'>WEEKLY TASK COMPLETION</span>
                <h1 style='color:#8b5cf6;margin:5px 0;font-size:48px;font-weight:bold;'>{$completionRate}%</h1>
                <span style='color:#10b981;font-weight:semibold;'>{$completed} Completed</span> | <span style='color:#ef4444;font-weight:semibold;'>{$missed} Missed</span>
            </div>

            <div style='background:rgba(255,255,255,0.02);border:1px solid #1e293b;padding:15px;border-radius:8px;margin-bottom:20px;'>
                <h3 style='color:#8b5cf6;margin-top:0;margin-bottom:12px;'>🏆 Top Weekly Achievements</h3>
                <ul style='padding-left:20px;margin:0;line-height:1.4;'>
                    {$achievementsLi}
                </ul>
            </div>

            <div style='background:rgba(255,255,255,0.02);border:1px solid #1e293b;padding:15px;border-radius:8px;margin-bottom:20px;'>
                <h3 style='color:#f59e0b;margin-top:0;margin-bottom:10px;'>💡 Continuous Improvement</h3>
                <p style='color:#94a3b8;font-size:13px;margin:0;line-height:1.5;'>
                    " . ($completionRate >= 80 ? 'Excellent consistency! Keep up this high standard of chunking down tasks.' : 'Try scheduling focus time block slots on your calendar to tackle pending critical tasks.') . "
                </p>
            </div>

            <div style='text-align:center;border-top:1px solid #1e293b;padding-top:15px;margin-top:20px;font-size:11px;color:#64748b;'>
                You are receiving this recap because you turned on email summaries in settings.<br>
                <a href='" . APP_URL . "/settings' style='color:#8b5cf6;text-decoration:none;'>Unsubscribe / Manage settings</a>
            </div>
        </div>";

        DB::insert(
            "INSERT INTO email_queue (user_id, recipient, subject, body, status) VALUES (?, ?, ?, ?, 'pending')",
            [$userId, $userEmail, "Your AetherLife Weekly Recap Summary", $emailBody]
        );
        $queuedCount++;
    }

    echo "Successfully generated and queued {$queuedCount} weekly recap reports.\n";

} catch (Exception $e) {
    error_log("Weekly Cron Summary Failure: " . $e->getMessage());
    echo "Cron failed: " . $e->getMessage() . "\n";
    exit(1);
}
