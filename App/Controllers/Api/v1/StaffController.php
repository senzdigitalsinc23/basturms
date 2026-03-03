<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\StaffService;
use App\Services\ValidationService;
use App\Services\LoggingService;
use App\Services\AuthService;
use App\Services\NotificationService;
use App\Exceptions\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Staff Management",
    description: "API endpoints for managing staff records, registration, and assignments"
)]
class StaffController
{
    private StaffService $staffService;
    private ValidationService $validationService;
    private LoggingService $logger;
    private AuthService $authService;
    private NotificationService $notificationService;

    public function __construct(
        StaffService $staffService,
        ValidationService $validationService,
        LoggingService $logger,
        AuthService $authService,
        NotificationService $notificationService
    ) {
        $this->staffService = $staffService;
        $this->validationService = $validationService;
        $this->logger = $logger;
        $this->authService = $authService;
        $this->notificationService = $notificationService;
    }

    #[OA\Post(
        path: "/api/v1/staff/register",
        summary: "Register a new staff member",
        description: "Create a new staff record with personal details, address, academic history, and appointment information",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["personal_contact", "address"],
                properties: [
                    new OA\Property(
                        property: "personal_contact",
                        required: ["first_name", "last_name", "email", "phone", "id_type", "id_no"],
                        properties: [
                            new OA\Property(property: "first_name", type: "string", example: "Joseph", description: "Staff first name"),
                            new OA\Property(property: "last_name", type: "string", example: "Konnie", description: "Staff last name"),
                            new OA\Property(property: "other_name", type: "string", example: "", description: "Staff other/middle name"),
                            new OA\Property(property: "email", type: "string", format: "email", example: "joseph.konnie@basturms.com"),
                            new OA\Property(property: "phone", type: "string", example: "0247760226", description: "Phone number (10-15 digits)"),
                            new OA\Property(property: "id_type", type: "string", example: "1", description: "Type of ID"),
                            new OA\Property(property: "id_no", type: "string", example: "GHA-718881425-1", description: "ID number"),
                            new OA\Property(property: "snnit_no", type: "string", example: "1234567879898987", description: "SSNIT number (optional)"),
                            new OA\Property(property: "date_of_joining", type: "string", format: "date", example: "2026-01-01", description: "Date of joining"),
                            new OA\Property(property: "status", type: "string", example: "active", description: "Staff status")
                        ],
                        type: "object"
                    ),
                    new OA\Property(
                        property: "address",
                        required: ["country", "hometown", "residence", "house_no", "gps_no"],
                        properties: [
                            new OA\Property(property: "country", type: "string", example: "GH", description: "Country code"),
                            new OA\Property(property: "city", type: "string", example: "Tarkwa", description: "City (optional)"),
                            new OA\Property(property: "hometown", type: "string", example: "Dompim Pepesa", description: "Hometown"),
                            new OA\Property(property: "residence", type: "string", example: "Dompim", description: "Current residence"),
                            new OA\Property(property: "house_no", type: "string", example: "DP21", description: "House number"),
                            new OA\Property(property: "gps_no", type: "string", example: "WT-2018-0191", description: "GPS address")
                        ],
                        type: "object"
                    ),
                    new OA\Property(
                        property: "academic_history",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "school_name", type: "string", example: "University of Ghana"),
                                new OA\Property(property: "program_offered", type: "string", example: "Bsc. Agricultural Science"),
                                new OA\Property(property: "qualification", type: "string", example: "Bsc Agric"),
                                new OA\Property(property: "year_completed", type: "string", example: "2020")
                            ]
                        ),
                        description: "Array of academic history records"
                    ),
                    new OA\Property(
                        property: "appointment_history",
                        properties: [
                            new OA\Property(property: "appointment_date", type: "string", format: "date", example: "2026-02-20"),
                            new OA\Property(property: "appointment_status", type: "string", example: "appointed"),
                            new OA\Property(property: "class_teacher_for", type: "string", example: "jhs1", description: "Class teacher assignment"),
                            new OA\Property(
                                property: "assigned_classes",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "class_id", type: "string", example: "jhs1")
                                    ]
                                ),
                                example: [["class_id" => "jhs1"], ["class_id" => "jhs2"], ["class_id" => "jhs3"]],
                                description: "Array of class assignments"
                            ),
                            new OA\Property(
                                property: "assigned_subjects",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "subject_id", type: "string", example: "INT-SCI"),
                                        new OA\Property(property: "class_id", type: "string", example: "jhs1")
                                    ]
                                ),
                                description: "Array of subject assignments"
                            ),
                            new OA\Property(
                                property: "roles",
                                type: "array",
                                items: new OA\Items(type: "integer"),
                                example: [19],
                                description: "Array of role IDs"
                            )
                        ],
                        type: "object"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Staff registered successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff registered successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "staff_id", type: "string", example: "LBAST26001", description: "Auto-generated staff ID (Format: LBA+ST+Year+Sequence)"),
                                new OA\Property(property: "email", type: "string", example: "joseph.konnie@basturms.com"),
                                new OA\Property(
                                    property: "login_credentials",
                                    properties: [
                                        new OA\Property(property: "username", type: "string", example: "joseph.konnie@basturms.com", description: "Login username (same as email)"),
                                        new OA\Property(property: "temporary_password", type: "string", example: "a3f7b2c9", description: "Temporary password for first login"),
                                        new OA\Property(property: "note", type: "string", example: "Please change your password after first login")
                                    ],
                                    type: "object"
                                ),
                                new OA\Property(property: "message", type: "string", example: "Staff registered successfully")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
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
     * Register a new staff member
     * POST /api/v1/staff/register
     */
    public function register(Request $request, Response $response): Response
    {
        try {
            // Get all body params directly
            $bodyParams = $request->getPost();

/*             $file_path = dirname(__DIR__) . '\\testLog.json'; // Specify the file path
$content = json_encode($bodyParams); // The text content

//echo json_encode($file_path);exit;
// Write the content to the file, overwriting existing content if the file exists
file_put_contents($file_path, $content);exit; */

            
            // Debug: Check what we're actually receiving
            if (empty($bodyParams)) {
                $response->json([
                    'success' => false,
                    'message' => 'No data received',
                    'debug' => [
                        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
                        'method' => $request->getMethod(),
                        'raw_input_preview' => substr(file_get_contents('php://input'), 0, 200)
                    ]
                ], 400);
                return $response;
            }
            
            // Get grouped input data from body params
            $personalContact = $bodyParams['personal_contact'] ?? [];
            $address = $bodyParams['address'] ?? [];
            $academicHistory = $bodyParams['academic_history'] ?? [];
            $appointmentHistory = $bodyParams['appointment_history'] ?? [];
            
            // Debug: Check if nested data exists
            if (empty($personalContact)) {
                $response->json([
                    'success' => false,
                    'message' => 'personal_contact data is missing',
                    'debug' => [
                        'received_keys' => array_keys($bodyParams),
                        'sample_data' => array_slice($bodyParams, 0, 3)
                    ]
                ], 400);
                return $response;
            }

            // Get current user for audit trail
            $currentUser = $this->authService->getCurrentUser();
            $addedBy = $currentUser ? $currentUser->userId : 'system';

            // Flatten personal contact data and merge with nested structures
            $data = array_merge($personalContact, [
                'address' => $address,
                'academic_history' => $academicHistory,
                'appointment' => $appointmentHistory,
                'added_by' => $addedBy
            ]);
            
            // Validate only personal contact fields
            $rules = [
                'first_name' => 'required|string|min:2|max:100',
                'last_name' => 'required|string|min:2|max:100',
                'other_name' => 'nullable|string|max:100',
                'email' => 'required|email|max:100',
                'phone' => 'required|string|min:10|max:15',
                'id_type' => 'required|string|max:20',
                'id_no' => 'required|string|max:15',
                'snnit_no' => 'nullable|string|max:20',
                'appointment_date' => 'nullable|date',
                'status' => 'nullable|string'
            ];

            // Validate personal contact fields only
            $this->validationService->validate($personalContact, $rules);
            
            // Use full data (with nested structures) for registration
            $result = $this->staffService->registerStaff($data);

            $this->logger->logAudit(
                'staff_registration',
                'Staff registered successfully: ' . $result['staff_id'],
                $data['added_by']
            );

            $response->json([
                'success' => true,
                'message' => 'Staff registered successfully',
                'data' => $result
            ], 201);
            return $response;

        } catch (ValidationException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 422);
            return $response;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'staff_registration_error',
                'Staff registration failed: ' . $e->getMessage(),
                $data['added_by'] ?? 'unknown'
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred during staff registration',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Get(
        path: "/api/v1/staff/{id}",
        summary: "Get staff details by ID",
        description: "Retrieve complete details of a specific staff member including personal info, address, academic history, and appointment details",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Staff details retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "personal_contact",
                                    properties: [
                                        new OA\Property(property: "staff_id", type: "string"),
                                        new OA\Property(property: "first_name", type: "string"),
                                        new OA\Property(property: "last_name", type: "string"),
                                        new OA\Property(property: "other_name", type: "string"),
                                        new OA\Property(property: "email", type: "string"),
                                        new OA\Property(property: "phone", type: "string"),
                                        new OA\Property(property: "id_type", type: "string"),
                                        new OA\Property(property: "id_no", type: "string"),
                                        new OA\Property(property: "snnit_no", type: "string"),
                                        new OA\Property(property: "date_of_joining", type: "string"),
                                        new OA\Property(property: "status", type: "string"),
                                        new OA\Property(property: "signature_id", type: "string")
                                    ]
                                ),
                                new OA\Property(
                                    property: "address",
                                    properties: [
                                        new OA\Property(property: "country", type: "string"),
                                        new OA\Property(property: "city", type: "string"),
                                        new OA\Property(property: "hometown", type: "string"),
                                        new OA\Property(property: "residence", type: "string"),
                                        new OA\Property(property: "house_no", type: "string"),
                                        new OA\Property(property: "gps_no", type: "string")
                                    ]
                                ),
                                new OA\Property(property: "academic_history", type: "array", items: new OA\Items(type: "object")),
                                new OA\Property(
                                    property: "appointment_history",
                                    properties: [
                                        new OA\Property(property: "appointment_date", type: "string"),
                                        new OA\Property(property: "appointment_status", type: "string"),
                                        new OA\Property(property: "class_teacher_for", type: "string"),
                                        new OA\Property(property: "assigned_classes", type: "array", items: new OA\Items(type: "object")),
                                        new OA\Property(property: "assigned_subjects", type: "array", items: new OA\Items(type: "object")),
                                        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "object"))
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Staff not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Staff not found")
                    ]
                )
            )
        ]
    )]
    /**
     * Get staff by ID
     * GET /api/v1/staff/{id}
     */
    public function getStaff(Request $request, Response $response, array $params = []): Response
    {
        try {
            $staffId = $request->getPost('staff_id') ?? $params['id'] ?? null;

            
            if (!$staffId) {
                $response->json([
                    'success' => false,
                    'message' => 'Staff ID is required'
                ], 400);
                return $response;
            }

            $staff = $this->staffService->getStaffById($staffId);

            if (!$staff) {
                $response->json([
                    'success' => false,
                    'message' => 'Staff not found'
                ], 404);
                return $response;
            }

            $response->json([
                'success' => true,
                'message' => 'Staff retrieved successfully',
                'data' => $staff
            ]);
            return $response;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'get_staff_error',
                'Error retrieving staff: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred while retrieving staff'
            ], 500);
            return $response;
        }
    }

    #[OA\Get(
        path: "/api/v1/staff",
        summary: "Get all staff with pagination",
        description: "Retrieve a paginated list of staff members with essential information including roles, classes, and subjects assigned",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "Page number",
                schema: new OA\Schema(type: "integer", default: 1),
                example: 1
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                required: false,
                description: "Items per page",
                schema: new OA\Schema(type: "integer", default: 10),
                example: 10
            ),
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Staff status filter",
                schema: new OA\Schema(type: "string", default: "active"),
                example: "active"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Staff list retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff list retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "staff_id", type: "string", example: "LBAST26001"),
                                    new OA\Property(property: "first_name", type: "string", example: "John"),
                                    new OA\Property(property: "last_name", type: "string", example: "Doe"),
                                    new OA\Property(property: "other_name", type: "string", example: "Middle"),
                                    new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "object")),
                                    new OA\Property(property: "classes_assigned", type: "array", items: new OA\Items(type: "object")),
                                    new OA\Property(property: "subjects_assigned", type: "array", items: new OA\Items(type: "object")),
                                    new OA\Property(property: "date_of_joining", type: "string", example: "2026-02-26"),
                                    new OA\Property(property: "status", type: "string", example: "active")
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "pagination",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "per_page", type: "integer", example: 10),
                                new OA\Property(property: "total", type: "integer", example: 50),
                                new OA\Property(property: "total_pages", type: "integer", example: 5)
                            ]
                        )
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
     * Get all staff with pagination
     * GET /api/v1/staff
     */
    public function getAllStaff(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQuery('page') ?? 1);
            $limit = (int)($request->getQuery('limit') ?? 10);
            $status = $request->getQuery('status') ?? 'active';

            $result = $this->staffService->getAllStaff($page, $limit, $status);

            $response->json([
                'success' => true,
                'message' => 'Staff list retrieved successfully',
                'data' => $result['data'],
                'pagination' => $result['pagination']
            ]);
            return $response;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'get_all_staff_error',
                'Error retrieving staff list: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred while retrieving staff list'
            ], 500);
            return $response;
        }
    }

    #[OA\Post(
        path: "/api/v1/staff/share-credentials",
        summary: "Share staff login credentials",
        description: "Send staff login credentials via email, SMS, or both",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["staff_id", "method"],
                properties: [
                    new OA\Property(property: "staff_id", type: "string", example: "LBAST26001", description: "Staff ID"),
                    new OA\Property(property: "username", type: "string", example: "joseph.konnie@basturms.com", description: "Login username"),
                    new OA\Property(property: "password", type: "string", example: "a3f7b2c9", description: "Temporary password"),
                    new OA\Property(
                        property: "method",
                        type: "string",
                        enum: ["email", "sms", "both"],
                        example: "email",
                        description: "Delivery method for credentials"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Credentials shared successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Credentials sent successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "email_sent", type: "boolean", example: true),
                                new OA\Property(property: "sms_sent", type: "boolean", example: false)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid request",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Staff not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Staff not found")
                    ]
                )
            )
        ]
    )]
    /**
     * Share staff login credentials
     * POST /api/v1/staff/share-credentials
     */
    public function shareCredentials(Request $request, Response $response): Response
    {
        try {
            $bodyParams = $request->getPost();
            
            $staffId = $bodyParams['staff_id'] ?? null;
            $username = $bodyParams['username'] ?? null;
            $password = $bodyParams['password'] ?? null;
            $method = $bodyParams['method'] ?? 'email';

            // Validate required fields
            if (!$staffId || !$username || !$password) {
                $response->json([
                    'success' => false,
                    'message' => 'staff_id, username, and password are required'
                ], 400);
                return $response;
            }

            // Validate method
            if (!in_array($method, ['email', 'sms', 'both'])) {
                $response->json([
                    'success' => false,
                    'message' => 'Invalid method. Must be email, sms, or both'
                ], 400);
                return $response;
            }

            // Get staff details
            $staff = $this->staffService->getStaffById($staffId);
            
            if (!$staff) {
                $response->json([
                    'success' => false,
                    'message' => 'Staff not found'
                ], 404);
                return $response;
            }

            $staffInfo = $staff['personal_contact'] ?? [];
            $email = $staffInfo['email'] ?? null;
            $phone = $staffInfo['phone'] ?? null;

            $results = [
                'email_sent' => false,
                'sms_sent' => false
            ];

            // Send based on method
            if ($method === 'email' || $method === 'both') {
                if ($email) {
                    $results['email_sent'] = $this->notificationService->sendCredentialsByEmail(
                        $staffId,
                        $email,
                        $username,
                        $password,
                        $staffInfo
                    );
                }
            }

            if ($method === 'sms' || $method === 'both') {
                if ($phone) {
                    $results['sms_sent'] = $this->notificationService->sendCredentialsBySMS(
                        $staffId,
                        $phone,
                        $username,
                        $password,
                        $staffInfo
                    );
                }
            }

            // Determine success message
            $successCount = ($results['email_sent'] ? 1 : 0) + ($results['sms_sent'] ? 1 : 0);
            
            if ($successCount === 0) {
                $response->json([
                    'success' => false,
                    'message' => 'Failed to send credentials',
                    'data' => $results
                ], 500);
                return $response;
            }

            $message = 'Credentials sent successfully';
            if ($method === 'both') {
                if ($results['email_sent'] && $results['sms_sent']) {
                    $message = 'Credentials sent via email and SMS';
                } elseif ($results['email_sent']) {
                    $message = 'Credentials sent via email only (SMS failed)';
                } elseif ($results['sms_sent']) {
                    $message = 'Credentials sent via SMS only (email failed)';
                }
            } elseif ($method === 'email') {
                $message = 'Credentials sent via email';
            } elseif ($method === 'sms') {
                $message = 'Credentials sent via SMS';
            }

            $this->logger->logAudit(
                'staff_credentials_shared',
                "Credentials shared for staff {$staffId} via {$method}",
                $staffInfo['staff_id'] ?? 'system'
            );

            $response->json([
                'success' => true,
                'message' => $message,
                'data' => $results
            ]);
            return $response;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'share_credentials_error',
                'Error sharing credentials: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred while sharing credentials',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Put(
        path: "/api/v1/staff/{id}",
        summary: "Update staff member details",
        description: "Update complete staff information including personal details, address, academic history, and appointment information",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["personal_contact", "address"],
                properties: [
                    new OA\Property(
                        property: "personal_contact",
                        required: ["first_name", "last_name", "email", "phone", "id_type", "id_no"],
                        properties: [
                            new OA\Property(property: "first_name", type: "string", example: "Joseph", description: "Staff first name"),
                            new OA\Property(property: "last_name", type: "string", example: "Konnie", description: "Staff last name"),
                            new OA\Property(property: "other_name", type: "string", example: "", description: "Staff other/middle name"),
                            new OA\Property(property: "email", type: "string", format: "email", example: "joseph.konnie@basturms.com"),
                            new OA\Property(property: "phone", type: "string", example: "0247760226", description: "Phone number (10-15 digits)"),
                            new OA\Property(property: "id_type", type: "string", example: "1", description: "Type of ID"),
                            new OA\Property(property: "id_no", type: "string", example: "GHA-718881425-1", description: "ID number"),
                            new OA\Property(property: "snnit_no", type: "string", example: "1234567879898987", description: "SSNIT number (optional)"),
                            new OA\Property(property: "date_of_joining", type: "string", format: "date", example: "2026-01-01", description: "Date of joining"),
                            new OA\Property(property: "status", type: "string", example: "active", description: "Staff status")
                        ],
                        type: "object"
                    ),
                    new OA\Property(
                        property: "address",
                        required: ["country", "hometown", "residence", "house_no", "gps_no"],
                        properties: [
                            new OA\Property(property: "country", type: "string", example: "GH", description: "Country code"),
                            new OA\Property(property: "city", type: "string", example: "Tarkwa", description: "City (optional)"),
                            new OA\Property(property: "hometown", type: "string", example: "Dompim Pepesa", description: "Hometown"),
                            new OA\Property(property: "residence", type: "string", example: "Dompim", description: "Current residence"),
                            new OA\Property(property: "house_no", type: "string", example: "DP21", description: "House number"),
                            new OA\Property(property: "gps_no", type: "string", example: "WT-2018-0191", description: "GPS address")
                        ],
                        type: "object"
                    ),
                    new OA\Property(
                        property: "academic_history",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "school_name", type: "string", example: "University of Ghana"),
                                new OA\Property(property: "program_offered", type: "string", example: "Bsc. Agricultural Science"),
                                new OA\Property(property: "qualification", type: "string", example: "Bsc Agric"),
                                new OA\Property(property: "year_completed", type: "string", example: "2020")
                            ]
                        ),
                        description: "Array of academic history records"
                    ),
                    new OA\Property(
                        property: "appointment_history",
                        properties: [
                            new OA\Property(property: "appointment_date", type: "string", format: "date", example: "2026-02-20"),
                            new OA\Property(property: "appointment_status", type: "string", example: "appointed"),
                            new OA\Property(property: "class_teacher_for", type: "string", example: "jhs1", description: "Class teacher assignment"),
                            new OA\Property(
                                property: "assigned_classes",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "class_id", type: "string", example: "jhs1")
                                    ]
                                ),
                                example: [["class_id" => "jhs1"], ["class_id" => "jhs2"]],
                                description: "Array of class assignments"
                            ),
                            new OA\Property(
                                property: "assigned_subjects",
                                type: "array",
                                items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "subject_id", type: "string", example: "INT-SCI"),
                                        new OA\Property(property: "class_id", type: "string", example: "jhs1")
                                    ]
                                ),
                                description: "Array of subject assignments"
                            ),
                            new OA\Property(
                                property: "roles",
                                type: "array",
                                items: new OA\Items(type: "integer"),
                                example: [19],
                                description: "Array of role IDs"
                            )
                        ],
                        type: "object"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Staff updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff updated successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "staff_id", type: "string", example: "LBAST26001"),
                                new OA\Property(property: "email", type: "string", example: "joseph.konnie@basturms.com"),
                                new OA\Property(property: "message", type: "string", example: "Staff updated successfully")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Staff not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Staff not found")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
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
     * Update staff member
     * PUT /api/v1/staff/{id}
     */
    public function updateStaff(Request $request, Response $response, array $params = []): Response
    {
        try {
            $staffId = $params['id'] ?? null;

            if (!$staffId) {
                $response->json([
                    'success' => false,
                    'message' => 'Staff ID is required'
                ], 400);
                return $response;
            }

            // Get all body params
            $bodyParams = $request->getPost();
            
            // Get grouped input data from body params
            $personalContact = $bodyParams['personal_contact'] ?? [];
            $address = $bodyParams['address'] ?? [];
            $academicHistory = $bodyParams['academic_history'] ?? [];
            $appointmentHistory = $bodyParams['appointment_history'] ?? [];

            // Get current user for audit trail
            $currentUser = $this->authService->getCurrentUser();
            $addedBy = $currentUser ? $currentUser->userId : 'system';

            // Flatten personal contact data and merge with nested structures
            $data = array_merge($personalContact, [
                'address' => $address,
                'academic_history' => $academicHistory,
                'appointment' => $appointmentHistory,
                'added_by' => $addedBy
            ]);
            
            // Validate only personal contact fields
            $rules = [
                'first_name' => 'required|string|min:2|max:100',
                'last_name' => 'required|string|min:2|max:100',
                'other_name' => 'nullable|string|max:100',
                'email' => 'required|email|max:100',
                'phone' => 'required|string|min:10|max:15',
                'id_type' => 'required|string|max:20',
                'id_no' => 'required|string|max:15',
                'snnit_no' => 'nullable|string|max:20',
                'date_of_joining' => 'nullable|date',
                'status' => 'nullable|string'
            ];

            // Validate personal contact fields only
            $this->validationService->validate($personalContact, $rules);
            
            // Use full data (with nested structures) for update
            $result = $this->staffService->updateStaff($staffId, $data);

            $this->logger->logAudit(
                'staff_update',
                'Staff updated successfully: ' . $result['staff_id'],
                $addedBy
            );

            $response->json([
                'success' => true,
                'message' => 'Staff updated successfully',
                'data' => $result
            ]);
            return $response;

        } catch (ValidationException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 422);
            return $response;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'staff_update_error',
                'Staff update failed: ' . $e->getMessage(),
                $data['added_by'] ?? 'unknown'
            );

            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Patch(
        path: "/api/v1/staff/{id}/status",
        summary: "Update staff status (activate/deactivate)",
        description: "Change staff status to active, inactive, suspended, or terminated",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "string",
                        enum: ["active", "inactive", "suspended", "terminated"],
                        example: "inactive",
                        description: "New status for the staff member"
                    ),
                    new OA\Property(
                        property: "reason",
                        type: "string",
                        example: "Extended leave of absence",
                        description: "Reason for status change (optional)"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Status updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff status updated to inactive"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "staff_id", type: "string", example: "LBAST26001"),
                                new OA\Property(property: "old_status", type: "string", example: "active"),
                                new OA\Property(property: "new_status", type: "string", example: "inactive"),
                                new OA\Property(property: "message", type: "string", example: "Staff status updated to inactive")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Staff not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Staff not found")
                    ]
                )
            )
        ]
    )]
    /**
     * Update staff status
     * PATCH /api/v1/staff/{id}/status
     */
    public function updateStatus(Request $request, Response $response, array $params = []): Response
    {
        try {
            $staffId = $params['id'] ?? null;

            if (!$staffId) {
                $response->json([
                    'success' => false,
                    'message' => 'Staff ID is required'
                ], 400);
                return $response;
            }

            $bodyParams = $request->getPost();
            $status = $bodyParams['status'] ?? null;
            $reason = $bodyParams['reason'] ?? null;

            if (!$status) {
                $response->json([
                    'success' => false,
                    'message' => 'Status is required'
                ], 400);
                return $response;
            }

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $updatedBy = $currentUser ? $currentUser->userId : 'system';

            $result = $this->staffService->toggleStaffStatus($staffId, $status, $reason, $updatedBy);

            $response->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
            return $response;

        } catch (ValidationException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 422);
            return $response;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'staff_status_update_error',
                'Error updating staff status: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Delete(
        path: "/api/v1/staff/{id}",
        summary: "Delete staff member (soft delete)",
        description: "Archive/soft delete a staff member. The record is marked as archived but not permanently removed.",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "reason",
                        type: "string",
                        example: "Resignation",
                        description: "Reason for deletion (optional)"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Staff archived successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff archived successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "staff_id", type: "string", example: "LBAST26001"),
                                new OA\Property(property: "message", type: "string", example: "Staff archived successfully")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Staff not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Staff not found")
                    ]
                )
            )
        ]
    )]
    /**
     * Delete staff member (soft delete)
     * DELETE /api/v1/staff/{id}
     */
    public function deleteStaff(Request $request, Response $response, array $params = []): Response
    {
        try {
            $staffId = $params['id'] ?? null;

            if (!$staffId) {
                $response->json([
                    'success' => false,
                    'message' => 'Staff ID is required'
                ], 400);
                return $response;
            }

            $bodyParams = $request->getPost();
            $reason = $bodyParams['reason'] ?? null;

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $deletedBy = $currentUser ? $currentUser->userId : 'system';

            $result = $this->staffService->deleteStaff($staffId, $reason, $deletedBy);

            $response->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
            return $response;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'staff_delete_error',
                'Error deleting staff: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Delete(
        path: "/api/v1/staff/{id}/permanent",
        summary: "Permanently delete staff member",
        description: "Permanently delete a staff member and all related records (CASCADE DELETE). This action cannot be undone!",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Staff permanently deleted",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff permanently deleted successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "staff_id", type: "string", example: "LBAST26001"),
                                new OA\Property(property: "message", type: "string", example: "Staff permanently deleted successfully")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Staff not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Staff not found")
                    ]
                )
            )
        ]
    )]
    /**
     * Permanently delete staff member
     * DELETE /api/v1/staff/{id}/permanent
     */
    public function permanentlyDeleteStaff(Request $request, Response $response, array $params = []): Response
    {
        try {
            $staffId = $params['id'] ?? null;

            if (!$staffId) {
                $response->json([
                    'success' => false,
                    'message' => 'Staff ID is required'
                ], 400);
                return $response;
            }

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $deletedBy = $currentUser ? $currentUser->userId : 'system';

            $result = $this->staffService->permanentlyDeleteStaff($staffId, $deletedBy);

            $response->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
            return $response;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'staff_permanent_delete_error',
                'Error permanently deleting staff: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Get(
        path: "/api/v1/staff/filter",
        summary: "Get staff by filter",
        description: "Retrieve staff list filtered by role, class, subject, or search term with pagination",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "role_id",
                in: "query",
                required: false,
                description: "Filter by role ID (e.g., 19 for teachers)",
                schema: new OA\Schema(type: "integer"),
                example: 19
            ),
            new OA\Parameter(
                name: "class_id",
                in: "query",
                required: false,
                description: "Filter by class ID (e.g., jhs1)",
                schema: new OA\Schema(type: "string"),
                example: "jhs1"
            ),
            new OA\Parameter(
                name: "subject_id",
                in: "query",
                required: false,
                description: "Filter by subject ID (e.g., INT-SCI)",
                schema: new OA\Schema(type: "string"),
                example: "INT-SCI"
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                required: false,
                description: "Search by name, email, or staff ID",
                schema: new OA\Schema(type: "string"),
                example: "John"
            ),
            new OA\Parameter(
                name: "status",
                in: "query",
                required: false,
                description: "Filter by status",
                schema: new OA\Schema(type: "string", default: "active"),
                example: "active"
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                required: false,
                description: "Page number",
                schema: new OA\Schema(type: "integer", default: 1),
                example: 1
            ),
            new OA\Parameter(
                name: "limit",
                in: "query",
                required: false,
                description: "Items per page",
                schema: new OA\Schema(type: "integer", default: 10),
                example: 10
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Staff list retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff list retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "staff_id", type: "string", example: "LBAST26001"),
                                    new OA\Property(property: "first_name", type: "string", example: "John"),
                                    new OA\Property(property: "last_name", type: "string", example: "Doe"),
                                    new OA\Property(property: "other_name", type: "string", example: ""),
                                    new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "object")),
                                    new OA\Property(property: "classes_assigned", type: "array", items: new OA\Items(type: "object")),
                                    new OA\Property(property: "subjects_assigned", type: "array", items: new OA\Items(type: "object")),
                                    new OA\Property(property: "date_of_joining", type: "string", example: "2026-02-26"),
                                    new OA\Property(property: "status", type: "string", example: "active")
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "pagination",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "per_page", type: "integer", example: 10),
                                new OA\Property(property: "total", type: "integer", example: 25),
                                new OA\Property(property: "total_pages", type: "integer", example: 3)
                            ],
                            type: "object"
                        ),
                        new OA\Property(
                            property: "filters",
                            properties: [
                                new OA\Property(property: "role_id", type: "integer", example: 19),
                                new OA\Property(property: "status", type: "string", example: "active")
                            ],
                            type: "object"
                        )
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
     * Get staff by filter
     * GET /api/v1/staff/filter
     */
    public function getStaffByFilter(Request $request, Response $response): Response
    {
        try {
            $page = (int)($request->getQuery('page') ?? 1);
            $limit = (int)($request->getQuery('limit') ?? 10);

            //echo json_encode($request->getQuery());exit;
            
            // Build filters array
            $filters = [
                'role_id' => $request->getQuery('role_id'),
                'class_id' => $request->getQuery('class_id'),
                'subject_id' => $request->getQuery('subject_id'),
                'search' => $request->getQuery('search'),
                'status' => $request->getQuery('status') ?? 'active'
            ];
            
            // Remove null values
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $result = $this->staffService->getStaffByFilter($filters, $page, $limit);

            $response->json([
                'success' => true,
                'message' => 'Staff list retrieved successfully',
                'data' => $result['data'],
                'pagination' => $result['pagination'],
                'filters' => $result['filters']
            ]);
            return $response;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'get_staff_by_filter_error',
                'Error retrieving filtered staff list: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred while retrieving staff list'
            ], 500);
            return $response;
        }
    }

    #[OA\Post(
        path: "/api/v1/staff/assign-classes",
        summary: "Assign classes to staff",
        description: "Assign one or more classes to a staff member",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["class_ids"],
                properties: [
                    new OA\Property(
                        property: "class_ids",
                        type: "array",
                        items: new OA\Items(type: "string"),
                        example: ["jhs1", "jhs2", "jhs3"]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Classes assigned successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Classes assigned successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "assigned", type: "array", items: new OA\Items(type: "string")),
                                new OA\Property(property: "already_assigned", type: "array", items: new OA\Items(type: "string")),
                                new OA\Property(property: "total_assigned", type: "integer", example: 3)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Staff not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
    /**
     * Assign classes to staff
     * POST /api/v1/staff/{id}/assign-classes
     */
    public function assignClasses(Request $request, Response $response): Response
    {
        try {
            $staffId = $request->getPost('staff_id');
            $data = $request->getPost();

            // Validate input
            if (empty($data['class_ids']) || !is_array($data['class_ids'])) {
                $response->json([
                    'success' => false,
                    'message' => 'class_ids array is required'
                ], 400);
                return $response;
            }

            // Get current user for audit trail
            $currentUser = $this->authService->getCurrentUser();
            $assignedBy = $currentUser ? $currentUser->userId : 'system';
            
            $result = $this->staffService->assignClasses($staffId, $data['class_ids'], $assignedBy);

            $response->json([
                'success' => true,
                'message' => 'Classes assigned successfully',
                'data' => $result
            ]);
            return $response;

        } catch (NotFoundException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
            return $response;
        } catch (\Exception $e) {
            $this->logger->logAudit(
                'assign_classes_error',
                'Error assigning classes: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred while assigning classes'
            ], 500);
            return $response;
        }
    }

    #[OA\Post(
        path: "/api/v1/staff/{id}/assign-subjects",
        summary: "Assign subjects to staff",
        description: "Assign subjects to a staff member for specific classes. Supports two formats: 1) Array of subject-class pairs, 2) Array of subjects with multiple classes",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Supports two formats: Format 1 (detailed): array of {subject_id, class_id} pairs. Format 2 (simplified): array of {subject_id, class_ids[]} for assigning one subject to multiple classes",
            content: new OA\JsonContent(
                required: ["assignments"],
                properties: [
                    new OA\Property(
                        property: "assignments",
                        type: "array",
                        description: "Array of subject assignments. Each item can have either 'class_id' (single) or 'class_ids' (multiple)",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "subject_id", type: "string", example: "INT-SCI", description: "Subject ID (required)"),
                                new OA\Property(property: "class_id", type: "string", example: "jhs1", description: "Single class ID (use this OR class_ids)"),
                                new OA\Property(
                                    property: "class_ids", 
                                    type: "array", 
                                    items: new OA\Items(type: "string"),
                                    example: ["jhs1", "jhs2", "jhs3"],
                                    description: "Multiple class IDs (use this OR class_id)"
                                )
                            ]
                        ),
                        example: [
                            ["subject_id" => "INT-SCI", "class_ids" => ["jhs1", "jhs2", "jhs3"]],
                            ["subject_id" => "MATH", "class_id" => "jhs1"]
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Subjects assigned successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Subjects assigned successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "assigned", type: "array", items: new OA\Items(type: "object")),
                                new OA\Property(property: "already_assigned", type: "array", items: new OA\Items(type: "string")),
                                new OA\Property(property: "total_assigned", type: "integer", example: 3)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Staff not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
    /**
     * Assign subjects to staff
     * POST /api/v1/staff/{id}/assign-subjects
     */
    public function assignSubjects(Request $request, Response $response): Response
    {
        try {
            $staffId = $request->getPost('staff_id');
            $data = $request->getPost();

            // Validate input
            if (empty($data['assignments']) || !is_array($data['assignments'])) {
                $response->json([
                    'success' => false,
                    'message' => 'assignments array is required'
                ], 400);
                return $response;
            }

            // Normalize assignments to support both formats:
            // Format 1: [{"subject_id": "INT-SCI", "class_id": "jhs1"}]
            // Format 2: [{"subject_id": "INT-SCI", "class_ids": ["jhs1", "jhs2", "jhs3"]}]
            $normalizedAssignments = [];
            foreach ($data['assignments'] as $assignment) {
                if (!isset($assignment['subject_id'])) {
                    $response->json([
                        'success' => false,
                        'message' => 'Each assignment must have a subject_id'
                    ], 400);
                    return $response;
                }

                // Check if using class_ids (multiple classes) or class_id (single class)
                if (isset($assignment['class_ids']) && is_array($assignment['class_ids'])) {
                    // Format 2: Expand to multiple assignments
                    foreach ($assignment['class_ids'] as $classId) {
                        $normalizedAssignments[] = [
                            'subject_id' => $assignment['subject_id'],
                            'class_id' => $classId
                        ];
                    }
                } elseif (isset($assignment['class_id'])) {
                    // Format 1: Use as is
                    $normalizedAssignments[] = [
                        'subject_id' => $assignment['subject_id'],
                        'class_id' => $assignment['class_id']
                    ];
                } else {
                    $response->json([
                        'success' => false,
                        'message' => 'Each assignment must have either class_id or class_ids'
                    ], 400);
                    return $response;
                }
            }

            // Get current user for audit trail
            $currentUser = $this->authService->getCurrentUser();
            $assignedBy = $currentUser ? $currentUser->userId : 'system';
            
            $result = $this->staffService->assignSubjects($staffId, $normalizedAssignments, $assignedBy);

            $response->json([
                'success' => true,
                'message' => 'Subjects assigned successfully',
                'data' => $result
            ]);
            return $response;

        } catch (NotFoundException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
            return $response;
        } catch (\Exception $e) {
            $this->logger->logAudit(
                'assign_subjects_error',
                'Error assigning subjects: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred while assigning subjects'
            ], 500);
            return $response;
        }
    }

    #[OA\Get(
        path: "/api/v1/staff/{id}/assignments",
        summary: "Get staff assignments",
        description: "Retrieve all class and subject assignments for a staff member",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Staff assignments retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff assignments retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "staff_id", type: "string", example: "LBAST26001"),
                                new OA\Property(property: "staff_name", type: "string", example: "John Doe"),
                                new OA\Property(property: "classes", type: "array", items: new OA\Items(type: "object")),
                                new OA\Property(property: "subjects_by_class", type: "array", items: new OA\Items(type: "object")),
                                new OA\Property(property: "total_classes", type: "integer", example: 3),
                                new OA\Property(property: "total_subjects", type: "integer", example: 5)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Staff not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
    /**
     * Get staff assignments
     * GET /api/v1/staff/{id}/assignments
     */
    public function getStaffAssignments(Request $request, Response $response): Response
    {
        try {
            $staffId = $request->getPost('staff_id');
            $result = $this->staffService->getStaffAssignments($staffId);

            $response->json([
                'success' => true,
                'message' => 'Staff assignments retrieved successfully',
                'data' => $result
            ]);
            return $response;

        } catch (NotFoundException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
            return $response;
        } catch (\Exception $e) {
            $this->logger->logAudit(
                'get_staff_assignments_error',
                'Error retrieving staff assignments: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred while retrieving staff assignments'
            ], 500);
            return $response;
        }
    }

    #[OA\Delete(
        path: "/api/v1/staff/{id}/remove-class/{class_id}",
        summary: "Remove class assignment",
        description: "Remove a class assignment from a staff member. This will also remove all subject assignments for this class.",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            ),
            new OA\Parameter(
                name: "class_id",
                in: "path",
                required: true,
                description: "Class ID",
                schema: new OA\Schema(type: "string"),
                example: "jhs1"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Class assignment removed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Class assignment removed successfully (including all related subject assignments)")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Staff not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
    /**
     * Remove class assignment
     * DELETE /api/v1/staff/{id}/remove-class/{class_id}
     */
    public function removeClassAssignment(Request $request, Response $response): Response
    {
        try {
            $staffId = $request->getPost('staff_id');
            $classId = $request->getPost('class_id');

            $this->staffService->removeClassAssignment($staffId, $classId);

            $response->json([
                'success' => true,
                'message' => 'Class assignment removed successfully (including all related subject assignments)'
            ]);
            return $response;

        } catch (NotFoundException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
            return $response;
        } catch (\Exception $e) {
            $this->logger->logAudit(
                'remove_class_assignment_error',
                'Error removing class assignment: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred while removing class assignment'
            ], 500);
            return $response;
        }
    }

    #[OA\Delete(
        path: "/api/v1/staff/{id}/remove-subject/{subject_id}/{class_id}",
        summary: "Remove subject assignment",
        description: "Remove a subject assignment from a staff member for a specific class",
        tags: ["Staff Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            ),
            new OA\Parameter(
                name: "subject_id",
                in: "path",
                required: true,
                description: "Subject ID",
                schema: new OA\Schema(type: "string"),
                example: "INT-SCI"
            ),
            new OA\Parameter(
                name: "class_id",
                in: "path",
                required: true,
                description: "Class ID",
                schema: new OA\Schema(type: "string"),
                example: "jhs1"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Subject assignment removed successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Subject assignment removed successfully")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Staff not found"),
            new OA\Response(response: 500, description: "Internal server error")
        ]
    )]
    /**
     * Remove subject assignment
     * DELETE /api/v1/staff/{id}/remove-subject/{subject_id}/{class_id}
     */
    public function removeSubjectAssignment(Request $request, Response $response): Response
    {
        try {
            $staffId = $request->getPost('staff_id');
            $subjectId = $request->getPost('subject_id');
            $classId = $request->getPost('class_id');

            $this->staffService->removeSubjectAssignment($staffId, $subjectId, $classId);

            $response->json([
                'success' => true,
                'message' => 'Subject assignment removed successfully'
            ]);
            return $response;

        } catch (NotFoundException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
            return $response;
        } catch (\Exception $e) {
            $this->logger->logAudit(
                'remove_subject_assignment_error',
                'Error removing subject assignment: ' . $e->getMessage()
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred while removing subject assignment'
            ], 500);
            return $response;
        }
    }

}
