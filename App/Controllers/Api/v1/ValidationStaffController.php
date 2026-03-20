<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Validation Staff",
    description: "API endpoints for staff management in validation system"
)]
class ValidationStaffController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    #[OA\Get(
        path: "/api/v1/validation/staff",
        summary: "Get all staff",
        description: "Retrieve all staff members with their unit information and optional filters with pagination",
        tags: ["Validation Staff"],
        parameters: [
            new OA\Parameter(name: "unit", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "department", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["At Post", "Not At Post", "Not Validated"])),
            new OA\Parameter(name: "month", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "year", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "perPage", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: "Staff retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function getAllStaff(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $userRole = $user['role'] ?? '';
            $userId = $user['user_id'] ?? '';

            // Get filter parameters
            $unitFilter = $request->getQuery('unit');
            $departmentFilter = $request->getQuery('department');
            $statusFilter = $request->getQuery('status');
            $searchQuery = $request->getQuery('search');
            $month = $request->getQuery('month') ?: date('F');
            $year = $request->getQuery('year') ?: date('Y');
            
            // Get pagination parameters
            $page = max(1, (int)($request->getQuery('page') ?: 1));
            $perPage = max(1, min(100, (int)($request->getQuery('perPage') ?: 20)));
            $offset = ($page - 1) * $perPage;

            // Build WHERE conditions
            $whereConditions = ["s.deleted_at IS NULL"];
            $params = ['month' => $month, 'year' => $year];

            // Check if user is HR incharge (has full access)
            $isHRIncharge = false;
            if ($userRole === 'incharge') {
                $stmt = $this->db->prepare("
                    SELECT u.name as unit_name
                    FROM validation_staff s
                    LEFT JOIN units u ON s.unit_id = u.id
                    WHERE s.id = :user_id
                ");
                $stmt->execute(['user_id' => $userId]);
                $userUnit = $stmt->fetch(PDO::FETCH_ASSOC);
                $isHRIncharge = ($userUnit && $userUnit['unit_name'] === 'Human Resources');

                // Non-HR incharges can only see their unit
                if (!$isHRIncharge) {
                    $whereConditions[] = "s.unit_id = (SELECT unit_id FROM validation_staff WHERE id = :user_id)";
                    $params['user_id'] = $userId;
                }
            }

            // Apply filters
            if ($unitFilter) {
                $whereConditions[] = "s.unit_id = :unit_id";
                $params['unit_id'] = $unitFilter;
            }

            if ($departmentFilter) {
                $whereConditions[] = "e.department_id = :department_id";
                $params['department_id'] = $departmentFilter;
            }

            // Handle search filter
            if ($searchQuery) {
                $whereConditions[] = "(s.name LIKE :search_name OR CAST(s.id AS CHAR) LIKE :search_id OR s.email LIKE :search_email)";
                $params['search_name'] = '%' . $searchQuery . '%';
                $params['search_id'] = '%' . $searchQuery . '%';
                $params['search_email'] = '%' . $searchQuery . '%';
            }

            // Handle validation status filter
            if ($statusFilter) {
                if ($statusFilter === 'Not Validated') {
                    $whereConditions[] = "v.id IS NULL";
                } elseif ($statusFilter === 'Validated') {
                    // Validated = any validation record exists (At Post OR Not At Post)
                    $whereConditions[] = "v.id IS NOT NULL";
                } else {
                    $whereConditions[] = "v.validation_status = :status";
                    $params['status'] = $statusFilter;
                }
            }

            $whereClause = implode(' AND ', $whereConditions);

            // Get total count first
            $countQuery = "
                SELECT COUNT(DISTINCT s.id) as total
                FROM validation_staff s
                LEFT JOIN units u ON s.unit_id = u.id
                LEFT JOIN staff_employment_info e ON s.id = e.staff_id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN validations v ON s.id = v.staff_id 
                    AND v.month = :month 
                    AND v.year = :year
                WHERE $whereClause
            ";

            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $totalCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalCount / $perPage);

            // Build query with joins and pagination
            $query = "
                SELECT 
                    s.id,
                    s.name,
                    s.email,
                    s.role,
                    s.unit_id as unitId,
                    u.name as unitName,
                    e.department_id as departmentId,
                    d.name as departmentName,
                    v.validation_status as validationStatus,
                    v.comments
                FROM validation_staff s
                LEFT JOIN units u ON s.unit_id = u.id
                LEFT JOIN staff_employment_info e ON s.id = e.staff_id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN validations v ON s.id = v.staff_id 
                    AND v.month = :month 
                    AND v.year = :year
                WHERE $whereClause
                ORDER BY s.name
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $this->db->prepare($query);
            
            // Bind pagination parameters separately
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, [
                'success' => true,
                'staff' => $staff,
                'pagination' => [
                    'currentPage' => $page,
                    'perPage' => $perPage,
                    'totalItems' => $totalCount,
                    'totalPages' => $totalPages,
                    'hasNextPage' => $page < $totalPages,
                    'hasPrevPage' => $page > 1
                ]
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to retrieve staff: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/validation/staff",
        summary: "Create new staff",
        description: "Add a new staff member to the system",
        tags: ["Validation Staff"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "unitId"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", example: "password123"),
                    new OA\Property(property: "unitId", type: "string"),
                    new OA\Property(property: "role", type: "string", enum: ["staff", "incharge", "accountant", "admin"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Staff created successfully"),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function createStaff(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $name = $data['name'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $unitId = $data['unitId'] ?? '';
            $role = $data['role'] ?? 'staff';

            if (empty($name) || empty($email) || empty($password) || empty($unitId)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Missing required fields'
                ], 400);
            }

            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM validation_staff WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Email already exists'
                ], 400);
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $this->db->prepare("
                INSERT INTO validation_staff (name, email, password, role, unit_id)
                VALUES (:name, :email, :password, :role, :unit_id)
            ");

            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role,
                'unit_id' => $unitId
            ]);

            $id = (int)$this->db->lastInsertId();

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Staff created successfully',
                'staff' => [
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'unitId' => $unitId
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to create staff: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/validation/departments",
        summary: "Get all departments",
        description: "Retrieve all departments",
        tags: ["Validation Staff"],
        responses: [
            new OA\Response(response: 200, description: "Departments retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function getDepartments(Request $request, Response $response): Response
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    id,
                    name,
                    description
                FROM departments
                ORDER BY name
            ");

            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, [
                'success' => true,
                'departments' => $departments
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to retrieve departments: ' . $e->getMessage()
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
