<?php

require __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    require_once __DIR__ . '/Database/Migration.php';
    require_once __DIR__ . '/Database/migrations/20260110080633_create_calendar_events_tables.php';

    $migration = new CreateCalendarEventsTables20260110080633($db);
    $migration->up();
    
    echo "Migration executed successfully.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
