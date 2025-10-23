<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\LoggingService;
use App\Services\AuthService;
use App\DTOs\LoginRequestDTO;

class AuthController
{
    private LoggingService $loggingService;
    private AuthService $authService;

    public function __construct(LoggingService $loggingService, AuthService $authService)
    {
        $this->loggingService = $loggingService;
        $this->authService = $authService;
    }

    public function login(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $email = $data['email'] ?? null;
            $password = $data['password'] ?? null;

            if (!$email || !$password) {
                $this->loggingService->logAuth('login', 'failure', 'Missing credentials');
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'Missing credentials',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            $loginDTO = new LoginRequestDTO($email, $password);
            $result = $this->authService->login($loginDTO);

            $this->loggingService->logAuth('login', 'success', "User logged in: {$email}");
            $response->setContent(json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $result['user'],
                    'token' => $result['token'],
                ]
            ]));
            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logAuth('login', 'failure', "Login error: " . $e->getMessage());
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            $response->setStatusCode(500);
            return $response;
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getPost('user_id') ?? 'unknown';
            
            $this->loggingService->logAuth('logout', 'success', "User logged out: {$userId}");
            
            $response->setContent(json_encode([
                'success' => true,
                'message' => 'Logout successful',
                'data' => null
            ]));

            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAuth('logout', 'failure', "Logout error: " . $e->getMessage());
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            $response->setStatusCode(500);
            return $response;
        }
    }
}