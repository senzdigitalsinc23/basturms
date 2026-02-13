<?php

require_once 'vendor/autoload.php';

use Database\Migration;

// Run the subjects status migration
require_once 'Database/migrations/20251202_add_status_to_subjects_table.php';

echo "Running add status to subjects table migration...\n";
$migration = new AddStatusToSubjectsTable20251202();
$migration->up();

echo "✓ Status column added to subjects table\n";
echo "\nMigration completed!\n";
