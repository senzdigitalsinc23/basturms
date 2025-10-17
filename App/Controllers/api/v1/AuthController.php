<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\AuthValidationService;
use App\DTOs\LoginRequestDTO;
use App\DTOs\RegisterRequestDTO;
use App\Exceptions\AuthException;

class AuthController 
{
    private AuthService $authService;
    private AuthValidationService $validationService;

    public function __construct(AuthService $authService, AuthValidationService $validationService)
    {
        $this->authService = $authService;
        $this->validationService = $validationService;
    }

    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            
            // Validate input data
            $validation = $this->validationService->validateRegisterData($data);
            
            if (!$validation['success']) {
                $response->setStatusCode(422);
                $response->setHeader('Content-Type', 'application/json');
                $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]);
                return $response;
            }

            // Create register DTO
            $registerData = RegisterRequestDTO::fromArray($validation['data']);

            // Register user
            $result = $this->authService->register($registerData);

            $response->setStatusCode(201);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => $result['user']
            ]);
            return $response;

        } catch (AuthException $e) {
            $response->setStatusCode($e->getCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]);
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]);
            return $response;
        }
    }

    public function login(Request $request, Response $response) 
    {
        try {
            $data = $request->getPost();

            // Validate input data
            $validation = $this->validationService->validateLoginData($data);
            
                        
            if (!$validation['success']) {
                $response->setStatusCode(422);
                $response->setHeader('Content-Type', 'application/json');
                $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]);
                
                return $response;
            }

            // Create login DTO
            $loginData = LoginRequestDTO::fromArray($validation['data']);

            // Login user
            $result = $this->authService->login($loginData);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');

            //echo json_encode($result);exit;
            $response->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $result['user'],
                'token' => $result['token'] ?? null
            ]);exit;
            //return $response;exit;

        } catch (AuthException $e) {
            $response->setStatusCode($e->getCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]);exit;
            //return $response;exit;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]);
            return $response;exit;
        }
    }

    public function me(Request $request, Response $response): Response
    {
        try {
            $user = $this->authService->getCurrentUser();

            if (!$user) {
                $response->setStatusCode(401);
                $response->setHeader('Content-Type', 'application/json');
                $response->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please log in',
                    'data' => null
                ]);
                return $response;
            }

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => true,
                'message' => 'User profile retrieved successfully',
                'data' => $user->toArrayWithoutPassword()
            ]);
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]);
            return $response;
        }
    }

    public function profile(Request $request, Response $response): Response
    {
        return $this->me($request, $response);
    }

    public function logout(Request $request, Response $response)
    {
        try {
            $result = $this->authService->logout();

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => true,
                'message' => 'Logout success',
                'data' => []
            ]);

            return '';
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]);
            return $response;
        }
    }

    public function changePassword(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            
            // Validate input data
            $validation = $this->validationService->validatePasswordChangeData($data);
            
            if (!$validation['success']) {
                $response->setStatusCode(422);
                $response->setHeader('Content-Type', 'application/json');
                $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]);
                return $response;
            }

            // Get current user
            $user = $this->authService->getCurrentUser();
            if (!$user) {
                $response->setStatusCode(401);
                $response->setHeader('Content-Type', 'application/json');
                $response->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please log in',
                    'data' => null
                ]);
                return $response;
            }

            // Change password
            $result = $this->authService->changePassword(
                $user->id,
                $validation['data']['current_password'],
                $validation['data']['new_password']
            );

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => true,
                'message' => 'Password changed successfully',
                'data' => $result
            ]);
            return $response;

        } catch (AuthException $e) {
            $response->setStatusCode($e->getCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]);
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]);
            return $response;
        }
    }
} 