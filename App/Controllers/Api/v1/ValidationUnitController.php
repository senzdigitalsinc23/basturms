<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Validation Units",
    description: "API endpoints for unit management in validation system"
)]
class ValidationUnitController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    #[OA\Get(
        path: "/api/v1/validation/units",
        summary: "Get all units",
        description: "Retrieve all organizational units",
        tags: ["Validation Units"],
        responses: [
            new OA\Response(response: 200, description: "Units retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function getAllUnits(Request $request, Response $response): Response
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    id,
                    name,
                    description,
                    created_at as createdAt
                FROM units
                WHERE deleted_at IS NULL
                ORDER BY name
            ");

            $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, [
                'success' => true,
                'units' => $units
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to retrieve units: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/validation/units",
        summary: "Create new unit",
        description: "Add a new organizational unit",
        tags: ["Validation Units"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Human Resources"),
                    new OA\Property(property: "description", type: "string", example: "HR Department")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Unit created successfully"),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function createUnit(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $name = $data['name'] ?? '';
            $description = $data['description'] ?? '';

            if (empty($name)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Unit name is required'
                ], 400);
            }

            // Check if unit name already exists
            $stmt = $this->db->prepare("SELECT id FROM units WHERE name = :name AND deleted_at IS NULL");
            $stmt->execute(['name' => $name]);
            if ($stmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Unit name already exists'
                ], 400);
            }

            $id = $this->generateUuid();

            $stmt = $this->db->prepare("
                INSERT INTO units (id, name, description)
                VALUES (:id, :name, :description)
            ");

            $stmt->execute([
                'id' => $id,
                'name' => $name,
                'description' => $description
            ]);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Unit created successfully',
                'unit' => [
                    'id' => $id,
                    'name' => $name,
                    'description' => $description
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to create unit: ' . $e->getMessage()
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

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
