<?php
/**
 * Cron Task: Daily Morning Report Generator (Scheduled for 7:30 AM)
 */

declare(strict_types=1);

// Restrict execution to CLI only (or a secure key for Hostinger URL triggers)
$is_cli = (php_sapi_name() === 'cli');
$key = $_GET['key'] ?? '';
$configured_key = 'aether_cron_secret_123'; // Edit this in production

if (!$is_cli && $key !== $configured_key) {
    http_response_code(403);
    die("Unauthorized access to Cron Runner.");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

try {
    $db = DB::getConnection();

    // Fetch all users who have notification email summaries enabled
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

        // 1. Gather Today's Agenda Tasks
        $todayTasks = DB::fetchAll(
            "SELECT title, due_date, priority FROM tasks 
             WHERE user_id = ? AND DATE(due_date) = CURDATE() AND status != 'completed' AND status != 'archived'
             ORDER BY priority DESC",
            [$userId]
        );

        // 2. Gather Overdue Backlogs
        $overdueTasks = DB::fetchAll(
            "SELECT title, due_date FROM tasks 
             WHERE user_id = ? AND due_date < NOW() AND status IN ('pending', 'in_progress')
             ORDER BY due_date ASC",
            [$userId]
        );

        // 3. Gather Habits
        $habits = DB::fetchAll(
            "SELECT name, frequency FROM habits WHERE user_id = ?",
            [$userId]
        );

        // 4. Streak & Quote
        // Find maximum streak
        $habitsLogs = DB::fetchAll(
            "SELECT date, status FROM habit_logs hl 
             INNER JOIN habits h ON hl.habit_id = h.id 
             WHERE h.user_id = ? ORDER BY date ASC",
            [$userId]
        );
        $streakStats = calculateHabitStreaks($habitsLogs);
        $currentStreak = $streakStats['current_streak'] ?? 0;
        
        $quote = getDailyQuote();

        // 5. Build HTML Email Body
        $tasksLi = '';
        if (empty($todayTasks)) {
            $tasksLi = "<li style='color:#94a3b8;margin-bottom:8px;'>No tasks due today. Take some rest!</li>";
        } else {
            foreach ($todayTasks as $t) {
                $priorityBadge = $t['priority'] === 'critical' ? "<span style='color:#ef4444;font-weight:bold;'>[CRITICAL]</span>" : "";
                $dueTime = date('h:i A', strtotime($t['due_date']));
                $tasksLi .= "<li style='margin-bottom:8px;color:#f8fafc;'>{$priorityBadge} <strong>{$t['title']}</strong> at {$dueTime}</li>";
            }
        }

        $overdueLi = '';
        if (!empty($overdueTasks)) {
            $overdueLi .= "<h4 style='color:#ef4444;margin-top:20px;margin-bottom:10px;'>⚠️ Overdue Backlogs</h4><ul style='padding-left:20px;margin:0;'>";
            foreach (array_slice($overdueTasks, 0, 5) as $ot) {
                $dueDate = date('d M Y', strtotime($ot['due_date']));
                $overdueLi .= "<li style='color:#f8fafc;margin-bottom:6px;'>{$ot['title']} (Due: {$dueDate})</li>";
            }
            $overdueLi .= "</ul>";
        }

        $habitsLi = '';
        if (empty($habits)) {
            $habitsLi = "<li style='color:#94a3b8;'>No habits setup yet.</li>";
        } else {
            foreach ($habits as $h) {
                $habitsLi .= "<li style='color:#f8fafc;margin-bottom:6px;'>{$h['name']}</li>";
            }
        }

        $emailBody = "
        <div style='background-color:#0b0f19;font-family:sans-serif;padding:30px;color:#f8fafc;border-radius:10px;max-width:600px;margin:0 auto;border:1px solid #1e293b;'>
            <div style='text-align:center;border-bottom:1px solid #1e293b;padding-bottom:15px;margin-bottom:20px;'>
                <h2 style='color:#6366f1;margin:0;letter-spacing:1px;'>AETHERLIFE PLANNER</h2>
                <p style='color:#94a3b8;font-size:12px;margin:5px 0 0 0;'>Daily Morning Summary Digest</p>
            </div>
            
            <p style='font-size:16px;color:#f8fafc;'>Good Morning, {$userName}!</p>
            <p style='font-size:14px;color:#94a3b8;font-style:italic;line-height:1.5;'>\"{$quote['quote']}\"<br>— {$quote['author']}</p>
            
            <div style='background:rgba(255,255,255,0.02);border:1px solid #1e293b;padding:15px;border-radius:8px;margin-bottom:20px;'>
                <h3 style='color:#6366f1;margin-top:0;margin-bottom:12px;'>📋 Today's Agenda</h3>
                <ul style='padding-left:20px;margin:0;'>
                    {$tasksLi}
                </ul>
                {$overdueLi}
            </div>

            <div style='background:rgba(255,255,255,0.02);border:1px solid #1e293b;padding:15px;border-radius:8px;margin-bottom:20px;'>
                <h3 style='color:#10b981;margin-top:0;margin-bottom:12px;'>🌱 Today's Habits Checklist</h3>
                <ul style='padding-left:20px;margin:0;'>
                    {$habitsLi}
                </ul>
            </div>

            <div style='text-align:center;padding:10px 0;font-size:14px;'>
                <span style='color:#ef4444;font-weight:bold;'>🔥 Current Streak: {$currentStreak} Days</span>
            </div>

            <div style='text-align:center;border-top:1px solid #1e293b;padding-top:15px;margin-top:20px;font-size:11px;color:#64748b;'>
                You are receiving this digest because you turned on daily reports in settings.<br>
                <a href='" . APP_URL . "/settings' style='color:#6366f1;text-decoration:none;'>Unsubscribe / Manage settings</a>
            </div>
        </div>";

        // Queue email in database
        DB::insert(
            "INSERT INTO email_queue (user_id, recipient, subject, body, status) VALUES (?, ?, ?, ?, 'pending')",
            [$userId, $userEmail, "Your AetherLife Daily Summary - " . date('d M Y'), $emailBody]
        );
        $queuedCount++;
    }

    echo "Successfully generated and queued {$queuedCount} daily summary emails.\n";

} catch (Exception $e) {
    error_log("Daily Cron Summary Failure: " . $e->getMessage());
    echo "Cron failed: " . $e->getMessage() . "\n";
    exit(1);
}
