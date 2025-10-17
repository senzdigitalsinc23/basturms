<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentService;
use App\Services\ValidationService;
use App\Exceptions\StudentException;
use App\Exceptions\ValidationException;

class StudentController
{
    private StudentService $studentService;
    private ValidationService $validationService;

    public function __construct(StudentService $studentService, ValidationService $validationService)
    {
        $this->studentService = $studentService;
        $this->validationService = $validationService;
    }

    public function show(Request $request, Response $response): Response
    {
        try {
            $studentNo = $request->getQuery()['student_no'] ?? null;
            if (!$studentNo) {
                $response->json([
                    'success' => false,
                    'message' => 'Missing student_no',
                    'data' => null
                ], 400);
                return $response;
            }

            $student = $this->studentService->getStudentWithRelations((string)$studentNo);
            if (!$student) {
                $response->json([
                    'success' => false,
                    'message' => 'Student not found',
                    'data' => null
                ], 404);
                return $response;
            }

            $response->json([
                'success' => true,
                'message' => 'Student retrieved successfully',
                'data' => $student
            ], 200);
            return $response;
        } catch (\Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
            return $response;
        }
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            // Validate search parameters
            $searchData = $request->getQuery();
            $validation = $this->validationService->validateStudentSearch($searchData);
            
            if (!$validation['success']) {
                $response->json([
                    'success' => false, 
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]);
                return $response;
            }

            $validatedData = $validation['data'];
            
            $result = $this->studentService->getStudents(
                $validatedData['page'],
                $validatedData['limit'],
                $validatedData['search'],
                $validatedData['status']                
                
            );

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'GET,OPTIONS');
            $response->setContent(json_encode([
                'success' => true, 
                'message' => 'Students retrieved successfully',
                'data' => [
                    'students' => $result['students'],
                    'pagination' => $result['pagination']
                ]
            ]));
            
            return $response;

        } catch (\Exception $e) {
             $response->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
            
            return $response;
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            
            // Validate input data
            $validation = $this->validationService->validateStudentData($data);
            
            if (!$validation['success']) {
                 $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ], 422);
                
                return $response;
            }

            // Create student
            $result = $this->studentService->createStudent($validation['data']);

            $response->json(['success' => true, 'message' => 'Student created successfully', 'data' => $result], 201);

            return $response;

        } catch (StudentException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], $e->getCode());
            
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
            
            return $response;
        }
    }

    public function freeze(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            
            // Validate input data
            $validation = $this->validationService->validateStudentStatusUpdate($data);
            
            if (!$validation['success']) {
                $response->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ], 422);
                
                return $response;
            }

            $validatedData = $validation['data'];
            
            // Get student by ID first (you'll need to implement this in repository)
            // For now, we'll use the student number directly
            $result = $this->studentService->updateStudentStatus(
                (string) $validatedData['id'], 
                $validatedData['status']
            );

            $response->json(['success' => true, 'message' => 'Student freezed successfully', 'data' => $result], 200);

            return $response;

        } catch (StudentException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], $e->getCode());
            
            return $response;

        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ], $e->getCode());
            
            return $response;
        }
    }
}
