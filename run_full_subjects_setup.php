<?php

require_once 'vendor/autoload.php';

use App\Core\Database;
use Database\Migration;

// Temporarily drop and recreate the subjects table to ensure correct schema
$db = Database::getInstance()->getConnection();

try {
    echo "Dropping subjects table if it exists...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;"); // Disable foreign key checks
    $db->exec("DROP TABLE IF EXISTS class_subjects;");
    $db->exec("DROP TABLE IF EXISTS staff_subjects;");
    $db->exec("DROP TABLE IF EXISTS results;");
    $db->exec("DROP TABLE IF EXISTS subjects;");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;"); // Re-enable foreign key checks
    echo "✓ Dependent tables and Subjects table dropped\n\n";

    // Run the subjects creation migration
    require_once 'Database/migrations/20250115000005_create_subjects_table.php';
    echo "Running create_subjects_table migration...\n";
    $createSubjectsMigration = new CreateSubjectsTable20250115000005();
    $createSubjectsMigration->up();
    echo "✓ Subjects table recreated with correct schema\n\n";

    // The status column is now part of the create_subjects_table migration, so this separate migration is no longer needed.
    // require_once 'Database/migrations/20251202_add_status_to_subjects_table.php';
    // echo "Running add_status_to_subjects_table migration...\n";
    // $addStatusMigration = new AddStatusToSubjectsTable20251202();
    // $addStatusMigration->up();
    // echo "✓ Status column added to subjects table\n\n";

    // Run the subjects seeder
    require_once 'Database/seeders/SubjectSeeder.php';
    echo "Running subjects seeder...\n";
    $seeder = new SubjectSeeder();
    $seeder->up();
    echo "✓ Subjects seeded successfully\n";

    echo "\nSubjects setup and seeding completed!\n";

} catch (Exception $e) {
    echo "✗ Error during subjects setup: " . $e->getMessage() . "\n";
}
