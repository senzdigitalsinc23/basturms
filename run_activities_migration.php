<?php
require 'vendor/autoload.php';

use App\Core\Database;

require_once 'Database/migrations/20251220000000_create_activities_table.php';

try {
    $db = Database::getInstance()->getConnection();
    $migration = new CreateActivitiesTable20251220000000($db);
    $migration->up();
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
