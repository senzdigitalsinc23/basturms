<?php

require __DIR__ . '/vendor/autoload.php';

use App\Repositories\CalendarEventRepository;
use App\Services\CalendarEventService;

try {
    echo "Seeding test events...\n";

    $repo = new CalendarEventRepository();
    $service = new CalendarEventService($repo);

    // Get 'School event' category ID
    $categories = $service->getCategories();
    $schoolEventCatId = null;
    foreach ($categories as $cat) {
        if ($cat['event_type_name'] === 'School event') {
            $schoolEventCatId = $cat['event_type_id'];
            break;
        }
    }

    if (!$schoolEventCatId) {
        // Fallback if not found (though it should be there)
        $schoolEventCatId = 1;
        echo "Warning: 'School event' category not found by name, using ID 1.\n";
    }

    // Event details
    $title = "Reopening day";
    $date = "2025-09-12";

    // Check if exists to avoid duplicates (optional but good)
    // For now, just create it as requested.
    $event = $service->createEvent($title, $schoolEventCatId, $date);

    echo "Event created: {$title} on {$date} (Category ID: {$schoolEventCatId})\n";
    echo "Event ID: " . $event['event_id'] . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
