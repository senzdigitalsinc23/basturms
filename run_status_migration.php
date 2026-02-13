<?php

require_once 'vendor/autoload.php';

use App\Core\Database;

$migrationFile = 'Database/migrations/20251219000001_add_status_to_assignment_activities.php';

try {
    if (file_exists($migrationFile)) {
        echo "Running migration: $migrationFile\n";
        require_once $migrationFile;
        
        $db = Database::getInstance()->getConnection();
        $className = 'AddStatusToAssignmentActivities20251219000001';
        $migration = new $className($db);
        $migration->up();
        echo "✓ Migration completed!\n";
    } else {
        echo "✗ Migration file not found: $migrationFile\n";
    }
} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
