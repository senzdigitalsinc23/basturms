<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\LoggingService;
use App\DTOs\LoginRequestDTO;
use App\DTOs\RegisterRequestDTO;
use App\Exceptions\ValidationException;
use App\Exceptions\AuthException;
use App\Core\Session;

use OpenApi\Attributes as OA;

/**
 * Controller for handling authentication-related API requests.
 */
#[OA\Tag(
    name: "Authentication",
    description: "API endpoints for user login, logout, and session management"
)]
class AuthController extends Controller
{
    private LoggingService $loggingService;
    private AuthService $authService;

    /**
     * @param LoggingService $loggingService
     * @param AuthService $authService
     */
    public function __construct(LoggingService $loggingService, AuthService $authService)
    {
        $this->loggingService = $loggingService;
        $this->authService = $authService;
    }

    #[OA\Post(
        path: "/login",
        summary: "User login",
        description: "Authenticates a user and returns a JWT token.",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Login successful", content: new OA\JsonContent(properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "token", type: "string"),
                new OA\Property(property: "user", type: "object")
            ])),
            new OA\Response(response: 400, description: "Invalid credentials"),
            new OA\Response(response: 429, description: "Too many login attempts")
        ]
    )]
    public function login(Request $request, Response $response): Response
    {
        
        // Brute-force lockout check
        $bruteForce = new \App\Middleware\BruteForceLockoutMiddleware();
        $bruteForce->handle();
        try {
            $data = $request->getPost();
            $email = (string)($data['email'] ?? '');
            $password = (string)($data['password'] ?? '');

            if (!$email || !$password) {
                $bruteForce->recordFailure();
                $this->loggingService->logAuth('login', 'failure', 'Missing credentials', null);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Missing credentials',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            try {
                $loginDTO = new LoginRequestDTO($email, $password);
                $result = $this->authService->login($loginDTO);
            } catch (\Exception $e) {
                $bruteForce->recordFailure();
                throw $e;
            }

            $bruteForce->clear();
            $this->loggingService->logAuth('login', 'success', "User logged in: {$email}", (string)$result['user']['user_id']);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                ]
            ]));
            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logAuth('login', 'failure', "Login error: " . $e->getMessage(), null);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Internal server error' ,
                'data' => null
            ]));
            $response->setStatusCode(500);
            return $response;
        }
    }

    /**
     * Handles user logout.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Get(
        path: "/logout",
        summary: "Logout User",
        description: "Logout the currently authenticated user and invalidate their session.",
        tags: ["Authentication"],
        security: [["ApiKeyAuth" => []]],
        /* parameters: [
            new OA\Parameter(name: "academic_year", in: "query", description: "Search by academic year string", required: false, schema: new OA\Schema(type: "string"))
        ], */
        responses: [
            new OA\Response(response: 200, description: "User logged out successfully", content: new OA\JsonContent(properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Logout successful")
            ])),
            new OA\Response(response: 401, description: "Unauthorized"),
        ]
    )]
    public function logout(Request $request, Response $response): Response
    {
        Session::destroy();

        $response->setHeader('Content-Type', 'application/json');
        $response->setContent((string)json_encode([
            'success' => true,
            'message' => 'Logout successful'
        ]));
        $response->setStatusCode(200);
        return $response;
    }

    /**
     * Handles user registration.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $email = (string)($data['email'] ?? '');
            $password = (string)($data['password'] ?? '');
            $firstName = (string)($data['first_name'] ?? '');
            $lastName = (string)($data['last_name'] ?? '');

            if (!$email || !$password || !$firstName || !$lastName) {
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Missing registration credentials',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            $name = trim($firstName . ' ' . $lastName);
            $registerDTO = new \App\DTOs\RegisterRequestDTO($name, $email, $password);
            $result = $this->authService->register($registerDTO);

            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $result['user']
                ]
            ]));

            return $response;
        } catch (\Exception $e) {
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            $response->setStatusCode(400);
            return $response;
        }
    }

    /**
     * Retrieves the currently authenticated user's profile.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function me(Request $request, Response $response): Response
    {
        try {
            $user = (array)Session::get('user');

            if (empty($user)) {
                $response->setStatusCode(401);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'data' => null
                ]));
                return $response;
            }

            $this->loggingService->logAuth('me', 'success', "User profile accessed", (string)($user['user_id'] ?? ''));
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => 'User profile retrieved',
                'data' => $user
            ]));
            return $response;

        } catch (AuthException $e) {
            $statusCode = 401; // Unauthorized by default
            if ($e->getMessage() === 'Token expired') {
                $statusCode = 403; // Forbidden for expired token
            }
            $this->loggingService->logAuth('me', 'failure', "Profile access error: " . $e->getMessage());
            $response->setStatusCode($statusCode);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logAuth('me', 'failure', "Profile access error: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred while retrieving profile.',
                'data' => null
            ]));
            return $response;
        }
    }

    /**
     * Alias for me() method for backward compatibility.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function profile(Request $request, Response $response): Response
    {
        return $this->me($request, $response);
    }

    /**
     * Reset user password.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Post(
        path: "/auth/reset-password",
        summary: "Reset User Password",
        description: "Reset a user's password with new password and confirmation.",
        tags: ["Authentication"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id", "new_password", "confirm_password"],
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "new_password", type: "string", format: "password", example: "NewPassword123"),
                    new OA\Property(property: "confirm_password", type: "string", format: "password", example: "NewPassword123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Password reset successful", content: new OA\JsonContent(properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Password reset successfully")
            ])),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function resetPassword(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $userId = isset($data['user_id']) ? $data['user_id'] : 0;
            $newPassword = (string)($data['new_password'] ?? '');
            $confirmPassword = (string)($data['confirm_password'] ?? '');

            //echo json_encode($data);exit;

            // Validate required fields
            if (!$userId || !$newPassword || !$confirmPassword) {
                $this->loggingService->logAuth('reset-password', 'failure', 'Missing required fields', null);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: user_id, new_password, and confirm_password are required',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            // Call the service to reset password
            $result = $this->authService->resetPassword($userId, $newPassword, $confirmPassword);

            $this->loggingService->logAuth('reset-password', 'success', "Password reset for user ID: {$userId}", (string)$userId);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => $result['message'],
                'data' => null
            ]));
            $response->setStatusCode(200);
            return $response;

        } catch (AuthException $e) {
            $this->loggingService->logAuth('reset-password', 'failure', "Password reset error: " . $e->getMessage(), null);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            $response->setStatusCode(400);
            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logAuth('reset-password', 'failure', "Password reset error: " . $e->getMessage(), null);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'An error occurred while resetting password',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            $response->setStatusCode(500);
            return $response;
        }
    }

    /**
     * Handle forgot password request.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Post(
        path: "/auth/forgot-password",
        summary: "Forgot Password",
        description: "Request a password reset. A new random password will be sent to the user's email.",
        tags: ["Authentication"],
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
            new OA\Response(response: 200, description: "Password reset processed", content: new OA\JsonContent(properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "If an account exists with this email, a password reset has been sent.")
            ])),
            new OA\Response(response: 400, description: "Invalid email format"),
            new OA\Response(response: 429, description: "Too many requests"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function forgotPassword(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $email = (string)($data['email'] ?? '');

            // Validate email is provided
            if (!$email) {
                $this->loggingService->logAuth('forgot-password', 'failure', 'Missing email', null);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Email is required',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            // Call the service to handle forgot password
            $result = $this->authService->forgotPassword($email);

            $this->loggingService->logAuth('forgot-password', 'success', "Forgot password request for: {$email}", null);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => $result['message'],
                'data' => null
            ]));
            $response->setStatusCode(200);
            return $response;

        } catch (AuthException $e) {
            $this->loggingService->logAuth('forgot-password', 'failure', "Forgot password error: " . $e->getMessage(), null);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => ""
            ]));
            $response->setStatusCode(400);
            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logAuth('forgot-password', 'failure', "Forgot password error: " . $e->getMessage(), null);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            $response->setStatusCode(500);
            return $response;
        }
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Put(
        path: "/profile/update",
        summary: "Update user profile",
        description: "Updates the current user's profile information (username and email only). Use /auth/reset-password for password changes.",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "username", type: "string", example: "john_doe", description: "New username (optional)"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com", description: "New email address (optional)")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Profile updated successfully", content: new OA\JsonContent(properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Profile updated successfully"),
                new OA\Property(property: "data", type: "object", description: "Updated user profile")
            ])),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 409, description: "Username or email already exists"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            // Get current user from session
            $currentUser = Session::get('user');
            if (!$currentUser || !isset($currentUser['id'])) {
                $response->setStatusCode(401);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'data' => null
                ]));
                return $response;
            }

            $userId = (int)$currentUser['id'];
            $data = $request->getPost();

            // Create DTO from request data
            $profileUpdateDTO = \App\DTOs\ProfileUpdateDTO::fromArray($data);

            // Initialize profile service
            $profileService = new \App\Services\ProfileService();

            // Validate permissions (user can update their own profile)
            $profileService->validateUpdatePermissions($userId, $userId);

            // Update profile
            $updatedUser = $profileService->updateProfile($userId, $profileUpdateDTO);

            $this->loggingService->logAuth('profile-update', 'success', "Profile updated for user: {$userId}", (string)$userId);

            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedUser
            ]));
            $response->setStatusCode(200);
            return $response;

        } catch (\App\Exceptions\ValidationException $e) {
            $this->loggingService->logAuth('profile-update', 'failure', "Validation error: " . $e->getMessage(), (string)($currentUser['id'] ?? ''));
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->getErrors(),
                'data' => null
            ]));
            return $response;

        } catch (\App\Exceptions\AuthException $e) {
            $this->loggingService->logAuth('profile-update', 'failure', "Auth error: " . $e->getMessage(), (string)($currentUser['id'] ?? ''));
            $response->setStatusCode(401);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\App\Exceptions\ConflictException $e) {
            $this->loggingService->logAuth('profile-update', 'failure', "Conflict error: " . $e->getMessage(), (string)($currentUser['id'] ?? ''));
            $response->setStatusCode(409);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAuth('profile-update', 'failure', "Profile update error: " . $e->getMessage(), (string)($currentUser['id'] ?? ''));
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'An error occurred while updating profile',
                'data' => null
            ]));
            return $response;
        }
    }

    /**
     * Get detailed user profile
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Get(
        path: "/profile/details",
        summary: "Get detailed user profile",
        description: "Retrieves detailed profile information for the current user.",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Profile retrieved successfully", content: new OA\JsonContent(properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Profile retrieved successfully"),
                new OA\Property(property: "data", type: "object", description: "User profile data")
            ])),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function getProfileDetails(Request $request, Response $response): Response
    {
        try {
            // Get current user from session
            $currentUser = Session::get('user');
            if (!$currentUser || !isset($currentUser['id'])) {
                $response->setStatusCode(401);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'data' => null
                ]));
                return $response;
            }

            $userId = (int)$currentUser['id'];

            // Initialize profile service
            $profileService = new \App\Services\ProfileService();

            // Get profile details
            $profileData = $profileService->getProfile($userId);

            $this->loggingService->logAuth('profile-details', 'success', "Profile details retrieved for user: {$userId}", (string)$userId);

            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => $profileData
            ]));
            $response->setStatusCode(200);
            return $response;

        } catch (\App\Exceptions\AuthException $e) {
            $this->loggingService->logAuth('profile-details', 'failure', "Auth error: " . $e->getMessage(), (string)($currentUser['id'] ?? ''));
            $response->setStatusCode(401);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAuth('profile-details', 'failure', "Profile details error: " . $e->getMessage(), (string)($currentUser['id'] ?? ''));
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'An error occurred while retrieving profile',
                'data' => null
            ]));
            return $response;
        }
    }

    /**
     * Update user profile image
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Put(
        path: "/profile/image",
        summary: "Update profile image",
        description: "Updates the current user's profile image by providing an upload ID.",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["upload_id"],
                properties: [
                    new OA\Property(property: "upload_id", type: "integer", example: 123, description: "ID of the uploaded image from uploads table")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Profile image updated successfully"),
            new OA\Response(response: 400, description: "Invalid upload ID"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function updateProfileImage(Request $request, Response $response): Response
    {
        try {
            // Get current user from session
            $currentUser = Session::get('user');
            if (!$currentUser || !isset($currentUser['id'])) {
                $response->setStatusCode(401);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'data' => null
                ]));
                return $response;
            }

            $userId = (int)$currentUser['id'];
            $data = $request->getPost();

            if (!isset($data['upload_id']) || !is_numeric($data['upload_id'])) {
                $response->setStatusCode(400);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Upload ID is required and must be a number',
                    'data' => null
                ]));
                return $response;
            }

            $uploadId = (int)$data['upload_id'];

            // Initialize profile service
            $profileService = new \App\Services\ProfileService();

            // Update profile image
            $updatedUser = $profileService->updateProfileImage($userId, $uploadId);

            $this->loggingService->logAuth('profile-image-update', 'success', "Profile image updated for user: {$userId}", (string)$userId);

            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => 'Profile image updated successfully',
                'data' => $updatedUser
            ]));
            $response->setStatusCode(200);
            return $response;

        } catch (\App\Exceptions\AuthException $e) {
            $this->loggingService->logAuth('profile-image-update', 'failure', "Auth error: " . $e->getMessage(), (string)($currentUser['id'] ?? ''));
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAuth('profile-image-update', 'failure', "Profile image update error: " . $e->getMessage(), (string)($currentUser['id'] ?? ''));
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'An error occurred while updating profile image',
                'data' => null
            ]));
            return $response;
        }
    }

    /**
     * Remove user profile image
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Delete(
        path: "/profile/image",
        summary: "Remove profile image",
        description: "Removes the current user's profile image.",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Profile image removed successfully"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function removeProfileImage(Request $request, Response $response): Response
    {
        try {
            // Get current user from session
            $currentUser = Session::get('user');
            if (!$currentUser || !isset($currentUser['id'])) {
                $response->setStatusCode(401);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'data' => null
                ]));
                return $response;
            }

            $userId = (int)$currentUser['id'];

            // Initialize profile service
            $profileService = new \App\Services\ProfileService();

            // Remove profile image
            $updatedUser = $profileService->removeProfileImage($userId);

            $this->loggingService->logAuth('profile-image-remove', 'success', "Profile image removed for user: {$userId}", (string)$userId);

            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => 'Profile image removed successfully',
                'data' => $updatedUser
            ]));
            $response->setStatusCode(200);
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAuth('profile-image-remove', 'failure', "Profile image removal error: " . $e->getMessage(), (string)($currentUser['id'] ?? ''));
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'An error occurred while removing profile image',
                'data' => null
            ]));
            return $response;
        }
    }
}
