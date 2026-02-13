<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\StudentService;
use App\Services\ValidationService;
use App\Services\LoggingService;
use App\Services\AuthService;
use App\Exceptions\StudentException;
use App\Exceptions\ValidationException;
use App\Models\Student;
use Database\ORM\Model;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Students",
    description: "API endpoints for managing student records, enrollment, and data"
)]
class StudentController
{
    private StudentService $studentService;
    private ValidationService $validationService;
    private LoggingService $loggingService;
    private AuthService $authService;

    /**
     * StudentController constructor.
     *
     * @param StudentService $studentService Service for student operations
     * @param ValidationService $validationService Service for input validation
     * @param LoggingService $loggingService Service for audit logging
     * @param AuthService $authService Service for authentication and authorization session
     */
    public function __construct(StudentService $studentService, ValidationService $validationService, LoggingService $loggingService, AuthService $authService)
    {
        $this->studentService = $studentService;
        $this->validationService = $validationService;
        $this->loggingService = $loggingService;
        $this->authService = $authService;
    }

    #[OA\Post(
        path: "/students/show",
        summary: "Get student details",
        description: "Retrieve detailed information about a specific student including their academic records.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["student_no"],
                properties: [
                    new OA\Property(property: "student_no", type: "string", example: "STU2024001", description: "Student number/ID")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Student details retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Student retrieved successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Validation error or business rule violation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            )
        ]
    )]
    /**
     * Retrieves detailed information about a specific student.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function show(Request $request, Response $response): Response
    {
        try {
            $studentNo = $request->getPost('student_no');
            if (!$studentNo) {
                $this->loggingService->logAudit('view_student_failed', 'Missing student_no');
                $response->setContent((string)json_encode([
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
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Student not found',
                    'data' => null
                ]));
                $response->setStatusCode(404);
                return $response;
            }

            $this->loggingService->logAudit('view_student_success', "Student retrieved: {$studentNo}");
            $response->setContent((string)json_encode([
                'success' => true,
                'message' => 'Student retrieved successfully',
                'data' => $student
            ]));

            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logAudit('view_student_error', "Error retrieving student: " . $e->getMessage());
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            $response->setStatusCode(500);
            return $response;
        }
    }

    #[OA\Get(
        path: "/students",
        summary: "List students",
        description: "Retrieve a paginated list of students with optional search and filtering.",
        tags: ["Students"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Page number for pagination",
                required: false,
                schema: new OA\Schema(type: "integer", default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                description: "Number of records per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Search term for student name or ID",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "class_id",
                in: "query",
                description: "Filter by class ID",
                required: false,
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "status",
                in: "query",
                description: "Filter by student status",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["active", "inactive", "suspended"])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Students retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Students retrieved successfully"),
                        new OA\Property(property: "data", type: "object",
                            properties: [
                                new OA\Property(property: "students", type: "array", items: new OA\Items(type: "object")),
                                new OA\Property(property: "pagination", type: "object",
                                    properties: [
                                        new OA\Property(property: "current_page", type: "integer"),
                                        new OA\Property(property: "total_pages", type: "integer"),
                                        new OA\Property(property: "total_records", type: "integer"),
                                        new OA\Property(property: "per_page", type: "integer")
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Validation error in search parameters",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    /**
     * Lists students with pagination and filtering.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            // Validate search parameters
            $searchData = (array)$request->getQuery();
            $validation = $this->validationService->validateStudentSearch($searchData);
            
            if (!$validation['success']) {
                $response->setContent((string)json_encode([
                    'success' => false, 
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]));
                
                return $response;
            }

            $validatedData = (array)$validation['data'];
            
            $result = $this->studentService->getStudents(
                (int)($validatedData['page'] ?? 1),
                (int)($validatedData['limit'] ?? 10),
                (string)($validatedData['search'] ?? ''),
                (string)($validatedData['status'] ?? 'active')
            );

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'GET,OPTIONS');
            $response->setContent((string)json_encode([
                'success' => true, 
                'message' => 'Students retrieved successfully',
                'data' => [
                    'students' => $result['students'],
                    'pagination' => $result['pagination']
                ]
            ]));
            
            return $response;

        } catch (\Exception $e) {
             $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;
        }
    }

    #[OA\Post(
        path: "/students/create",
        summary: "Create a new student",
        description: "Create a new student record with personal and academic information.",
        tags: ["Students"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["first_name", "last_name", "date_of_birth", "gender", "class_id"],
                properties: [
                    new OA\Property(property: "first_name", type: "string", example: "John", description: "Student's first name"),
                    new OA\Property(property: "last_name", type: "string", example: "Doe", description: "Student's last name"),
                    new OA\Property(property: "date_of_birth", type: "string", format: "date", example: "2005-05-15", description: "Date of birth (YYYY-MM-DD)"),
                    new OA\Property(property: "gender", type: "string", enum: ["Male", "Female"], example: "Male", description: "Student gender"),
                    new OA\Property(property: "class_id", type: "integer", example: 1, description: "Class ID to assign the student to"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@student.school.edu", description: "Student email address"),
                    new OA\Property(property: "phone", type: "string", example: "+1234567890", description: "Student phone number"),
                    new OA\Property(property: "address", type: "string", example: "123 Main Street", description: "Student address"),
                    new OA\Property(property: "emergency_contact_name", type: "string", example: "Jane Doe", description: "Emergency contact name"),
                    new OA\Property(property: "emergency_contact_phone", type: "string", example: "+1234567890", description: "Emergency contact phone")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Student created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Student created successfully"),
                        new OA\Property(property: "data", type: "object", description: "Created student data including generated student number")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Validation error or missing required fields",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    /**
     * Creates a new student record.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = (array)$request->getPost();
            
            // Validate input data
            $validation = $this->validationService->validateStudentData($data);
            
            if (!$validation['success']) {
                $this->loggingService->logAudit('create_student_failed', 'Validation failed: ' . (string)json_encode($validation['errors']));
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]));
                
                return $response;
            }
            
            // Create student
            $result = $this->studentService->createStudent((array)$validation['data']);

            $createdStudentNo = (string)($result['data']['studentInfo']['studentNo']
                ?? $result['data']['student_no']
                ?? 'unknown');
            $this->loggingService->logAudit('create_student_success', "Student created: " . $createdStudentNo, (string)($request->getPost('user_id') ?? ''));
            $response->setStatusCode(201);
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'POST,OPTIONS');

            $response->setContent((string)json_encode(['success' => true, 'message' => 'Student created successfully', 'data' => $result]));

            return $response;

        } catch (StudentException $e) {
            $this->loggingService->logAudit('create_student_error', "Student creation failed: " . $e->getMessage());
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('create_student_error', "Student creation error: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;
        }
    }

    #[OA\Post(
        path: "/students/update",
        summary: "Update student information",
        description: "Update an existing student's personal and academic information.",
        tags: ["Students"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["student_no"],
                properties: [
                    new OA\Property(property: "student_no", type: "string", example: "STU2024001", description: "Student number/ID to update"),
                    new OA\Property(property: "first_name", type: "string", example: "John", description: "Updated first name"),
                    new OA\Property(property: "last_name", type: "string", example: "Doe", description: "Updated last name"),
                    new OA\Property(property: "date_of_birth", type: "string", format: "date", example: "2005-05-15", description: "Updated date of birth"),
                    new OA\Property(property: "gender", type: "string", enum: ["Male", "Female"], example: "Male", description: "Updated gender"),
                    new OA\Property(property: "class_id", type: "integer", example: 2, description: "Updated class ID"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@student.school.edu", description: "Updated email address"),
                    new OA\Property(property: "phone", type: "string", example: "+1234567890", description: "Updated phone number"),
                    new OA\Property(property: "address", type: "string", example: "456 Oak Street", description: "Updated address"),
                    new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "suspended"], example: "active", description: "Student status")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Student updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Student updated successfully"),
                        new OA\Property(property: "data", type: "object", description: "Updated student data")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Validation error or student not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Student not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Student not found")
                    ]
                )
            )
        ]
    )]
    /**
     * Updates an existing student record.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function update(Request $request, Response $response): Response
    {
        try {
            $data = (array)$request->getPost();

            // Validate input data
            $validation = $this->validationService->validateStudentUpdate($data);
            
            if (!$validation['success']) {
                $this->loggingService->logAudit('update_student_failed', 'Validation failed: ' . (string)json_encode($validation['errors']));
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]));
                
                return $response;
            }
            
            // Update student
            $result = $this->studentService->updateStudent((array)$validation['data']);

            $updatedStudentNo = (string)($result['data']['studentInfo']['studentNo']
                ?? $result['data']['student_no']
                ?? 'unknown');
            $this->loggingService->logAudit('update_student_success', "Student updated: " . $updatedStudentNo, (string)($request->getPost('user_id') ?? ''));
            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'POST,OPTIONS');

            $response->setContent((string)json_encode(['success' => true, 'message' => 'Student updated successfully', 'data' => $result]));

            return $response;

        } catch (StudentException $e) {
            $this->loggingService->logAudit('update_student_error', "Student update failed: " . $e->getMessage());
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('update_student_error', "Student update error: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;
        }
    }

    /**
     * Updates student status (freeze/unfreeze).
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function freeze(Request $request, Response $response): Response
    {
        try {
            $data = (array)$request->getPost();
            
            // Validate input data
            $validation = $this->validationService->validateStudentStatusUpdate($data);
            
            if (!$validation['success']) {
                $this->loggingService->logAudit('freeze_student_failed', 'Validation failed: ' . (string)json_encode($validation['errors']));
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'data' => null
                ]));
                
                return $response;
            }

            $validatedData = (array)$validation['data'];
            $studentNos = (array)($validatedData['student_nos'] ?? []);
            $reason = (string)($validatedData['reason'] ?? ($request->getPost('reason') ?? ''));
            $archivedBy = (string)($validatedData['archived_by'] ?? ($request->getPost('user_id') ?? ''));
            
            $result = $this->studentService->updateStudentsStatus(
                $studentNos,
                (string)($validatedData['status'] ?? 'suspended'),
                $reason,
                $archivedBy
            );

            if (!$result['success']) {
                $this->loggingService->logAudit('freeze_student_failed', 'Update failed: ' . (string)($result['message'] ?? 'Unknown error'));
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Update failed',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            $this->loggingService->logAudit(
                'freeze_student_success',
                "Student status updated: " . implode(',', $studentNos) . " to " . (string)($validatedData['status'] ?? 'suspended')
            );
            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'POST,OPTIONS');
            $response->setContent((string)json_encode(['success' => true, 'message' => 'Student status updated successfully', 'data' => $result]));

            return $response;

        } catch (StudentException $e) {
            $this->loggingService->logAudit('freeze_student_error', "Student freeze failed: " . $e->getMessage());
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('freeze_student_error', "Student freeze error: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            
            return $response;
        }
    }

    

    /**
     * Imports students from a CSV file.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function importCsv(Request $request, Response $response): Response
    {
        $data = (array)$request->getPost();

        try {
            // Check if file was uploaded
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
                $this->loggingService->logAudit('csv_import_failed', 'No valid CSV file uploaded', (string)($data['user_id'] ?? ''));
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Please upload a valid CSV file.',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if (!$file) {
                $this->loggingService->logAudit('csv_import_failed', 'Failed to open CSV file', (string)($data['user_id'] ?? ''));
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Failed to open CSV file.',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            // Skip header row and get headers
            $headers = (array)fgetcsv($file); 
            if (empty($headers)) {
                $this->loggingService->logAudit('csv_import_failed', 'Invalid CSV format - no headers found', (string)($data['user_id'] ?? ''));
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Invalid CSV format - no headers found.',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            $totalRecords = 0;
            $totalImported = 0;
            $totalSkipped = 0;
            $errors = [];
            $createdBy = (string)($request->getPost('user_id') ?? '');

            if (empty($createdBy)) {
                $this->loggingService->logAudit('csv_import_failed', 'User ID not provided');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'User ID is required for import.',
                    'data' => null
                ]));
                $response->setStatusCode(401);
                return $response;
            }

            // Process each row
            while (($row = fgetcsv($file)) !== false) {
                $totalRecords++;

                try {
                    // Convert CSV row to nested structure expected by validation service
                    $studentData = $this->mapCsvRowToStudentData($headers, (array)$row, $createdBy);

                    // Check if student already exists (skip before validation to avoid unnecessary processing)
                    $studentNo = (string)($studentData['student_info']['student_no'] ?? '');
                    if (!empty($studentNo) && $this->studentService->getStudentWithRelations($studentNo)) {
                        $totalSkipped++;
                        continue;
                    }

                    // Validate the data
                    $validation = $this->validationService->validateStudentData($studentData);

                    if (!$validation['success']) {
                        $errors[] = [
                            'row' => $totalRecords,
                            'errors' => $validation['errors']
                        ];
                        continue;
                    }

                    // Create the student
                    $result = $this->studentService->createStudent((array)$validation['data']);

                    if ($result['success']) {
                        $totalImported++;
                        $this->loggingService->logAudit('csv_import_student_created',
                            "Student created: " . (string)($result['data']['studentInfo']['studentNo'] ?? 'unknown'),
                            $createdBy
                        );
                    } else {
                        $errors[] = [
                            'row' => $totalRecords,
                            'error' => $result['message'] ?? 'Failed to create student'
                        ];
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $totalRecords,
                        'error' => $e->getMessage()
                    ];
                    $this->loggingService->logAudit('csv_import_error', "Row {$totalRecords}: " . $e->getMessage(), (string)($data['user_id'] ?? ''));
                }
            }

            fclose($file);

            // Prepare response message
            $message = $this->generateImportMessage($totalRecords, $totalImported, $totalSkipped, count($errors));

            $this->loggingService->logAudit('csv_import_completed',
                "Total: {$totalRecords}, Imported: {$totalImported}, Skipped: {$totalSkipped}, Errors: " . count($errors),
                $createdBy
            );

            $response->setContent((string)json_encode([
                'success' => true,
                'message' => $message,
                'data' => [
                    'total_records' => $totalRecords,
                    'imported' => $totalImported,
                    'skipped' => $totalSkipped,
                    'errors' => count($errors),
                    'error_details' => count($errors) > 0 ? array_slice($errors, 0, 10) : [] // Show first 10 errors
                ]
            ]));
            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->loggingService->logAudit('csv_import_error', 'Unexpected error: ' . $e->getMessage());
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred during import.',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
        }

        return $response;
    }

    /**
     * Map CSV row data to the nested structure expected by validation service.
     *
     * @param array $headers CSV header names
     * @param array $row Row data
     * @param string $createdBy User ID who initiated the import
     * @return array Nested data structure
     */
    private function mapCsvRowToStudentData(array $headers, array $row, string $createdBy): array
    {
        $data = [
            'student_info' => [],
            'contact_address' => [],
            'admission_info' => [],
            'guardians' => [],
            'emergency_contact' => []
        ];

        $studentNo = '';

        foreach ($headers as $index => $header) {
            $value = (string)($row[$index] ?? '');

            switch (trim(strtolower((string)$header))) {
                // Student Info
                case 'first_name':
                    $data['student_info']['first_name'] = $value;
                    break;
                case 'last_name':
                    $data['student_info']['last_name'] = $value;
                    break;
                case 'other_name':
                case 'middle_name':
                    $data['student_info']['other_name'] = $value;
                    break;
                case 'gender':
                    $data['student_info']['gender'] = $value;
                    break;
                case 'dob':
                case 'date_of_birth':
                    $data['student_info']['dob'] = $this->normalizeDate($value);
                    break;
                case 'nhis_no':
                case 'nhis_number':
                    $data['student_info']['nhis_no'] = $value;
                    break;

                // Contact Address
                case 'email':
                    $data['contact_address']['email'] = $value;
                    break;
                case 'phone':
                case 'phone_number':
                    $data['contact_address']['phone'] = $value;
                    break;
                case 'country_id':
                case 'country':
                    $data['contact_address']['country_id'] = $value;
                    break;
                case 'city':
                    $data['contact_address']['city'] = $value;
                    break;
                case 'hometown':
                    $data['contact_address']['hometown'] = $value;
                    break;
                case 'residence':
                    $data['contact_address']['residence'] = $value;
                    break;
                case 'house_no':
                case 'house_number':
                    $data['contact_address']['house_no'] = $value;
                    break;
                case 'gps_no':
                case 'gps_number':
                    $data['contact_address']['gps_no'] = $value;
                    break;

                // Admission Info
                case 'admission_status':
                    $data['admission_info']['admission_status'] = $value;
                    break;
                case 'class_assigned':
                case 'class':
                    $data['admission_info']['class_assigned'] = $value;
                    break;
                case 'enrollment_date':
                    $normalizedDate = $this->normalizeDate($value);
                    $data['admission_info']['enrollment_date'] = $normalizedDate;
                    // Generate student number based on enrollment date
                    if (!empty($normalizedDate)) {
                        $studentNo = $this->studentService->generateStudentNo($normalizedDate);
                        $data['student_info']['student_no'] = $studentNo;
                    }
                    break;

                // Guardians
                case 'fathers_name':
                case 'father_name':
                    if (!empty($value)) {
                        $data['guardians'][] = [
                            'guardian_name' => $value,
                            'guardian_relationship' => 'father',
                            'guardian_phone' => '',
                            'guardian_email' => null
                        ];
                    }
                    break;
                case 'fathers_phone':
                case 'father_phone':
                    if (!empty($data['guardians']) && end($data['guardians'])['guardian_relationship'] === 'father') {
                        $data['guardians'][count($data['guardians']) - 1]['guardian_phone'] = $value;
                    }
                    break;
                case 'fathers_email':
                case 'father_email':
                    if (!empty($data['guardians']) && end($data['guardians'])['guardian_relationship'] === 'father') {
                        $data['guardians'][count($data['guardians']) - 1]['guardian_email'] = $value;
                    }
                    break;
                case 'mothers_name':
                case 'mother_name':
                    if (!empty($value)) {
                        $data['guardians'][] = [
                            'guardian_name' => $value,
                            'guardian_relationship' => 'mother',
                            'guardian_phone' => '',
                            'guardian_email' => null
                        ];
                    }
                    break;
                case 'mothers_phone':
                case 'mother_phone':
                    if (!empty($data['guardians']) && end($data['guardians'])['guardian_relationship'] === 'mother') {
                        $data['guardians'][count($data['guardians']) - 1]['guardian_phone'] = $value;
                    }
                    break;
                case 'mothers_email':
                case 'mother_email':
                    if (!empty($data['guardians']) && end($data['guardians'])['guardian_relationship'] === 'mother') {
                        $data['guardians'][count($data['guardians']) - 1]['guardian_email'] = $value;
                    }
                    break;

                // Emergency Contact
                case 'emergency_name':
                    $data['emergency_contact']['emergency_name'] = $value;
                    break;
                case 'emergency_phone':
                    $data['emergency_contact']['emergency_phone'] = $value;
                    break;
                case 'emergency_email':
                    $data['emergency_contact']['emergency_email'] = $value;
                    break;
                case 'emergency_relationship':
                    $data['emergency_contact']['emergency_relationship'] = $value;
                    break;
            }
        }

        // Set created_by
        $data['student_info']['created_by'] = $createdBy;

        return $data;
    }

    /**
     * Generates a summary message after CSV import.
     *
     * @param int $total Total records processed
     * @param int $imported Count of successfully imported records
     * @param int $skipped Count of skipped records (already existed)
     * @param int $errors Count of records with errors
     * @return string Summary message
     */
    private function generateImportMessage(int $total, int $imported, int $skipped, int $errors): string
    {
        if ($imported === $total && $errors === 0) {
            return "✅ All {$total} records imported successfully";
        }

        if ($imported === 0) {
            return "❌ {$total} records failed to import. {$skipped} already exist, {$errors} have errors";
        }

        $parts = [];
        if ($imported > 0) {
            $parts[] = "✅ {$imported} of {$total} students imported successfully";
        }
        if ($skipped > 0) {
            $parts[] = "{$skipped} records already exist";
        }
        if ($errors > 0) {
            $parts[] = "{$errors} records have errors";
        }

        return implode(', ', $parts);
    }

    /**
     * Permanently delete a student (Super Admin only)
     */
    #[OA\Post(
        path: "/students/delete",
        summary: "Delete a student",
        description: "Permanently delete a student record. Requires super admin privileges.",
        tags: ["Students"],
        security: [["ApiKeyAuth" => []], ["BearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["student_no"],
                properties: [
                    new OA\Property(property: "student_no", type: "string", example: "STU2024001", description: "Student number/ID to delete")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Student deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Student deleted successfully")
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - insufficient privileges",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Access denied. Super admin privileges required.")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Student not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Student not found")
                    ]
                )
            )
        ]
    )]
    /**
     * Permanently deletes a student record (Super Admin only).
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function delete(Request $request, Response $response): Response
    {
        try {
            // Check if user is super admin
            $currentUser = $this->authService->getCurrentUser();
            if (!$currentUser || !$currentUser->isSuperAdmin) {
                $this->loggingService->logAudit('delete_student_denied', 'Access denied - not super admin');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Access denied. Super admin privileges required.',
                    'data' => null
                ]));
                $response->setStatusCode(403);
                return $response;
            }

            $data = (array)$request->getPost();
            $studentNo = (string)($data['student_no'] ?? '');

            if (empty($studentNo)) {
                $this->loggingService->logAudit('delete_student_failed', 'Missing student_no parameter');
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Student number is required.',
                    'data' => null
                ]));
                $response->setStatusCode(400);
                return $response;
            }

            // Check if student exists
            $student = $this->studentService->getStudentWithRelations($studentNo);
            if (!$student) {
                $this->loggingService->logAudit('delete_student_failed', "Student not found: {$studentNo}");
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Student not found.',
                    'data' => null
                ]));
                $response->setStatusCode(404);
                return $response;
            }

            // Delete the student
            $result = $this->studentService->deleteStudent($studentNo);

            if ($result['success']) {
                $this->loggingService->logAudit('delete_student_success', "Student permanently deleted: {$studentNo}", (string)$currentUser->userId);
                $response->setContent((string)json_encode([
                    'success' => true,
                    'message' => 'Student permanently deleted successfully.',
                    'data' => [
                        'student_no' => $studentNo,
                        'deleted_at' => date('Y-m-d H:i:s')
                    ]
                ]));
                $response->setStatusCode(200);
            } else {
                $this->loggingService->logAudit('delete_student_failed', "Failed to delete student: {$studentNo} - " . (string)($result['message'] ?? 'Unknown error'));
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to delete student.',
                    'data' => null
                ]));
                $response->setStatusCode(500);
            }

        } catch (\Exception $e) {
            $this->loggingService->logAudit('delete_student_error', "Delete error: " . $e->getMessage());
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
                'data' => null
            ]));
            $response->setStatusCode(500);
        }

        return $response;
    }

    /**
     * Normalizes a date string to YYYY-MM-DD format.
     *
     * @param string $dateString Date string in various formats
     * @return string Normalized date or empty string if invalid
     */
    private function normalizeDate(string $dateString): string
    {
        if (empty($dateString)) {
            return '';
        }

        // Remove any extra whitespace
        $dateString = trim($dateString);

        // Try different date formats and convert to YYYY-MM-DD
        $formats = [
            'Y-m-d',     // Already in correct format: 2023-12-25
            'd/m/Y',     // DD/MM/YYYY: 25/12/2023
            'm/d/Y',     // MM/DD/YYYY: 12/25/2023
            'd-m-Y',     // DD-MM-YYYY: 25-12-2023
            'm-d-Y',     // MM-DD-YYYY: 12-25-2023
            'Y/m/d',     // YYYY/MM/DD: 2023/12/25
            'Y-m-d H:i:s', // With time: 2023-12-25 10:30:00
            'd/m/Y H:i:s', // DD/MM/YYYY with time
            'm/d/Y H:i:s', // MM/DD/YYYY with time
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // If no format matches, try strtotime as fallback
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        // If all parsing fails, return empty string
        return '';
    }

    /**
     * Get students for a specific class.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    #[OA\Post(
        path: "/students/class",
        summary: "Get students by class",
        description: "Retrieves a list of students belonging to a specific class with optional status filtering.",
        tags: ["Students"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["class_id"],
                properties: [
                    new OA\Property(property: "class_id", type: "string", example: "p1", description: "The class ID to filter by"),
                    new OA\Property(property: "status", type: "string", example: "Admitted", description: "Optional admission status filter (Admitted, Stopped, Pending, Graduated, Transferred, Suspended)")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Students retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        new OA\Property(property: "count", type: "integer", example: 25)
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function classStudents(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $data['class_id'] = isset($data['class_id']) && is_int($data['class_id']) ? Student::where('id', $data['class_id'], 'classes')->class_id : $data['class_id'];
            //echo json_encode($data);exit;
            
            if (empty($data['class_id'])) {
                $response->setStatusCode(400);
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'class_id is required'
                ]));
                return $response;
            } 
            
            $classId = (string)$data['class_id'];
            $status = !empty($data['status']) ? (string)$data['status'] : null;
            
            $result = $this->studentService->getClassStudents($classId, $status);
            
            $response->setStatusCode($result['success'] ? 200 : 500);
            $response->setContent((string)json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $this->loggingService->logError('Error in classStudents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $response->setStatusCode(500);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'An error occurred while fetching class students'
            ]));
            return $response;
        }
    }
}
