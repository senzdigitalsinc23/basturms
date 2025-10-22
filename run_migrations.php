<?php

require_once 'vendor/autoload.php';

use Database\Migration;

// List of migration files to run
$migrations = [
    'database/migrations/20250115000000_create_attendance_table.php',
    'database/migrations/20250115000001_create_student_payments_table.php',
    'database/migrations/20250115000002_create_audit_logs_table.php',
    'database/migrations/20250115000003_create_auth_logs_table.php',
    'database/migrations/20250115000004_create_student_bill_items_table.php',
    'database/migrations/20250115000005_create_student_clubs_table.php',
    'database/migrations/20250115000006_create_student_sports_teams_table.php',
    'database/migrations/20250115000007_create_student_documents_table.php'
];

echo "Running migrations...\n";

foreach ($migrations as $migrationFile) {
    if (file_exists($migrationFile)) {
        echo "Running migration: $migrationFile\n";
        
        // Include the migration file
        require_once $migrationFile;
        
        // Extract class name from filename
        $className = basename($migrationFile, '.php');
        
        // Create instance and run migration
        $migration = new $className();
        $migration->up();
        
        echo "✓ Migration completed: $migrationFile\n";
    } else {
        echo "✗ Migration file not found: $migrationFile\n";
    }
}

echo "All migrations completed!\n";
