<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentService;
use App\Services\ValidationService;
use App\Services\LoggingService;
use App\Exceptions\StudentException;
use App\Exceptions\ValidationException;

class StudentController
{
    private StudentService $studentService;
    private ValidationService $validationService;
    private LoggingService $loggingService;

    public function __construct(StudentService $studentService, ValidationService $validationService, LoggingService $loggingService)
    {
        $this->studentService = $studentService;
        $this->validationService = $validationService;
        $this->loggingService = $loggingService;
    }

    public function show(Request $request, Response $response): Response
    {
        //echo json_encode($request->getPost());exit;
        try {
            $studentNo = $request->getPost('student_no') ?? null;
            if (!$studentNo) {
                $this->loggingService->logAudit('view_student_failed', 'Missing student_no');
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'Missing student_no',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            $student = $this->studentService->getStudentWithRelations((string)$studentNo);
            if (!$student) {
                $this->loggingService->logAudit('view_student_failed', "Student not found: {$studentNo}");
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'Student not found',
                    'data' => null
                ]));
                $response->setStatusCode(404);
                return $response;
            }

            $this->loggingService->logAudit('view_student_success', "Student retrieved: {$studentNo}");
            $response->setContent(json_encode([
                'success' => true,
                'message' => 'Student retrieved successfully',
                'data' => $student
            ]));

            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logAudit('view_student_error', "Error retrieving student: " . $e->getMessage());
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

    public function index(Request $request, Response $response): Response
    {
        try {
            // Validate search parameters
            $searchData = $request->getQuery();
            $validation = $this->validationService->validateStudentSearch($searchData);
            
            if (!$validation['success']) {
                $response->setContent(json_encode([
                    'success' => false, 
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]));
                
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
             $response->setContent(json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            
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
                $this->loggingService->logAudit('create_student_failed', 'Validation failed: ' . json_encode($validation['errors']));
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]));
                
                return $response;
            }

            // Create student
            $result = $this->studentService->createStudent($validation['data']);

            $this->loggingService->logAudit('create_student_success', "Student created: " . ($result['data']['student_no'] ?? 'unknown'));
            $response->setStatusCode(201);
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'POST,OPTIONS');

            $response->setContent(json_encode(['success' => true, 'message' => 'Student created successfully', 'data' => $result]));

            return $response;

        } catch (StudentException $e) {
            $this->loggingService->logAudit('create_student_error', "Student creation failed: " . $e->getMessage());
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('create_student_error', "Student creation error: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            
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
                $this->loggingService->logAudit('freeze_student_failed', 'Validation failed: ' . json_encode($validation['errors']));
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]));
                
                return $response;
            }

            $validatedData = $validation['data'];
            
            // Get student by ID first (you'll need to implement this in repository)
            // For now, we'll use the student number directly
            $result = $this->studentService->updateStudentStatus(
                (string) $validatedData['id'], 
                $validatedData['status']
            );

            $this->loggingService->logAudit('freeze_student_success', "Student status updated: " . $validatedData['id'] . " to " . $validatedData['status']);
            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'POST,OPTIONS');
            $response->setContent(json_encode(['success' => true, 'message' => 'Student freezed successfully', 'data' => $result]));

            return $response;

        } catch (StudentException $e) {
            $this->loggingService->logAudit('freeze_student_error', "Student freeze failed: " . $e->getMessage());
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('freeze_student_error', "Student freeze error: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;
        }
    }
}
