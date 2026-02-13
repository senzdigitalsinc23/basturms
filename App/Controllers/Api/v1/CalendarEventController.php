<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Models\Activity;
use App\Models\CalendarEvent;
use App\Models\CalendarEventCategories;
use App\Services\CalendarEventService;
use OpenApi\Attributes as OA;

/**
 * Controller for calendar events.
 */
#[OA\Tag(
    name: "Calendar Management",
    description: "API endpoints for managing calendar events"
)]
class CalendarEventController
{
    private CalendarEventService $calendarEventService;

    public function __construct(CalendarEventService $calendarEventService)
    {
        $this->calendarEventService = $calendarEventService;
    }

    #[OA\Post(
        path: "/calendar/events/add",
        summary: "Add a new calendar event",
        tags: ["Calendar Management"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["event_title", "event_category", "event_date"],
                properties: [
                    new OA\Property(property: "event_title", type: "string"),
                    new OA\Property(property: "event_category", type: "integer"),
                    new OA\Property(property: "event_date", type: "string", format: "date")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Event created successfully"),
            new OA\Response(response: 400, description: "Validation error")
        ]
    )]
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getPost();

        $data['event_category'] = is_string($data['event_category']) ? CalendarEventCategories::where('event_type_name', $data['event_category'] ?? '')->event_type_id : (int)($data['event_category'] ?? 0);
        $data['event_date'] = to_date($data['event_date']) ?? '';
        
        $title = $data['event_title'] ?? '';
        $categoryId = (int)($data['event_category'] ?? 0);
        $date = $data['event_date'] ?? '';

        if (empty($title) || $categoryId <= 0 || empty($date)) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => 'Missing required fields']));
            return $response;
        }

        $event = $this->calendarEventService->createEvent($title, $categoryId, $date);

        $response->setStatusCode(201);
        $response->setContent(json_encode(['success' => true, 'data' => $event]));
        return $response;
    }

    #[OA\Post(
        path: "/calendar/events/update",
        summary: "Update a calendar event",
        tags: ["Calendar Management"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["event_id", "event_title", "event_category", "event_date"],
                properties: [
                    new OA\Property(property: "event_id", type: "integer"),
                    new OA\Property(property: "event_title", type: "string"),
                    new OA\Property(property: "event_category", type: "integer"),
                    new OA\Property(property: "event_date", type: "string", format: "date")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Event updated successfully"),
            new OA\Response(response: 400, description: "Validation error")
        ]
    )]
    public function update(Request $request, Response $response): Response
    {
        $data = $request->getPost();

        $data['event_category'] = is_string($data['event_category']) ? CalendarEventCategories::where('event_type_name', $data['event_category'] ?? '')->event_type_id : (int)($data['event_category'] ?? 0);
        $data['event_date'] = to_date($data['event_date']) ?? '';

        $id = (int)($data['id'] ?? 0);
        $title = $data['event_title'] ?? '';
        $categoryId = (int)($data['event_category'] ?? 0);
        $date = to_date($data['event_date']) ?? '';

        if ($id <= 0 || empty($title) || $categoryId <= 0 || empty($date)) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => 'Missing required fields']));
            return $response;
        }

        $success = $this->calendarEventService->updateEvent($id, $title, $categoryId, $date);

        //echo json_encode($success);exit;
        if ($success) {
            $response->setStatusCode(200);
            $response->setContent(json_encode(['success' => true, 'message' => 'Event updated successfully', 'data' => $data]));
        } else {
            $response->setStatusCode(500); // Or 404 if not found, but update usually returns false on failure
            $response->setContent(json_encode(['success' => false, 'message' => 'Failed to update event']));
        }
        return $response;
    }

    #[OA\Post(
        path: "/calendar/events/delete",
        summary: "Delete a calendar event",
        tags: ["Calendar Management"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["event_id"],
                properties: [
                    new OA\Property(property: "event_id", type: "integer")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Event deleted successfully")
        ]
    )]
    public function delete(Request $request, Response $response): Response
    {
        $data = $request->getPost();

        $id = (int)($data['id'] ?? 0);

        //echo json_encode($id);exit;
        if ($id <= 0) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => 'Invalid event ID']));
            return $response;
        }

        $success = $this->calendarEventService->deleteEvent($id);

        if ($success) {
            $response->setStatusCode(200);
            $response->setContent(json_encode(['success' => true, 'message' => 'Event deleted successfully']));
        } else {
            $response->setStatusCode(500);
            $response->setContent(json_encode(['success' => false, 'message' => 'Failed to delete event']));
        }
        return $response;
    }

    #[OA\Get(
        path: "/calendar/events/list",
        summary: "List all calendar events",
        tags: ["Calendar Management"],
        responses: [
            new OA\Response(response: 200, description: "List of events")
        ]
    )]
    public function list(Request $request, Response $response): Response
    {
        $events = $this->calendarEventService->getAllEvents();
        $response->setStatusCode(200);
        $response->setContent(json_encode(['success' => true, 'data' => $events]));
        return $response;
    }

    public function listCategories(Request $request, Response $response): Response
    {
        $categories = $this->calendarEventService->getCategories();
        $response->setStatusCode(200);
        $response->setContent(json_encode(['success' => true, 'data' => $categories]));
        return $response;
    }
}
