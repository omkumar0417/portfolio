<?php
/**
 * Database Seeder Script
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$is_cli = (php_sapi_name() === 'cli');

function logSeeder(string $message, bool $success = true) {
    global $is_cli;
    $prefix = $success ? "[SUCCESS] " : "[INFO] ";
    if ($is_cli) {
        echo $prefix . $message . "\n";
    } else {
        $color = $success ? 'green' : 'blue';
        echo "<div style='font-family: monospace; color: $color; margin: 4px 0;'>" . htmlspecialchars($message) . "</div>";
    }
}

try {
    $db = DB::getConnection();
    
    // Disable foreign key checks for clean truncation
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Truncate tables to ensure idempotency
    $tables = [
        'users', 'categories', 'folders', 'tasks', 'subtasks', 
        'attachments', 'habits', 'habit_logs', 'goals', 
        'goal_milestones', 'notes', 'journal', 'pomodoro_logs', 
        'notifications', 'settings', 'login_history', 'audit_logs', 'email_queue'
    ];
    foreach ($tables as $table) {
        $db->exec("TRUNCATE TABLE `$table` ;");
    }
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    logSeeder("Truncated all existing tables for a clean seeding run.", false);

    // 1. Create Demo User
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    $userId = DB::insert("INSERT INTO users (email, password_hash, name, is_verified, avatar, timezone, country, language, occupation, birthday, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'demo@aetherlife.com',
        $password_hash,
        'Alex Vance',
        1,
        'avatar_demo.png',
        'Asia/Kolkata',
        'India',
        'en',
        'Software Engineer',
        '1998-05-15',
        'Productivity enthusiast, building cool software tools and learning daily.'
    ]);
    logSeeder("Created Demo User: demo@aetherlife.com (Password: password123)");

    // 2. Create Settings
    DB::insert("INSERT INTO settings (user_id, theme, accent_color, card_radius, compact_mode, sidebar_style, wallpaper, font_size, dashboard_layout, notification_email, notification_browser) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        $userId,
        'dark',
        'indigo',
        12,
        0,
        'glassmorphic',
        'default',
        'medium',
        'default',
        1,
        1
    ]);
    logSeeder("Created theme settings.");

    // 3. Create Categories
    $categories = [
        ['name' => 'Study', 'color' => '#3b82f6', 'icon' => 'fa-book'],
        ['name' => 'Health', 'color' => '#10b981', 'icon' => 'fa-heartbeat'],
        ['name' => 'Personal', 'color' => '#8b5cf6', 'icon' => 'fa-user'],
        ['name' => 'Projects', 'color' => '#f59e0b', 'icon' => 'fa-laptop-code'],
        ['name' => 'Finance', 'color' => '#ef4444', 'icon' => 'fa-wallet'],
        ['name' => 'Shopping', 'color' => '#ec4899', 'icon' => 'fa-shopping-cart']
    ];
    $categoryIds = [];
    foreach ($categories as $cat) {
        $catId = DB::insert("INSERT INTO categories (user_id, name, color, icon, is_system) VALUES (?, ?, ?, ?, ?)", [
            $userId, $cat['name'], $cat['color'], $cat['icon'], 0
        ]);
        $categoryIds[$cat['name']] = $catId;
    }
    logSeeder("Seeded custom task/habit categories.");

    // 4. Create Folders for Notes
    $folders = ['Study Guides', 'Project Specs', 'Personal Logs', 'Shopping Checklists'];
    $folderIds = [];
    foreach ($folders as $folder) {
        $folderId = DB::insert("INSERT INTO folders (user_id, name) VALUES (?, ?)", [
            $userId, $folder
        ]);
        $folderIds[$folder] = $folderId;
    }
    logSeeder("Seeded notes folders.");

    // 5. Create Habits
    $habits = [
        ['name' => 'Drink 3L Water', 'desc' => 'Hydrate throughout the day', 'cat' => 'Health', 'color' => '#06b6d4', 'icon' => 'fa-tint', 'freq' => 'daily'],
        ['name' => 'Read Book', 'desc' => 'Read 10 pages of a non-fiction book', 'cat' => 'Study', 'color' => '#8b5cf6', 'icon' => 'fa-book-open', 'freq' => 'daily'],
        ['name' => 'Gym/Workout', 'desc' => 'Strength training or cardio', 'cat' => 'Health', 'color' => '#10b981', 'icon' => 'fa-dumbbell', 'freq' => 'weekdays'],
        ['name' => 'DSA Practice', 'desc' => 'Solve 2 problems on Leetcode', 'cat' => 'Study', 'color' => '#f59e0b', 'icon' => 'fa-code', 'freq' => 'daily']
    ];
    
    $habitIds = [];
    foreach ($habits as $h) {
        $habitId = DB::insert("INSERT INTO habits (user_id, name, description, category_id, color, icon, frequency) VALUES (?, ?, ?, ?, ?, ?, ?)", [
            $userId, $h['name'], $h['desc'], $categoryIds[$h['cat']] ?? null, $h['color'], $h['icon'], $h['freq']
        ]);
        $habitIds[] = [
            'id' => $habitId,
            'name' => $h['name'],
            'freq' => $h['freq']
        ];
    }
    logSeeder("Seeded habits schema.");

    // 6. Seed 30 Days of Logs, Tasks, Journals & Pomodoros
    $today = new DateTime('now', new DateTimeZone('UTC'));
    $startDate = (clone $today)->modify('-30 days');
    
    $insertedLogs = 0;
    $insertedTasks = 0;
    $insertedPomodoros = 0;
    
    for ($i = 0; $i <= 30; $i++) {
        $currentDate = (clone $startDate)->modify("+$i days");
        $dateStr = $currentDate->format('Y-m-d');
        $dayOfWeek = (int)$currentDate->format('N'); // 1 = Monday, 7 = Sunday
        
        // --- SEED HABIT LOGS (With Realistic Streaks) ---
        foreach ($habitIds as $h) {
            $freq = $h['freq'];
            
            // Skip gym if it's weekend
            if ($freq === 'weekdays' && ($dayOfWeek === 6 || $dayOfWeek === 7)) {
                continue;
            }
            
            // Create a realistic success pattern:
            // Let's make Alex Vance complete habits ~80% of the time,
            // with a streak break around day 12 and 22.
            $complete = true;
            if ($i === 12 || $i === 22 || $i === 23) {
                $complete = false;
            } else {
                // random slight chance of missing
                $complete = (rand(1, 10) > 2);
            }
            
            if ($complete) {
                DB::insert("INSERT INTO habit_logs (habit_id, date, status, notes) VALUES (?, ?, 'completed', ?)", [
                    $h['id'], $dateStr, 'Done automatically in morning'
                ]);
                $insertedLogs++;
            } else {
                DB::insert("INSERT INTO habit_logs (habit_id, date, status, notes) VALUES (?, ?, 'missed', ?)", [
                    $h['id'], $dateStr, 'Felt tired / busy schedule'
                ]);
                $insertedLogs++;
            }
        }
        
        // --- SEED TASKS FOR THIS DAY ---
        // Let's insert a couple of tasks per day.
        if ($i < 30) { // Past tasks
            // Task 1: Health
            $taskTitle = "Morning run 5km";
            $status = (rand(1, 10) > 2) ? 'completed' : 'missed';
            $completedAt = ($status === 'completed') ? $dateStr . ' 07:30:00' : null;
            $estTime = 40;
            $actTime = ($status === 'completed') ? rand(35, 45) : 0;
            
            $taskId = DB::insert("INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date, estimated_time, actual_time, progress_percent, completed_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $userId, $categoryIds['Health'], $taskTitle, 'Running around the park', 'low', $status, $dateStr . ' 08:00:00', $estTime, $actTime, ($status === 'completed' ? 100 : 0), $completedAt, $dateStr . ' 06:00:00'
            ]);
            $insertedTasks++;
            
            // Task 2: Study/Work
            $taskTitle = "Solve DSA LinkedList questions";
            $status = (rand(1, 10) > 3) ? 'completed' : 'pending'; // Some might remain pending
            if ($status === 'pending' && $i < 27) {
                // If it is older than 3 days, it became 'missed'
                $status = 'missed';
            }
            $completedAt = ($status === 'completed') ? $dateStr . ' 16:30:00' : null;
            $estTime = 90;
            $actTime = ($status === 'completed') ? rand(80, 110) : 0;
            
            $taskId = DB::insert("INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date, estimated_time, actual_time, progress_percent, completed_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $userId, $categoryIds['Study'], $taskTitle, 'Reverse linked list and cycle detection', 'high', $status, $dateStr . ' 18:00:00', $estTime, $actTime, ($status === 'completed' ? 100 : 0), $completedAt, $dateStr . ' 10:00:00'
            ]);
            $insertedTasks++;
            
            // Seed subtasks for this task
            DB::insert("INSERT INTO subtasks (task_id, title, is_completed) VALUES (?, ?, ?)", [$taskId, 'Reverse linked list problem', ($status === 'completed' ? 1 : 0)]);
            DB::insert("INSERT INTO subtasks (task_id, title, is_completed) VALUES (?, ?, ?)", [$taskId, 'Detect cycle in linked list', ($status === 'completed' ? 1 : 0)]);
            
            // Task 3: Projects
            if ($dayOfWeek === 2 || $dayOfWeek === 5) { // Tuesday/Friday spec
                $taskTitle = "Build API logic for Planner APP";
                $status = 'completed';
                $completedAt = $dateStr . ' 20:00:00';
                
                $taskId = DB::insert("INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date, estimated_time, actual_time, progress_percent, completed_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                    $userId, $categoryIds['Projects'], $taskTitle, 'Implement core MVC routing interfaces', 'critical', $status, $dateStr . ' 21:00:00', 120, 135, 100, $completedAt, $dateStr . ' 14:00:00'
                ]);
                $insertedTasks++;
                
                // --- SEED POMODORO LOGS ---
                // Add pomodoro session related to this task
                DB::insert("INSERT INTO pomodoro_logs (user_id, duration_minutes, task_id, type, created_at) VALUES (?, ?, ?, 'focus', ?)", [
                    $userId, 50, $taskId, $dateStr . ' 15:00:00'
                ]);
                DB::insert("INSERT INTO pomodoro_logs (user_id, duration_minutes, task_id, type, created_at) VALUES (?, ?, ?, 'focus', ?)", [
                    $userId, 50, $taskId, $dateStr . ' 16:30:00'
                ]);
                $insertedPomodoros += 2;
            }
        } else {
            // Seeding TODAY's tasks and upcoming deadlines
            // Task 1: Today high priority
            DB::insert("INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date, estimated_time, progress_percent, created_at) VALUES (?, ?, 'Integrate FullCalendar in dashboard', 'Setup client-side event binding', 'high', 'in_progress', ?, 120, 40, ?)", [
                $userId, $categoryIds['Projects'], $dateStr . ' 18:00:00', $dateStr . ' 09:00:00'
            ]);
            
            // Task 2: Today critical deadline
            DB::insert("INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date, estimated_time, progress_percent, created_at) VALUES (?, ?, 'Submit weekly progress summary', 'Required for team review', 'critical', 'pending', ?, 30, 0, ?)", [
                $userId, $categoryIds['Projects'], $dateStr . ' 15:00:00', $dateStr . ' 08:30:00'
            ]);
            
            // Task 3: Tomorrow task
            $tomorrowStr = (clone $currentDate)->modify('+1 day')->format('Y-m-d');
            DB::insert("INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date, estimated_time, progress_percent, created_at) VALUES (?, ?, 'Gym: Chest & Triceps session', 'Strength building phase', 'medium', 'pending', ?, 60, 0, ?)", [
                $userId, $categoryIds['Health'], $tomorrowStr . ' 08:00:00', $dateStr . ' 12:00:00'
            ]);
            
            // Task 4: Next week task
            $nextWeekStr = (clone $currentDate)->modify('+4 days')->format('Y-m-d');
            DB::insert("INSERT INTO tasks (user_id, category_id, title, description, priority, status, due_date, estimated_time, progress_percent, created_at) VALUES (?, ?, 'Monthly financial accounts reconciliation', 'Categorize all expense receipts', 'low', 'pending', ?, 90, 0, ?)", [
                $userId, $categoryIds['Finance'], $nextWeekStr . ' 17:00:00', $dateStr . ' 10:00:00'
            ]);
            
            $insertedTasks += 4;
        }
        
        // --- SEED JOURNAL FOR THE DAY ---
        // Let's write journal logs for the last 15 days
        if ($i >= 15 && $i < 30) {
            $moods = ['happy', 'energetic', 'neutral', 'tired'];
            $randomMood = $moods[array_rand($moods)];
            $energy = rand(3, 5);
            $productivity = rand(3, 5);
            
            DB::insert("INSERT INTO journal (user_id, date, morning_journal, night_journal, mood, energy_level, productivity_score, gratitude, reflection, learning, problems, achievements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                $userId,
                $dateStr,
                "Woke up early at 6 AM. Excited for today's coding milestones.",
                "Completed my tasks. Had a great coding run on the app backend.",
                $randomMood,
                $energy,
                $productivity,
                "Grateful for good coffee and clean documentation.",
                "Spent a bit too much time debugging. Need to search docs earlier next time.",
                "Learned about PDO multi-query execution variables.",
                "Encountered a few migration path errors but fixed them.",
                "Successfully routed base layout controllers."
            ]);
        }
    }
    
    logSeeder("Seeded $insertedLogs Habit Logs across 30 days.", false);
    logSeeder("Seeded $insertedTasks Tasks with Checklist items.", false);
    logSeeder("Seeded $insertedPomodoros Pomodoro logs.", false);
    logSeeder("Seeded Journal records for the last 15 days.", false);

    // 7. Create Goals
    // Long term Goal
    $goalId1 = DB::insert("INSERT INTO goals (user_id, title, description, type, status, deadline, reward, progress_percent, notes) VALUES (?, ?, ?, 'quarterly', 'in_progress', ?, ?, 66, ?)", [
        $userId,
        'Master Backend Architecture and DSA',
        'Prepare comprehensively for senior backend engineering assessments.',
        (clone $today)->modify('+45 days')->format('Y-m-d'),
        'Custom mechanical keyboard!',
        'Stay focused on consistent LeetCode and System Design reviews daily.'
    ]);
    
    DB::insert("INSERT INTO goal_milestones (goal_id, title, is_completed, deadline) VALUES (?, ?, 1, ?)", [$goalId1, 'Solve 100 Medium Leetcode problems', (clone $today)->modify('-5 days')->format('Y-m-d')]);
    DB::insert("INSERT INTO goal_milestones (goal_id, title, is_completed, deadline) VALUES (?, ?, 1, ?)", [$goalId1, 'Build dynamic customized MVC application', (clone $today)->modify('+10 days')->format('Y-m-d')]);
    DB::insert("INSERT INTO goal_milestones (goal_id, title, is_completed, deadline) VALUES (?, ?, 0, ?)", [$goalId1, 'Study microservices patterns & load balancing', (clone $today)->modify('+30 days')->format('Y-m-d')]);

    // Short term Goal
    $goalId2 = DB::insert("INSERT INTO goals (user_id, title, description, type, status, deadline, reward, progress_percent, notes) VALUES (?, ?, ?, 'short_term', 'completed', ?, ?, 100, ?)", [
        $userId,
        'Build Life Planner App Prototype',
        'Create a full working prototype of the productivity app.',
        (clone $today)->modify('-2 days')->format('Y-m-d'),
        'Weekend rest trip',
        'Finished everything within deadline.'
    ]);
    DB::insert("INSERT INTO goal_milestones (goal_id, title, is_completed, deadline) VALUES (?, ?, 1, ?)", [$goalId2, 'Design database relations & migrations', (clone $today)->modify('-10 days')->format('Y-m-d')]);
    DB::insert("INSERT INTO goal_milestones (goal_id, title, is_completed, deadline) VALUES (?, ?, 1, ?)", [$goalId2, 'Build modular REST API layers', (clone $today)->modify('-5 days')->format('Y-m-d')]);
    DB::insert("INSERT INTO goal_milestones (goal_id, title, is_completed, deadline) VALUES (?, ?, 1, ?)", [$goalId2, 'Assemble dashboard and glassmorphic css files', (clone $today)->modify('-2 days')->format('Y-m-d')]);

    logSeeder("Seeded 2 Major Goals with Milestones.");

    // 8. Create Notes
    DB::insert("INSERT INTO notes (user_id, folder_id, title, content, is_pinned, is_favorite, tags) VALUES (?, ?, ?, ?, ?, ?, ?)", [
        $userId,
        $folderIds['Study Guides'],
        'System Design Principles Cheat Sheet',
        "# System Design Core Pillars\n\n### 1. Scalability\n- **Horizontal Scaling**: Adding more nodes.\n- **Vertical Scaling**: Adding more power to existing node.\n\n### 2. Availability & Reliability\n- Redundancy\n- Load Balancers\n- Failover Strategies\n\n### 3. Caching Strategies\n- CDN\n- Redis / Memcached\n- Cache Aside pattern",
        1,
        1,
        'design,backend,study'
    ]);
    
    DB::insert("INSERT INTO notes (user_id, folder_id, title, content, is_pinned, is_favorite, tags) VALUES (?, ?, ?, ?, ?, ?, ?)", [
        $userId,
        $folderIds['Project Specs'],
        'Planner App MVC Directory Roadmap',
        "# Project Layout\n\n- `config/`: Contains DB config, helpers, security layers.\n- `controllers/`: Handles path routing processing.\n- `models/`: Interface database interactions.\n- `views/`: Premium user screens.\n- `api/`: AJAX endpoints.\n\nReady for deployment directly on Hostinger.",
        0,
        1,
        'aetherlife,development'
    ]);

    logSeeder("Seeded rich pinned and favorited Notes.");

    // 9. Login History & Audit Logs
    DB::insert("INSERT INTO login_history (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'success')", [
        $userId,
        '192.168.1.1',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36'
    ]);
    DB::insert("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'USER_SIGNUP', 'Completed demo account creation process.', '192.168.1.1')", [
        $userId
    ]);
    DB::insert("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, 'PROFILE_UPDATE', 'Set timezone setting to Asia/Kolkata.', '192.168.1.1')", [
        $userId
    ]);
    logSeeder("Seeded login and activity audit logs.");

    logSeeder("DATABASE SEEDING COMPLETED SUCCESSFULLY!", true);

} catch (Exception $e) {
    logSeeder("Seeding failed: " . $e->getMessage(), false);
    exit(1);
}
