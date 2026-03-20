<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use Firebase\JWT\JWT;
use PDO;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Validation Auth",
    description: "Authentication endpoints for validation system"
)]
class ValidationAuthController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    #[OA\Post(
        path: "/api/v1/validation/auth/login",
        summary: "Login to validation system",
        description: "Authenticate staff member and return JWT token",
        tags: ["Validation Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "staff@example.com"),
                    new OA\Property(property: "password", type: "string", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Login successful"),
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Email and password are required'
                ], 400);
            }

            $stmt = $this->db->prepare("
                SELECT 
                    s.id,
                    s.name,
                    s.email,
                    s.password,
                    s.role,
                    s.unit_id as unitId,
                    s.password_changed,
                    u.name as unitName
                FROM validation_staff s
                LEFT JOIN units u ON s.unit_id = u.id
                WHERE s.email = :email AND s.deleted_at IS NULL
            ");

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            // Generate JWT token
            $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
            $payload = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'unit_id' => $user['unitId'],
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24) // 24 hours
            ];

            $token = JWT::encode($payload, $jwtSecret, 'HS256');

            // Remove password from response
            unset($user['password']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/validation/auth/me",
        summary: "Get current user",
        description: "Retrieve authenticated user information",
        tags: ["Validation Auth"],
        responses: [
            new OA\Response(response: 200, description: "User retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function me(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            $userId = $user['user_id'] ?? null;

            if (!$userId) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $stmt = $this->db->prepare("
                SELECT 
                    s.id,
                    s.name,
                    s.email,
                    s.role,
                    s.unit_id as unitId,
                    u.name as unitName
                FROM validation_staff s
                LEFT JOIN units u ON s.unit_id = u.id
                WHERE s.id = :id AND s.deleted_at IS NULL
            ");

            $stmt->execute(['id' => $userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'user' => $userData
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to retrieve user: ' . $e->getMessage()
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
