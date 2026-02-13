<?php
require 'vendor/autoload.php';

use App\Core\Database;

require_once 'Database/migrations/20251220000001_add_status_to_class_activity_assignment.php';

try {
    $db = Database::getInstance()->getConnection();
    $migration = new AddStatusToClassActivityAssignment20251220000001($db);
    $migration->up();
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
