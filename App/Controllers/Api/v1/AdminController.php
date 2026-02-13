<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\AdminService;
use App\Services\AuthService;
use App\Exceptions\AdminException;
use App\Services\ValidationService;

use OpenApi\Attributes as OA;

/**
 * Controller for administrative API requests.
 */
#[OA\Tag(
    name: "Administration",
    description: "API endpoints for system administration and user management"
)]
class AdminController
{
    private AdminService $adminService;
    private AuthService $authService;

    /**
     * @param AdminService $adminService
     * @param AuthService $authService
     */
    public function __construct(AdminService $adminService, AuthService $authService)
    {
        $this->adminService = $adminService;
        $this->authService = $authService;
    }

    #[OA\Get(
        path: "/admin/users",
        summary: "List all users",
        description: "Retrieve a list of all registered users in the system.",
        tags: ["Administration"],
        security: [["ApiKeyAuth" => []], ["BearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of users")
        ]
    )]
    public function users(Request $request, Response $response): Response
    {
        try {
            $result = $this->adminService->getAllUsers();

            $response->setContent((string)json_encode(['success' => true, 'message' => 'Users retrieved successfully', 'data' => $result]));
            $response->setStatusCode(200);
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setContent((string)json_encode([
                'success' => false, 
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Retrieves a specific user by ID.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getUser(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getQuery('id');
            
            if (!$id) {
                $response->setStatusCode(400);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'User ID is required',
                    'data' => null
                ]));
                return $response;
            }

            $result = $this->adminService->getUserById($id);

            $response->setContent((string)json_encode(['success' => true, 'message' => 'User retrieved successfully', 'data' => $result]));
            return $response;

        } catch (AdminException $e) {
            $response->setStatusCode($e->getCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Updates an existing user.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateUser(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $validation = (new ValidationService())->validate($data, [
                'id' => 'required',
                'email' => 'required|email',
                'username' => 'required',
            ]);
            if (!$validation['success']) {
                $response->setStatusCode(422);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]));
                return $response;
            }
            $id = (int) $validation['data']['id'];
            unset($validation['data']['id']);
            $result = $this->adminService->updateUser($id, $validation['data']);
            $response->setContent((string)json_encode(['success' => true, 'message' => 'User updated successfully', 'data' => $result]));
            return $response;

        } catch (AdminException $e) {
            $response->setStatusCode($e->getCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Deletes a user.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deleteUser(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getPost('id');
            
            if (!$id) {
                $response->setStatusCode(400);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'User ID is required',
                    'data' => null
                ]));
                return $response;
            }

            $result = $this->adminService->deleteUser($id);

            $response->setContent((string)json_encode(['success' => true, 'message' => 'User deleted successfully', 'data' => $result]));
            return $response;

        } catch (AdminException $e) {

            $response->setStatusCode($e->getCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Retrieves users statistics.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function stats(Request $request, Response $response): Response
    {
        try {
            $result = $this->adminService->getUsersStats();

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode(['success' => true, 'message' => 'Users stats retrieved successfully', 'data' => $result]));
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Unlock a user account.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Post(
        path: "/admin/users/unlock",
        summary: "Unlock user account",
        description: "Unlock a user account that has been locked due to failed login attempts or admin action.",
        tags: ["Administration"],
        security: [["ApiKeyAuth" => []], ["BearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Account unlocked successfully"),
            new OA\Response(response: 404, description: "User not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
    public function unlockUserAccount(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            
            if (empty($data['email'])) {
                $response->setStatusCode(400);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Email is required',
                    'data' => null
                ]));
                return $response;
            }

            $result = $this->authService->unlockAccount($data['email']);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode($result));
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode($e->getCode() ?: 500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;
        }
    }
}
