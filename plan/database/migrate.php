<?php
/**
 * Database Migrations Runner
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Format output according to environment
$is_cli = (php_sapi_name() === 'cli');

function output(string $message, string $type = 'info') {
    global $is_cli;
    $colors = [
        'success' => "\033[32m[SUCCESS]\033[0m ",
        'error' => "\033[31m[ERROR]\033[0m ",
        'info' => "\033[34m[INFO]\033[0m "
    ];
    
    if ($is_cli) {
        echo ($colors[$type] ?? '') . $message . "\n";
    } else {
        $class = [
            'success' => 'color: green; font-weight: bold;',
            'error' => 'color: red; font-weight: bold;',
            'info' => 'color: blue;'
        ][$type] ?? '';
        echo "<div style='font-family: monospace; margin: 5px 0; $class'>" . htmlspecialchars($message) . "</div>";
    }
}

try {
    // Connect to MySQL server without database first to check database existence
    $dsnNoDb = sprintf("mysql:host=%s;charset=%s", DB_HOST, DB_CHARSET);
    $pdoInit = new PDO($dsnNoDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create database if not exists
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    output("Ensured database '" . DB_NAME . "' exists.", 'success');
    
    // Now get the primary connection
    $db = DB::getConnection();
    
    // Create migrations tracker table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_name VARCHAR(255) UNIQUE NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Scan for migration SQL files
    $migrationsDir = __DIR__ . '/migrations';
    if (!is_dir($migrationsDir)) {
        throw new Exception("Migrations directory not found at $migrationsDir");
    }

    $files = glob($migrationsDir . '/*.sql');
    sort($files); // Sort files alphabetically to ensure strict order

    // Fetch already executed migrations
    $executed = DB::fetchAll("SELECT migration_name FROM migrations");
    $executedNames = array_column($executed, 'migration_name');

    $runCount = 0;
    foreach ($files as $filePath) {
        $fileName = basename($filePath);
        
        if (in_array($fileName, $executedNames)) {
            continue;
        }

        output("Executing migration: $fileName ...", 'info');
        
        $sql = file_get_contents($filePath);
        if ($sql === false || trim($sql) === '') {
            output("Skipped empty migration file: $fileName", 'info');
            continue;
        }

        // Execute migration within transaction to prevent partial state on error
        $db->beginTransaction();
        try {
            // Note: CREATE/DROP tables cause implicit commit in MySQL.
            // Transaction handles logical queries, but rollback on schema fails might not fully revert in MySQL.
            // We run files directly.
            $db->exec($sql);
            
            // Log migration as completed
            $stmt = $db->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
            $stmt->execute([$fileName]);
            
            $db->commit();
            output("Successfully ran migration: $fileName", 'success');
            $runCount++;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception("Migration failed in $fileName: " . $e->getMessage());
        }
    }

    if ($runCount === 0) {
        output("Database is up to date. No migrations executed.", 'success');
    } else {
        output("Migrations completed successfully. Total executed: $runCount", 'success');
    }

} catch (Exception $e) {
    output($e->getMessage(), 'error');
    exit(1);
}
