<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\AdminService;
use App\Exceptions\AdminException;
use App\Services\ValidationService;

class AdminController
{
    private AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function users(Request $request, Response $response): Response
    {
        try {
            $result = $this->adminService->getAllUsers();

            $response->setContent(json_encode(['success' => true, 'message' => 'Users retrieved successfully', 'data' => $result]));
            $response->setStatusCode(200);
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setContent(json_encode([
                'success' => false, 
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    public function getUser(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getQuery('id');
            
            if (!$id) {
                $response->setStatusCode(400);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'User ID is required',
                    'data' => null
                ]));
                return $response;
            }

            $result = $this->adminService->getUserById($id);

            $response->setContent(json_encode(['success' => true, 'message' => 'User retrieved successfully', 'data' => $result]));
            return $response;

        } catch (AdminException $e) {
            $response->setStatusCode($e->getCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

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
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]));
            }
            $id = (int) $validation['data']['id'];
            unset($validation['data']['id']);
            $result = $this->adminService->updateUser($id, $validation['data']);
            $response->setContent(json_encode(['success' => true, 'message' => 'User updated successfully', 'data' => $result]));
            return $response;

        } catch (AdminException $e) {
            $response->setStatusCode($e->getCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    public function deleteUser(Request $request, Response $response): Response
    {
        try {
            $id = (int) $request->getPost('id');
            
            if (!$id) {
                $response->setStatusCode(400);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'User ID is required',
                    'data' => null
                ]));
                return $response;
            }

            $result = $this->adminService->deleteUser($id);

            $response->setContent(json_encode(['success' => true, 'message' => 'User deleted successfully', 'data' => $result]));
            return $response;

        } catch (AdminException $e) {

            $response->setStatusCode($e->getCode());
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    public function stats(Request $request, Response $response): Response
    {
        try {
            $result = $this->adminService->getUsersStats();

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => true, 'message' => 'Users stats retrieved successfully', 'data' => $result]));
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'data' => null,
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }
}