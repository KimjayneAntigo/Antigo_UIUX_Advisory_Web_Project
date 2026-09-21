<?php
/**
 * Applies cancellation columns and constraints to the projects table.
 */

require_once __DIR__ . '/../config/db.php';

echo "=== Applying Cancellation Schema Migration ===\n\n";

try {
    // 1. Check if cancelled_at already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM projects LIKE 'cancelled_at'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "Column 'cancelled_at' already exists on 'projects'. Checking enum...\n";
    } else {
        $sql = "ALTER TABLE `projects`
            MODIFY COLUMN `status_type` ENUM('pending','in_design','completed','cancelled') NOT NULL DEFAULT 'pending',
            ADD COLUMN `cancelled_at` TIMESTAMP NULL DEFAULT NULL AFTER `status_type`,
            ADD COLUMN `cancelled_by` INT UNSIGNED DEFAULT NULL COMMENT 'FK to users.id — who cancelled it' AFTER `cancelled_at`,
            ADD COLUMN `cancellation_reason` TEXT DEFAULT NULL AFTER `cancelled_by`,
            ADD CONSTRAINT `fk_projects_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`) ON DELETE SET NULL";
        
        $pdo->exec($sql);
        echo "Migration executed successfully!\n";
    }

    // Verify columns
    echo "\nColumns in 'projects' table:\n";
    $cols = $pdo->query("SHOW COLUMNS FROM projects")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        if (in_array($c['Field'], ['status_type', 'cancelled_at', 'cancelled_by', 'cancellation_reason'])) {
            echo " - {$c['Field']}: {$c['Type']} (Null: {$c['Null']}, Default: {$c['Default']})\n";
        }
    }

} catch (PDOException $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}