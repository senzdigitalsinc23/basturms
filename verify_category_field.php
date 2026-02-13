<?php

require __DIR__ . '/vendor/autoload.php';

use App\Repositories\CalendarEventRepository;

try {
    echo "Verifying category field...\n";
    $repo = new CalendarEventRepository();
    
    // Get all events
    $events = $repo->getAll();
    
    if (empty($events)) {
        echo "No events found to verify.\n";
    } else {
        $event = $events[0];
        print_r($event);
        
        if (isset($event['category']) && !empty($event['category'])) {
            echo "SUCCESS: 'category' field present: " . $event['category'] . "\n";
        } else {
            echo "FAILURE: 'category' field missing or empty.\n";
            exit(1);
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
