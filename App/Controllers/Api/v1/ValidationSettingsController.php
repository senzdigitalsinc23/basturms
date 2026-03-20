<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Validation Settings",
    description: "API endpoints for managing validation period settings"
)]
class ValidationSettingsController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    #[OA\Post(
        path: "/api/v1/validation/settings",
        summary: "Set validation period",
        description: "Set start and end dates for a validation period (Admin and HR only)",
        tags: ["Validation Settings"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["month", "year", "startDate", "endDate"],
                properties: [
                    new OA\Property(property: "month", type: "string", example: "March"),
                    new OA\Property(property: "year", type: "integer", example: 2026),
                    new OA\Property(property: "startDate", type: "string", format: "date", example: "2026-03-01"),
                    new OA\Property(property: "endDate", type: "string", format: "date", example: "2026-03-31"),
                    new OA\Property(property: "startTime", type: "string", format: "time", example: "08:00"),
                    new OA\Property(property: "endTime", type: "string", format: "time", example: "17:00")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Validation period set successfully"),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 403, description: "Forbidden - Admin or HR only")
        ]
    )]
    public function setValidationPeriod(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $userRole = $user['role'] ?? '';
            
            // Only admin and HR incharge can set validation periods
            if ($userRole !== 'admin') {
                // Check if HR incharge
                if ($userRole === 'incharge') {
                    $stmt = $this->db->prepare("
                        SELECT u.name as unit_name
                        FROM validation_staff s
                        LEFT JOIN units u ON s.unit_id = u.id
                        WHERE s.id = :user_id
                    ");
                    $stmt->execute(['user_id' => $user['user_id']]);
                    $userUnit = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$userUnit || $userUnit['unit_name'] !== 'Human Resources') {
                        return $this->jsonResponse($response, [
                            'success' => false,
                            'message' => 'Only Admin and HR can set validation periods'
                        ], 403);
                    }
                } else {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'message' => 'Only Admin and HR can set validation periods'
                    ], 403);
                }
            }

            $data = $request->getPost();
            $month = $data['month'] ?? '';
            $year = $data['year'] ?? 0;
            $startDate = $data['startDate'] ?? '';
            $endDate = $data['endDate'] ?? '';
            $startTime = $data['startTime'] ?? '00:00';
            $endTime = $data['endTime'] ?? '23:59';

            if (empty($month) || empty($year) || empty($startDate) || empty($endDate)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Missing required fields'
                ], 400);
            }

            // Combine date and time
            $startDateTime = $startDate . ' ' . $startTime . ':00';
            $endDateTime = $endDate . ' ' . $endTime . ':59';

            // Validate dates
            if (strtotime($startDateTime) === false || strtotime($endDateTime) === false) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid date or time format'
                ], 400);
            }

            if (strtotime($endDateTime) < strtotime($startDateTime)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'End date/time must be after start date/time'
                ], 400);
            }

            $stmt = $this->db->prepare("
                INSERT INTO validation_settings (month, year, start_date, end_date, created_by)
                VALUES (:month, :year, :start_date, :end_date, :created_by)
                ON DUPLICATE KEY UPDATE
                    start_date = VALUES(start_date),
                    end_date = VALUES(end_date),
                    created_by = VALUES(created_by),
                    updated_at = NOW()
            ");

            $stmt->execute([
                'month' => $month,
                'year' => $year,
                'start_date' => $startDateTime,
                'end_date' => $endDateTime,
                'created_by' => $user['user_id'] ?? null
            ]);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Validation period set successfully'
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to set validation period: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/validation/settings",
        summary: "Get validation period",
        description: "Get validation period settings for a specific month and year",
        tags: ["Validation Settings"],
        parameters: [
            new OA\Parameter(name: "month", in: "query", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "year", in: "query", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Validation period retrieved successfully"),
            new OA\Response(response: 404, description: "No validation period set")
        ]
    )]
    public function getValidationPeriod(Request $request, Response $response): Response
    {
        try {
            $month = $request->getQuery('month');
            $year = $request->getQuery('year');

            if (empty($month) || empty($year)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Month and year are required'
                ], 400);
            }

            $stmt = $this->db->prepare("
                SELECT 
                    month,
                    year,
                    start_date as startDate,
                    end_date as endDate,
                    created_by as createdBy,
                    created_at as createdAt,
                    updated_at as updatedAt
                FROM validation_settings
                WHERE month = :month AND year = :year
            ");

            $stmt->execute(['month' => $month, 'year' => $year]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'No validation period set for this month',
                    'settings' => null
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'settings' => $settings
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to retrieve validation period: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/validation/settings/check",
        summary: "Check if validation is allowed",
        description: "Check if validation is currently allowed based on the validation period",
        tags: ["Validation Settings"],
        parameters: [
            new OA\Parameter(name: "month", in: "query", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "year", in: "query", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Validation status checked successfully")
        ]
    )]
    public function checkValidationAllowed(Request $request, Response $response): Response
    {
        try {
            $month = $request->getQuery('month');
            $year = $request->getQuery('year');

            if (empty($month) || empty($year)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Month and year are required'
                ], 400);
            }

            $stmt = $this->db->prepare("
                SELECT start_date, end_date
                FROM validation_settings
                WHERE month = :month AND year = :year
            ");

            $stmt->execute(['month' => $month, 'year' => $year]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            $now = date('Y-m-d H:i:s');
            $isAllowed = false;
            $reason = '';

            if (!$settings) {
                $reason = 'No validation period set for this month';
            } else {
                if ($now < $settings['start_date']) {
                    $reason = 'Validation period has not started yet';
                } elseif ($now > $settings['end_date']) {
                    $reason = 'Validation period has ended';
                } else {
                    $isAllowed = true;
                    $reason = 'Validation is currently allowed';
                }
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'isAllowed' => $isAllowed,
                'reason' => $reason,
                'settings' => $settings
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to check validation status: ' . $e->getMessage()
            ], 500);
        }
    }

    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode($statusCode);
        return $response;
    }
}
