<?php
/**
 * Global general utilities and business calculations
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Set flash session message
 */
function setFlash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

/**
 * Check if flash session message exists
 */
function hasFlash(string $key): bool {
    return isset($_SESSION['flash'][$key]);
}

/**
 * Retrieve and clear flash session message
 */
function getFlash(string $key): string {
    if (!hasFlash($key)) {
        return '';
    }
    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Email sender utility.
 * Loads PHPMailer files dynamically from vendor/PHPMailer/
 * and falls back to PHP native mail() if not yet configured.
 */
function sendEmail(string $recipient, string $subject, string $body): bool {
    // Paths to PHPMailer classes in vendor
    $phpmailerPath = __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
    $smtpPath      = __DIR__ . '/../vendor/PHPMailer/SMTP.php';
    $exceptionPath = __DIR__ . '/../vendor/PHPMailer/Exception.php';

    // Alternative src/ subfolder layout
    if (!file_exists($phpmailerPath)) {
        $phpmailerPath = __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
        $smtpPath      = __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
        $exceptionPath = __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
    }

    if (file_exists($phpmailerPath) && file_exists($smtpPath) && file_exists($exceptionPath)) {
        require_once $exceptionPath;
        require_once $phpmailerPath;
        require_once $smtpPath;

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? 'ssl' : 'tls';
            $mail->Port       = SMTP_PORT;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($recipient);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("SMTP Dispatch Failed, using native mail fallback. Error: " . $e->getMessage());
        }
    }

    // Fallback: Standard PHP mail() header setups
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    
    return mail($recipient, $subject, $body, $headers);
}

/**
 * Calculates current, longest streaks and compliance rate for habits
 * Expects $logs as array of ['date' => 'YYYY-MM-DD', 'status' => 'completed'|'missed']
 * sorted chronologically (oldest to newest)
 */
function calculateHabitStreaks(array $logs, string $frequency = 'daily'): array {
    if (empty($logs)) {
        return [
            'current_streak' => 0,
            'longest_streak' => 0,
            'success_rate'   => 0.0
        ];
    }

    $completedDates = [];
    $totalCompleted = 0;
    $totalLogs = count($logs);

    foreach ($logs as $log) {
        if ($log['status'] === 'completed') {
            $completedDates[] = $log['date'];
            $totalCompleted++;
        }
    }

    $successRate = $totalLogs > 0 ? round(($totalCompleted / $totalLogs) * 100, 1) : 0.0;
    
    if (empty($completedDates)) {
        return [
            'current_streak' => 0,
            'longest_streak' => 0,
            'success_rate'   => 0.0
        ];
    }

    // Streaks evaluation
    // Convert to unique sorted list of DateTime strings
    $completedDates = array_unique($completedDates);
    sort($completedDates);

    $currentStreak = 0;
    $longestStreak = 0;
    $tempStreak = 0;
    $prevDate = null;

    $todayStr = date('Y-m-d');
    $yesterdayStr = date('Y-m-d', strtotime('-1 day'));

    foreach ($completedDates as $dateStr) {
        $curr = new DateTime($dateStr);
        if ($prevDate === null) {
            $tempStreak = 1;
        } else {
            $diff = $curr->diff($prevDate)->days;
            
            // For daily frequency, consecutive means difference of 1 day
            if ($diff === 1) {
                $tempStreak++;
            } elseif ($diff > 1) {
                // Check if skipped days were weekend for a weekdays frequency
                if ($frequency === 'weekdays') {
                    // Check if difference corresponds strictly to weekend days (Friday to Monday is 3 days diff)
                    $prevDay = (int)$prevDate->format('N');
                    if ($prevDay === 5 && $diff <= 3) {
                        $tempStreak++;
                    } else {
                        $tempStreak = 1;
                    }
                } else {
                    $tempStreak = 1;
                }
            }
        }
        
        if ($tempStreak > $longestStreak) {
            $longestStreak = $tempStreak;
        }
        $prevDate = $curr;
    }

    // Determine current active streak.
    // The streak is active if the last completed date is either today or yesterday.
    $lastCompletedDateStr = end($completedDates);
    if ($lastCompletedDateStr === $todayStr || $lastCompletedDateStr === $yesterdayStr) {
        // Find streak working backwards from the end
        $currentStreak = 1;
        $prevObj = new DateTime($lastCompletedDateStr);
        
        // Loop backwards in date array
        $reversed = array_reverse($completedDates);
        array_shift($reversed); // remove lastCompletedDateStr since we counted it
        
        foreach ($reversed as $dateStr) {
            $currObj = new DateTime($dateStr);
            $diff = $prevObj->diff($currObj)->days;
            if ($diff === 1) {
                $currentStreak++;
                $prevObj = $currObj;
            } elseif ($frequency === 'weekdays' && (int)$prevObj->format('N') === 1 && $diff <= 3) {
                $currentStreak++;
                $prevObj = $currObj;
            } else {
                break;
            }
        }
    } else {
        $currentStreak = 0;
    }

    return [
        'current_streak' => $currentStreak,
        'longest_streak' => $longestStreak,
        'success_rate'   => $successRate
    ];
}

/**
 * Daily Motivational Quote helper (changes stable daily)
 */
function getDailyQuote(): array {
    $quotes = [
        ["quote" => "The secret of getting ahead is getting started.", "author" => "Mark Twain"],
        ["quote" => "Your talent determines what you can do. Your motivation determines how much you are willing to do. Your attitude determines how well you do it.", "author" => "Lou Holtz"],
        ["quote" => "It always seems impossible until it's done.", "author" => "Nelson Mandela"],
        ["quote" => "Focus on being productive instead of busy.", "author" => "Tim Ferriss"],
        ["quote" => "Your energy is your currency. Spend it well. Invest it wisely.", "author" => "Oprah Winfrey"],
        ["quote" => "Consistency is the compound interest of self-improvement.", "author" => "James Clear"],
        ["quote" => "Do not wait for unique opportunities. Capture common occasions and make them great.", "author" => "Orison Swett Marden"],
        ["quote" => "Amateurs sit and wait for inspiration, the rest of us just get up and go to work.", "author" => "Stephen King"]
    ];
    
    // stable index based on day of month
    $index = (int)date('j') % count($quotes);
    return $quotes[$index];
}

/**
 * Dynamic greeting message based on local hour
 */
function getGreetingMessage(): string {
    $hour = (int)date('G');
    if ($hour < 12) {
        return 'Good Morning';
    } elseif ($hour < 17) {
        return 'Good Afternoon';
    } else {
        return 'Good Evening';
    }
}
