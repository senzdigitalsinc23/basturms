<?php

require __DIR__ . '/vendor/autoload.php';

use App\Repositories\CalendarEventRepository;
use App\Services\CalendarEventService;

try {
    echo "Starting verification...\n";

    $repo = new CalendarEventRepository();
    $service = new CalendarEventService($repo);

    // 1. Get Categories
    echo "Fetching Categories...\n";
    $categories = $service->getCategories();
    if (empty($categories)) {
        throw new Exception("No categories found!");
    }
    echo "Categories found: " . count($categories) . "\n";
    $firstCategoryId = $categories[0]['event_type_id'];

    // 2. Create Event
    echo "Creating Event...\n";
    $event = $service->createEvent("Test Verification Event", $firstCategoryId, "2026-01-20");
    if (!$event) {
        throw new Exception("Failed to create event");
    }
    $eventId = $event['event_id'];
    echo "Event created with ID: $eventId\n";

    // 3. List Events
    echo "Listing Events...\n";
    $events = $service->getAllEvents();
    $found = false;
    foreach ($events as $e) {
        if ($e['event_id'] == $eventId) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        throw new Exception("Created event not found in list");
    }
    echo "Event found in list.\n";

    // 4. Update Event
    echo "Updating Event...\n";
    $success = $service->updateEvent($eventId, "Updated Test Event", $firstCategoryId, "2026-01-21");
    if (!$success) {
        throw new Exception("Failed to update event");
    }
    $updatedEvent = $service->getEventById($eventId);
    if ($updatedEvent['event_title'] !== "Updated Test Event") {
        throw new Exception("Event title mismatch after update");
    }
    echo "Event updated successfully.\n";

    // 5. Delete Event
    echo "Deleting Event...\n";
    $success = $service->deleteEvent($eventId);
    if (!$success) {
        throw new Exception("Failed to delete event");
    }
    $deletedEvent = $service->getEventById($eventId);
    if ($deletedEvent) {
        throw new Exception("Event still exists after deletion");
    }
    echo "Event deleted successfully.\n";

    echo "Verification Complete: All tests passed.\n";

} catch (Exception $e) {
    echo "Verification Failed: " . $e->getMessage() . "\n";
    exit(1);
}
