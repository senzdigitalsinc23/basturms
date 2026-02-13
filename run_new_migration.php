<?php

require_once 'vendor/autoload.php';

use App\Core\Database;

$migrationFile = 'Database/migrations/20250116000000_create_class_levels_table.php';

try {
    if (file_exists($migrationFile)) {
        echo "Running migration: $migrationFile\n";
        require_once $migrationFile;
        
        $db = Database::getInstance()->getConnection();
        $className = 'CreateClassLevelsTable20250116000000';
        $migration = new $className($db);
        $migration->up();
        echo "✓ Migration completed!\n";
    } else {
        echo "✗ Migration file not found: $migrationFile\n";
    }
} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
