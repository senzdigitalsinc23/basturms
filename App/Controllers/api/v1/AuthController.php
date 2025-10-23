<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\LoggingService;

class AuthController
{
    private LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
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

            // Simulate authentication logic here
            // In a real application, you would validate credentials against the database
            $isValid = $this->validateCredentials($email, $password);

            if ($isValid) {
                $this->loggingService->logAuth('login', 'success', "User logged in: {$email}");
                $response->setContent(json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => ['user_id' => 'user123', 'email' => $email]
                ]));
            } else {
                $this->loggingService->logAuth('login', 'failure', "Invalid credentials for: {$email}");
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'data' => null
                ]));
                $response->setStatusCode(401);
            }

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

    private function validateCredentials(string $email, string $password): bool
    {
        // This is a placeholder - implement actual authentication logic
        // For demo purposes, accept any email/password combination
        return !empty($email) && !empty($password);
    }
}