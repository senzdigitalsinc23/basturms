<?php

require_once 'vendor/autoload.php';

use Database\Migration;

// Run the subjects migration
require_once 'Database/migrations/create_subjects_table.php';

echo "Running subjects migration...\n";
$migration = new CreateSubjectsTable();
$migration->up();

echo "✓ Subjects table created\n\n";

// Run the subjects seeder
require_once 'Database/seeders/SubjectSeeder.php';

echo "Running subjects seeder...\n";
$seeder = new SubjectSeeder();
$seeder->up();

echo "✓ Subjects seeded successfully\n";
echo "\nSubjects setup completed!\n";
