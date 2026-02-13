<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AcademicSetupService;
use App\Services\SubjectService;
use App\Services\ClassService;
use App\Services\ClassSubjectService;
use App\Services\TeacherSubjectService;
use App\Services\StudentScoreService;
use App\Services\AssignmentActivityService;
use App\Services\ClassActivityAssignmentService;
use App\Services\GradingSchemeService;
use App\Services\LoggingService;
use App\Exceptions\ValidationException;
use App\Models\ClassActivityAssignment;
use App\Models\Student;
use Database\ORM\Model;
use OpenApi\Attributes as OA;

use function PHPUnit\Framework\isString;

/**
 * Controller for handling academic management API endpoints.
 *
 * Provides endpoints for managing academic years, terms, subjects, classes,
 * teacher assignments, and student scores.
 */
#[OA\Tag(
    name: "Academic Management",
    description: "API endpoints for managing academic years, terms, subjects, classes, and scores"
)]
class AcademicController
{
    private AcademicSetupService $academicSetupService;
    private SubjectService $subjectService;
    private ClassService $classService;
    private ClassSubjectService $classSubjectService;
    private TeacherSubjectService $teacherSubjectService;
    private StudentScoreService $studentScoreService;
    private AssignmentActivityService $assignmentActivityService;
    private ClassActivityAssignmentService $classActivityAssignmentService;
    private GradingSchemeService $gradingSchemeService;
    private LoggingService $loggingService;

    /**
     * AcademicController constructor.
     * 
     * Note: Authentication is handled by AuthMiddleware in the routing layer.
     *
     * @param AcademicSetupService $academicSetupService Service for academic setup operations
     * @param SubjectService $subjectService Service for subject operations
     * @param ClassService $classService Service for class operations
     * @param ClassSubjectService $classSubjectService Service for class-subject assignments
     * @param TeacherSubjectService $teacherSubjectService Service for teacher-subject assignments
     * @param StudentScoreService $studentScoreService Service for student score operations
     * @param LoggingService $loggingService Service for audit logging
     */
    public function __construct(
        AcademicSetupService $academicSetupService,
        SubjectService $subjectService,
        ClassService $classService,
        ClassSubjectService $classSubjectService,
        TeacherSubjectService $teacherSubjectService,
        StudentScoreService $studentScoreService,
        AssignmentActivityService $assignmentActivityService,
        ClassActivityAssignmentService $classActivityAssignmentService,
        GradingSchemeService $gradingSchemeService,
        LoggingService $loggingService
    ) {
        $this->academicSetupService = $academicSetupService;
        $this->subjectService = $subjectService;
        $this->classService = $classService;
        $this->classSubjectService = $classSubjectService;
        $this->teacherSubjectService = $teacherSubjectService;
        $this->studentScoreService = $studentScoreService;
        $this->assignmentActivityService = $assignmentActivityService;
        $this->classActivityAssignmentService = $classActivityAssignmentService;
        $this->gradingSchemeService = $gradingSchemeService;
        $this->loggingService = $loggingService;
    }

    /**
     * Retrieves the current user ID from the session.
     *
     * @return string The user ID or 'system' if not available
     */
    private function getUserId(): string
    {
        $user = Session::get('user');
        if (is_array($user) && isset($user['user_id'])) {
            return $user['user_id'];
        }
        if (is_array($user) && isset($user['id'])) {
            return $user['id'];
        }
        return Session::get('user_id', 'system');
    }

    /**
     * Retrieves the current user's role from the session.
     *
     * @return string The user role or 'guest' if not available
     */
    private function getUserRole(): string
    {
        $user = Session::get('user');

        //echo json_encode($user);exit;
        if (is_array($user) && isset($user['role'])) {
            return $user['role'];
        }
        return 'guest';
    }

    /**
     * Checks if the currently authenticated user is a super admin.
     *
     * @return bool True if super admin, false otherwise
     */
    private function isSuperUser(): bool
    {
        $userSession = (array)Session::get('user');
        return (bool)($userSession['is_super_admin'] ?? false);
    }

    /**
     * Sets the number of terms for an academic year.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status and data
     */
    public function setNumberOfTerms(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $academicYear = trim($data['academic_year'] ?? '');
            $numberOfTerms = (int)($data['number_of_terms'] ?? 0);

            if (empty($academicYear)) {
                throw new ValidationException(['academic_year' => ['Academic year is required']], 'Validation failed');
            }

            $result = $this->academicSetupService->setNumberOfTerms($academicYear, $numberOfTerms);

            $this->loggingService->logAudit(
                'academic_setup',
                "Number of terms set for academic year: {$academicYear}",
                $this->getUserId()
            );

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $this->loggingService->logAudit(
                'academic_setup_error',
                "Validation failed: {$e->getMessage()}",
                $this->getUserId()
            );
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $this->loggingService->logAudit(
                'academic_setup_error',
                "Failed to set number of terms: {$e->getMessage()}",
                $this->getUserId()
            );
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while setting the number of terms'
            ]));
            return $response;
        }
    }

    #[OA\Post(
        path: "/academic/years/create",
        summary: "Create a new academic year",
        description: "Creates a new academic year with specified terms and status.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["academic_year", "number_of_terms"],
                properties: [
                    new OA\Property(property: "academic_year", type: "string", example: "2025/2026"),
                    new OA\Property(property: "number_of_terms", type: "integer", example: 3),
                    new OA\Property(property: "status", type: "string", enum: ["Active", "Upcoming", "Completed", "Archived"], default: "Upcoming")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Academic year created successfully"),
            new OA\Response(response: 400, description: "Validation error or year already exists")
        ]
    )]
    public function createAcademicYear(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userId = $this->getUserId();

            $academicYearName = trim(($data['academic_year'] ?? ''));
            $numberOfTerms = (int)($data['number_of_terms'] ?? 3);
            $status = trim(($data['status'] ?? 'Upcoming'));

            if (empty($academicYearName) || $numberOfTerms < 1 || $numberOfTerms > 3) {
                throw new ValidationException(
                    [
                        'academic_year' => ['Academic year name is required'],
                        'number_of_terms' => ['Number of terms must be between 1 and 3']
                    ],
                    'Validation failed'
                );
            }

            // Check for existing academic year with the same name
            if ($this->academicSetupService->findByName($academicYearName)) {
                throw new ValidationException(
                    ['academic_year' => ['Academic year with this name already exists']],
                    'Validation failed'
                );
            }

            $academicYear = $this->academicSetupService->createAcademicYear($academicYearName, $numberOfTerms, $status, $userId);

            $response->setContent(json_encode([
                'success' => true,
                'message' => 'Academic year created successfully',
                'data' => $academicYear
            ]));
            $response->setStatusCode(201);
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode([
                'success' => false, 
                'message' => $e->getMessage(), 
                'errors' => $e->getErrors()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setContent(json_encode([
                'success' => false, 
                'message' => $e->getMessage(),
                'error' => 'Internal server error'
            ]));
            return $response;
        }
    }

    #[OA\Get(
        path: "/academic/years/list",
        summary: "List all academic years",
        description: "Retrieve a list of all academic years, optionally filtered by academic year string.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "academic_year", in: "query", description: "Search by academic year string", required: false, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of academic years")
        ]
    )]
    public function listAcademicYears(Request $request, Response $response): Response
    {
        try {
            // Get search parameter from query string
            $searchYear = trim($request->getQuery('year', ''));

            $result = $this->academicSetupService->listAcademicYears($searchYear);
            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while retrieving academic years'
            ]));
            return $response;
        }
    }

    /**
     * Retrieves the currently active academic year term.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with active academic year data
     */
    #[OA\Get(
        path: "/academic/years/active",
        summary: "Get active academic year",
        description: "Retrieves the currently active academic year and its terms.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Active academic year details"),
            new OA\Response(response: 404, description: "No active academic year found")
        ]
    )]
    public function getActiveAcademicYear(Request $request, Response $response): Response
    {
        try {
            $result = $this->academicSetupService->getActiveAcademicYear();
            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(404);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while retrieving the active academic year'
            ]));
            return $response;
        }
    }

    /**
     * Updates the status of an academic year.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    public function updateAcademicYearStatus(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userId = $this->getUserId();

            $academicYear = trim($data['academic_year'] ?? '');
            $status = trim($data['status'] ?? '');

            if (empty($academicYear)) {
                throw new ValidationException(['academic_year' => ['Academic year is required']], 'Validation failed');
            }

            if (empty($status)) {
                throw new ValidationException(['status' => ['Status is required']], 'Validation failed');
            }

            $result = $this->academicSetupService->updateAcademicYearStatus($academicYear, $status, $userId);

            $this->loggingService->logAudit(
                'academic_setup',
                "Academic year status updated: {$academicYear} to {$status}",
                $userId
            );

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $this->loggingService->logAudit(
                'academic_setup_error',
                "Validation failed: {$e->getMessage()}",
                $this->getUserId()
            );
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(404);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while updating the academic year status'
            ]));
            return $response;
        }
    }

    /**
     * Deletes one or more academic years and all their associated terms.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    public function deleteAcademicYear(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            // Check if it's a single year or array of years
            $academicYears = $data['years'] ?? '';

            // Handle single year (backward compatibility)
            if (is_string($academicYears) && !empty(trim($academicYears))) {
                $academicYears = [trim($academicYears)];
            }
            // Handle array of years
            elseif (is_array($academicYears)) {
                $academicYears = array_map('trim', array_filter($academicYears));                
            }
            else {
                throw new ValidationException(['year' => ['Academic year(s) are required']], 'Validation failed');
            }

            if (empty($academicYears)) {
                throw new ValidationException(['year' => ['At least one valid academic year is required']], 'Validation failed');
            }
            
            $result = $this->academicSetupService->deleteAcademicYear($academicYears);

            // Log the bulk operation
            $yearList = is_array($academicYears) ? implode(', ', $academicYears) : $academicYears;
            $this->loggingService->logAudit(
                'academic_setup',
                "Bulk academic year deletion attempted: {$yearList}",
                $this->getUserId()
            );

            $response->setStatusCode($result['success'] ? 200 : 207); // 207 for partial success
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while deleting academic year(s)'
            ]));
            return $response;
        }
    }

    /**
     * Deletes a specific academic term.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    public function deleteAcademicTerm(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $academicYear = trim($data['year'] ?? '');
            $term = trim($data['term'] ?? '');

            if (empty($academicYear)) {
                throw new ValidationException(['year' => ['Academic year is required']], 'Validation failed');
            }

            if (empty($term)) {
                throw new ValidationException(['term' => ['Term is required']], 'Validation failed');
            }

            $result = $this->academicSetupService->deleteAcademicTerm($academicYear, $term);

            $this->loggingService->logAudit(
                'academic_setup',
                "Academic term deleted: {$academicYear} - {$term}",
                $this->getUserId()
            );

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(404);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while deleting the academic term'
            ]));
            return $response;
        }
    }

    /**
     * Adds a new term to an existing academic year.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    public function addAcademicTerm(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userId = $this->getUserId();

            $academicYear = trim($data['academic_year'] ?? '');
            $term = trim($data['term'] ?? '');
            $startDate = trim($data['start_date'] ?? '');
            $endDate = trim($data['end_date'] ?? '');
            $status = trim($data['status'] ?? 'Upcoming');

            if (empty($academicYear)) {
                throw new ValidationException(['academic_year' => ['Academic year is required']], 'Validation failed');
            }

            if (empty($term)) {
                throw new ValidationException(['term' => ['Term is required']], 'Validation failed');
            }

            if (empty($startDate)) {
                throw new ValidationException(['start_date' => ['Start date is required']], 'Validation failed');
            }

            if (empty($endDate)) {
                throw new ValidationException(['end_date' => ['End date is required']], 'Validation failed');
            }

            $result = $this->academicSetupService->addAcademicTerm(
                $academicYear,
                $term,
                $startDate,
                $endDate,
                $status,
                $userId
            );

            $this->loggingService->logAudit(
                'academic_setup',
                "Academic term added: {$academicYear} - {$term}",
                $userId
            );

            $response->setStatusCode(201);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $this->loggingService->logAudit(
                'academic_setup_error',
                "Failed to add academic term: {$e->getMessage()}",
                $this->getUserId()
            );
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while adding the academic term'
            ]));
            return $response;
        }
    }

    #[OA\Post(
        path: "/academic/subjects/create",
        summary: "Create a new subject",
        description: "Creates a new subject with specified name, code, and level.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["subject_name", "subject_code", "level"],
                properties: [
                    new OA\Property(property: "subject_name", type: "string", example: "Mathematics"),
                    new OA\Property(property: "subject_code", type: "string", example: "MAT101"),
                    new OA\Property(property: "level", type: "string", example: "JHS", enum: ["Creche", "Pre school", "Lower primary", "Upper primary", "Junior High School"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Subject created successfully"),
            new OA\Response(response: 400, description: "Validation error or subject already exists")
        ]
    )]
    public function createSubject(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userId = $this->getUserId();

            $subjectName = trim(($data['subject_name'] ?? ''));
            $subjectCode = trim(($data['subject_code'] ?? ''));
            $level = trim(($data['level'] ?? ''));
            $category = trim(($data['category'] ?? ''));
            $description = trim(($data['description'] ?? ''));


            if (empty($subjectName) || empty($subjectCode) || empty($level) || empty($category)) {
                throw new ValidationException(
                    [
                        'subject_name' => ['Subject name is required'],
                        'subject_code' => ['Subject code is required'],
                        'level' => ['Subject level is required'],
                        'category' => ['Subject category is required']
                    ],
                    'Validation failed'
                );
            }

            $result = $this->subjectService->createSubject(
                $subjectName,
                $subjectCode,
                $level,
                $category,
                $description,
                $userId
            );

            $this->loggingService->logAudit('subject', "Subject created: {$subjectCode}", $userId);
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        }
    }

    #[OA\Get(
        path: "/academic/subjects/list",
        summary: "List all subjects",
        description: "Retrieve a list of all subjects, optionally filtered by level.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "level", in: "query", description: "Filter by level", required: false, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of subjects")
        ]
    )]
    public function listSubjects(Request $request, Response $response): Response
    {
        
        try {
            $status = $request->getMethod() === 'POST' ? $request->getPost('status') : 'active';

            $result = $this->subjectService->listSubjects($status);
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Updates an existing subject's details.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    public function updateSubject(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userId = $this->getUserId();
            // echo json_encode($data);exit;
            $id = $data['id'] ?? $data['subject_ids'] ?? 0;

            $subjectName = '';
            $subjectCode = '';
            $level = '';
            $category = '';
            $description = '';
            $status = '';

            if (! is_array($id)) {
                $subjectName = trim($data['subject_name'] ?? '');
                $subjectCode = trim($data['subject_code'] ?? '');
                $level = trim($data['level'] ?? '');
                $category = trim($data['category'] ?? '');
                $description = trim($data['description'] ?? '');
                $status = trim($data['status']);

                if ($id <= 0) {
                    throw new ValidationException(['id' => ['Valid subject ID is required']], 'Validation failed');
                }
                if (empty($subjectName) || empty($subjectCode) || empty($level) || empty($category)) {
                    throw new ValidationException(
                        [
                            'subject_name' => ['Subject name is required'],
                            'subject_code' => ['Subject code is required'],
                            'level' => ['Subject level is required'],
                            'category' => ['Subject category is required']
                        ],
                        'Validation failed'
                    );
                }
            }            

            $result = $this->subjectService->updateSubject(
                $id,
                $subjectName,
                $subjectCode,
                $level,
                $category,
                $description,
                $status
            );

            $this->loggingService->logAudit('subject', "Subject ID {$id} updated", $userId);
            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while updating the subject'
            ]));
            return $response;
        }
    }

    /**
     * Sets one or more subjects' status to 'dormant' (soft delete).
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    public function deleteSubject(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userId = $this->getUserId();
            
            // Check if it's a single subject ID/code or an array of them
            $subjectsToDelete = $data['subject_id'] ?? '';
            // Normalize to an array of subject identifiers
            if (is_string($subjectsToDelete) && !empty(trim($subjectsToDelete))) {
                $subjectsToDelete = [trim($subjectsToDelete)];
            }else if (is_int($subjectsToDelete) && !empty(trim($subjectsToDelete))) {
                $subjectsToDelete = [trim($subjectsToDelete)];
            }
            elseif (is_array($subjectsToDelete)) {
                $subjectsToDelete = array_map('trim', array_filter($subjectsToDelete));
            } else {
                throw new ValidationException(['subject_id' => ['Subject ID(s) or code(s) are required']], 'Validation failed');
            }

            if (empty($subjectsToDelete)) {
                throw new ValidationException(['subject_id' => ['At least one valid subject ID or code is required']], 'Validation failed');
            }

            $result = $this->subjectService->deleteSubject($subjectsToDelete);

            // Log the bulk operation
            $subjectList = is_array($subjectsToDelete) ? implode(', ', $subjectsToDelete) : $subjectsToDelete;
            $this->loggingService->logAudit(
                'subject',
                "Bulk subject deletion attempted: {$subjectList}",
                $this->getUserId()
            );

            $response->setStatusCode($result['success'] ? 200 : 207); // 207 for partial success
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while deleting subject(s)'
            ]));
            return $response;
        }
    }

    #[OA\Post(
        path: "/academic/classes/create",
        summary: "Create a new class",
        description: "Creates a new class assigned to a specific level and category.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["class_name", "level_id"],
                properties: [
                    new OA\Property(property: "class_name", type: "string", example: "Basic 1A"),
                    new OA\Property(property: "level_id", type: "integer", description: "ID of the class level (e.g., from class_levels table)")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Class created successfully"),
            new OA\Response(response: 400, description: "Validation error or class already exists")
        ]
    )]
    public function createClass(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $classId = ($data['class_id'] ?? '');
            $className = ($data['class_name'] ?? '');
            $status = ($data['status'] ?? 'active');
            $levelId = isset($data['level_id']) ? $data['level_id'] : null;

            $result = $this->classService->createClass($classId, $className, $levelId);

            $this->loggingService->logAudit('class', "Class created: {$classId}", $this->getUserId());
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Lists classes filtered by status.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listClasses(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $status = ($data['status'] ?? 'active');

            $result = $this->classService->listClasses($status);
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Updates an existing class's details.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    /**
     * Updates an existing class's details.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    public function updateClass(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userId = $this->getUserId();
            $id = $data['id'] ?? $data['class_ids'] ?? 0;
            $classId = "";
            $className = '';
            $status = '';

            if (! is_array($id)) {
                $id = (int)$id;
                $classId = ($data['class_id'] ?? "");
                $className = trim(($data['class_name'] ?? ''));
                $status = trim(($data['status'] ?? 'active'));

                if ($id <= 0) {
                    throw new ValidationException(['id' => ['Valid ID is required']], 'Validation failed');
                }

                if ($classId === "") {
                    throw new ValidationException(['class_id' => ['Valid class ID is required']], 'Validation failed');
                }
                if (empty($className)) {
                    throw new ValidationException(['class_name' => ['Class name is required']], 'Validation failed');
                }
            }

            
            $levelId = isset($data['level_id']) ? $data['level_id'] : null;
            $result = $this->classService->updateClass($id, $classId, $className, $status, $levelId);

            $this->loggingService->logAudit('class', "Class ID {$classId} updated", $userId);
            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));

            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while updating the class'
            ]));
            return $response;
        }
    }

    /**
     * Deletes one or more classes.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    /**
     * Deletes one or more classes.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with success status
     */
    public function deleteClass(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userId = $this->getUserId();


            $classesToDelete = $data['class_ids'] ?? '';

            if (is_string($classesToDelete) && !empty(trim($classesToDelete))) {
                $classesToDelete = [(int)trim($classesToDelete)];
            } elseif (is_int($classesToDelete)) {
                $classesToDelete = [$classesToDelete];
            }
            elseif (is_array($classesToDelete)) {
                $classesToDelete = array_map(function($id) { return (int)trim($id); }, array_filter($classesToDelete));
            } else {
                throw new ValidationException(['class_ids' => ['Class ID(s) are required']], 'Validation failed');
            }

            if (empty($classesToDelete)) {
                throw new ValidationException(['class_ids' => ['At least one valid class ID is required']], 'Validation failed');
            }

            $result = $this->classService->deleteClasses($classesToDelete);

            $classList = implode(', ', $classesToDelete);
            $this->loggingService->logAudit('class', "Bulk class deletion attempted: {$classList}", $userId);

            $response->setStatusCode($result['success'] ? 200 : 207);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while deleting class(es)'
            ]));
            return $response;
        }
    }

    /**
     * Assigns one or more subjects to one or more classes.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function assignSubjectToClass(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $classIds = $data['class_id'] ?? [];
            $subjectIds = $data['subject_id'] ?? [];

            // Normalize to arrays
            if (!is_array($classIds)) {
                $classIds = [$classIds];
            }
            $classIds = array_map('intval', array_filter($classIds));

            if (!is_array($subjectIds)) {
                $subjectIds = [$subjectIds];
            }
            $subjectIds = array_map('intval', array_filter($subjectIds));

            if (empty($classIds) || empty($subjectIds)) {
                throw new ValidationException(
                    [
                        'class_id' => ['At least one Class ID is required'],
                        'subject_id' => ['At least one Subject ID is required']
                    ],
                    'Validation failed'
                );
            }

            
            $result = $this->classSubjectService->assignSubjectToClass(
                $classIds,
                $subjectIds,
                $this->getUserId()
            );

            $this->loggingService->logAudit('class_subject', "Subjects assigned/bulk assigned to class", $this->getUserId());
            $response->setContent(json_encode($result));
            $response->setStatusCode(200);
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Unassigns one or more subjects from a class.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function bulkRemoveSubjectsFromClass(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $classId = (int)($data['class_id'] ?? 0);
            $subjectIds = $data['subject_ids'] ?? [];

            if (!is_array($subjectIds)) {
                $subjectIds = [$subjectIds];
            }
            $subjectIds = array_map('intval', array_filter($subjectIds));

            if (empty($classId) || empty($subjectIds)) {
                throw new ValidationException(
                    [
                        'class_id' => ['Class ID is required'],
                        'subject_ids' => ['At least one subject ID is required']
                    ],
                    'Validation failed'
                );
            }

            $result = $this->classSubjectService->bulkRemoveSubjectsFromClass(
                $classId,
                $subjectIds
            );

            $this->loggingService->logAudit('class_subject', "Bulk subjects unassigned from class ID: {$classId}", $this->getUserId());
            $response->setContent(json_encode($result));
            $response->setStatusCode(200);
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Retrieves subjects assigned to a class.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getClassSubjects(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            //echo json_encode($data);exit;

            if ($request->getMethod() === 'GET') {
                $result = $this->classSubjectService->getClassSubjects(0);
            }else {
                $result = $this->classSubjectService->getClassSubjects(
                    (int)($data['class_id'] ?? 0)
                );
            }            

            $response->setContent(json_encode($result));

            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Assigns a subject to a teacher for a class and academic year.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function assignSubjectToTeacher(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $result = $this->teacherSubjectService->assignSubjectToTeacher(
                ($data['staff_id'] ?? ''),
                (int)($data['subject_id'] ?? 0),
                $data['class_id'] ?? null,
                $data['academic_year'] ?? null,
                $this->getUserId()
            );

            $this->loggingService->logAudit('teacher_subject', "Subject assigned to teacher", $this->getUserId());
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Retrieves subjects assigned to a teacher.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getTeacherSubjects(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $result = $this->teacherSubjectService->getTeacherSubjects(
                ($data['staff_id'] ?? ''),
                $data['academic_year'] ?? null
            );

            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    #[OA\Post(
        path: "/academic/scores/add",
        summary: "Add student activity score",
        description: "Records a score for a student for a specific subject and activity.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["student_no", "subject_id", "activity_id", "class_id", "academic_year", "term", "score"],
                properties: [
                    new OA\Property(property: "student_no", type: "string", example: "STU2024001"),
                    new OA\Property(property: "subject_id", type: "integer", example: 1),
                    new OA\Property(property: "activity_id", type: "integer", example: 5, description: "ID of the assignment activity"),
                    new OA\Property(property: "class_id", type: "integer", example: 6),
                    new OA\Property(property: "academic_year", type: "string", example: "2024/2025"),
                    new OA\Property(property: "term", type: "string", example: "Term 1"),
                    new OA\Property(property: "score", type: "number", example: 85.5)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Score added successfully"),
            new OA\Response(response: 400, description: "Validation error")
        ]
    )]
    public function addStudentScore(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();   

            $data['academic_year'] = $data['academic_year'] ?? Session::get('user')['academic_year'] ?? null;
            $data['term'] = $data['term'] ?? Session::get('user')['term'] ?? null;

            $data['activity_id'] = is_string($data['activity_id']) ? Student::where('sub_activity_id', $data['activity_id'], 'activities')->id : $data['activity_id'];

            $data['class_id'] = is_string($data['class_id']) ? Student::where('class_id', $data['class_id'], 'classes')->id : $data['class_id'];

            //echo json_encode($data);exit;
            // Validate required fields
            $required = ['student_no', 'subject_id', 'activity_id', 'class_id', 'academic_year', 'term', 'score'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Field '{$field}' is required");
                }
            }

            $result = $this->studentScoreService->addActivityScore(
                $data['student_no'],
                (int)$data['subject_id'],
                (int)$data['activity_id'],
                (int)$data['class_id'],
                $data['academic_year'],
                $data['term'],
                (float)$data['score'],
                $this->getUserId()
            );

            $this->loggingService->logAudit('student_score', "Activity score recorded", $this->getUserId());

            // Queue summarization job
            \App\Core\Queue::dispatch(\Jobs\SummarizeScoresJob::class, [
                'academicYear' => $data['academic_year'],
                'term' => $data['term']
            ]);

            $response->setContent(json_encode([
                "success"=>true,
                "message"=>"Score added successfully",
                "data"=>$result
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Retrieves scores for a specific student.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getStudentScores(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
//echo json_encode($data);exit;
            $result = $this->studentScoreService->getStudentScores(
                ($data['student_no'] ?? ''),
                $data['academic_year'] ?? null,
                $data['term'] ?? null
            );

            $response->setContent(json_encode([
                'success' => true,
                'message' => 'Student scores retrieved successfully',
                'data'=>$result
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Retrieves scores for an entire class.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getClassScores(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $academic_year = $data['academic_year'] ?? Session::get('user')['academic_year'] ?? null;
            $term = $data['term'] ?? Session::get('user')['term'] ?? null;

            
            $result = $this->studentScoreService->getClassScores(
                (int)($data['class_id'] ?? 0),
                ($academic_year ?? ''),
                ($term ?? ''),
                $data['subject_id'] ?? null
            );

            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Bulk adds student scores.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function bulkAddScores(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $scores = $data['scores'] ?? [];
            if (!is_array($scores)) {
                throw new \Exception('Scores must be an array');
            }

            $result = $this->studentScoreService->bulkAddScores($scores, $this->getUserId());

            $this->loggingService->logAudit('student_score', "Bulk scores recorded", $this->getUserId());

            // Queue summarization job if we have scores
            if (!empty($scores)) {
                $firstScore = $scores[0];
                $acYear = $firstScore['academic_year'] ?? $data['academic_year'] ?? Session::get('user')['academic_year'] ?? '';
                $term = $firstScore['term'] ?? $data['term'] ?? Session::get('user')['term'] ?? '';
                
                if ($acYear && $term) {
                    \App\Core\Queue::dispatch(\Jobs\SummarizeScoresJob::class, [
                        'academicYear' => $acYear,
                        'term' => $term
                    ]);
                }
            }

            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Handles CSV file upload for bulk score import.
     *
     * @param Request $request The HTTP request object
     * @param Response $response The HTTP response object
     * @return Response JSON response with import results
     */
    public function uploadScoresCSV(Request $request, Response $response): Response
    {
        try {
            if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
                throw new ValidationException(['file' => ['No file uploaded']], 'Validation failed');
            }

            $file = $_FILES['file'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
                ];
                $errorMsg = $errorMessages[$file['error']] ?? 'Unknown upload error';
                throw new ValidationException(['file' => [$errorMsg]], 'Validation failed');
            }
            
            // Validate file type
            $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, ['csv', 'txt']) && !in_array($file['type'], $allowedTypes)) {
                throw new ValidationException(['file' => ['Invalid file type. Only CSV files are allowed']], 'Validation failed');
            }

            // Validate file size (max 5MB)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                throw new ValidationException(['file' => ['File size exceeds maximum allowed size of 5MB']], 'Validation failed');
            }

            $tmpPath = $file['tmp_name'];
            $result = $this->studentScoreService->importFromCSV($tmpPath, $this->getUserId());

            $this->loggingService->logAudit(
                'student_score',
                "CSV scores uploaded: {$file['name']}",
                $this->getUserId()
            );

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $this->loggingService->logAudit(
                'student_score_error',
                "CSV upload failed: {$e->getMessage()}",
                $this->getUserId()
            );
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while uploading the CSV file'
            ]));
            return $response;
        }
    }

    /**
     * Creates a new assignment activity.
     */
    #[OA\Post(
        path: "/academic/activities/create",
        summary: "Create assignment activity",
        description: "Creates a new type of assignment activity (e.g., 'Weekly Quiz').",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["activity_name", "max_score", "weight"],
                properties: [
                    new OA\Property(property: "activity_name", type: "string", example: "Unit Test 1"),
                    new OA\Property(property: "max_score", type: "number", example: 100),
                    new OA\Property(property: "weight", type: "number", example: 0.1, description: "Weight of this activity in the final grade (0.0 to 1.0)"),
                    new OA\Property(property: "is_standalone", type: "integer", example: 0, description: "Whether the activity is standalone (1) or tied to a subject (0)")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Activity created successfully")
        ]
    )]
    public function createAssignmentActivity(Request $request, Response $response): Response
    {
        try {
            $data = (array)$request->getPost();
            $userId = $this->getUserId();

            //echo json_encode($data);exit;

            $result = $this->assignmentActivityService->createActivity($data, $userId);

            $this->loggingService->logAudit(
                'assignment_activity',
                "Assignment activity created: " . ($data['activity_id'] ?? ''),
                $userId
            );

            $response->setStatusCode(201);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors() ?? []
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while creating the assignment activity',
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Lists assignment activities.
     */
    #[OA\Get(
        path: "/academic/activities/list",
        summary: "List all assignment activities",
        description: "Retrieve a list of all defined assignment activities.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of activities")
        ]
    )]
    public function listAssignmentActivities(Request $request, Response $response): Response
    {
        try {
            $academicYear = ($request->getPost('academic_year') ?? $request->getQuery('academic_year', ''));
            $term = ($request->getPost('term') ?? $request->getQuery('term', ''));
            $status = ($request->getPost('status') ?? $request->getQuery('status', 'active'));

            $result = $this->assignmentActivityService->listActivities($academicYear, $term, $status);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while listing assignment activities',
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Updates an existing assignment activity.
     */
    /**
     * Updates an existing assignment activity.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateAssignmentActivity(Request $request, Response $response): Response
    {
        try {
            $data = (array)$request->getPost();
            $activityId = ($data['activity_id'] ?? '');
            $userId = $this->getUserId();

            if (empty($activityId)) {
                throw new ValidationException(['activity_id' => ['Activity ID is required']], 'Validation failed');
            }

            $result = $this->assignmentActivityService->updateActivity($activityId, $data);

            $this->loggingService->logAudit('assignment_activity', "Assignment activity updated: {$activityId}", $userId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while updating the activity', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Soft deletes an assignment activity (sets status to inactive).
     */
    /**
     * Soft deletes an assignment activity (sets status to inactive).
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deleteAssignmentActivity(Request $request, Response $response): Response
    {
        try {
            $activityId = ($request->getPost('activity_id') ?? '');
            $userId = $this->getUserId();

            if (empty($activityId)) {
                throw new ValidationException(['activity_id' => ['Activity ID is required']], 'Validation failed');
            }

            $result = $this->assignmentActivityService->softDelete($activityId);

            $this->loggingService->logAudit('assignment_activity', "Assignment activity soft-deleted: {$activityId}", $userId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while deleting the activity', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Permanently deletes an assignment activity (Super Admin only).
     */
    /**
     * Permanently deletes an assignment activity (Super Admin only).
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function permanentDeleteAssignmentActivity(Request $request, Response $response): Response
    {
        try {
            if (! $this->isSuperUser()) {
                $response->setStatusCode(403);
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent(json_encode(['success' => false, 'message' => 'Permission denied. Only Super Admin can perform permanent deletion.']));
                return $response;
            }

            $activityId = ($request->getPost('activity_id') ?? '');
            $userId = $this->getUserId();

            if (empty($activityId)) {
                throw new ValidationException(['activity_id' => ['Activity ID is required']], 'Validation failed');
            }

            $result = $this->assignmentActivityService->permanentDelete($activityId);

            $this->loggingService->logAudit('assignment_activity', "Assignment activity permanently deleted: {$activityId}", $userId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while permanently deleting the activity', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Lists inactive assignment activities.
     */
    /**
     * Lists inactive assignment activities.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listInactiveAssignmentActivities(Request $request, Response $response): Response
    {
        try {
            $academicYear = ($request->getPost('academic_year') ?? $request->getQuery('academic_year', ''));
            $term = ($request->getPost('term') ?? $request->getQuery('term', ''));

            $result = $this->assignmentActivityService->listActivities($academicYear, $term, 'inactive');

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while listing inactive assignment activities',
                'error' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Reactivates an inactive assignment activity.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function activateAssignmentActivity(Request $request, Response $response): Response
    {
        try {
            $activityId = ($request->getPost('activity_id') ?? '');
            $userId = $this->getUserId();

            if (empty($activityId)) {
                throw new ValidationException(['activity_id' => ['Activity ID is required']], 'Validation failed');
            }

            $result = $this->assignmentActivityService->activate($activityId);

            $this->loggingService->logAudit('assignment_activity', "Assignment activity reactivated: {$activityId}", $userId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while reactivating the activity', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Assigns an assignment activity to a class.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function assignActivityToClass(Request $request, Response $response): Response
    {
        try {
            $data = (array)$request->getPost();
            $userId = $this->getUserId();
            
            $classId = ClassActivityAssignment::where('id', $data['class_id'], 'classes');

            $data['class_id'] = is_numeric($data['class_id']) ? $classId->class_id : $data['class_id'];
            $result = $this->classActivityAssignmentService->assignActivity($data, $userId);

            $this->loggingService->logAudit('class_activity_assignment', "Activity " . ($data['act_id'] ?? '') . " assigned to class " . ($data['class_id'] ?? ''), $userId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while assigning activity', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Unassigns an assignment activity from a class.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function unassignActivityFromClass(Request $request, Response $response): Response
    {
        try {
            $classId = ($request->getPost('class_id') ?? '');
            $actId = ($request->getPost('act_id') ?? '');
            $userId = $this->getUserId();

            $classId = is_numeric($classId) ? ClassActivityAssignment::where('id', $classId, 'classes')->class_id : $classId;
//echo json_encode($actId);exit;
            $result = $this->classActivityAssignmentService->unassignActivity($classId, $actId);

            $this->loggingService->logAudit('class_activity_assignment', "Activity {$actId} unassigned from class {$classId}", $userId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while unassigning activity', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Lists all activities assigned to a class.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listClassActivityAssignments(Request $request, Response $response): Response
    {
        try {
            $classId = ($request->getPost('class_id') ?? $request->getQuery('class_id', ''));

            $classId = is_numeric($classId) ? ClassActivityAssignment::where('id', $classId, 'classes')->class_id : $classId;
            
            if (empty($classId)) {
                throw new ValidationException(['class_id' => ['Class ID is required']], 'Validation failed');
            }

            $result = $this->classActivityAssignmentService->listClassActivities($classId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while listing class activities', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Lists all individual activities assigned to a class.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listClassIndividualActivityAssignments(Request $request, Response $response): Response
    {
        try {
            $classId = ($request->getPost('class_id') ?? $request->getQuery('class_id', ''));

            $classId = is_numeric($classId) ? ClassActivityAssignment::where('id', $classId, 'classes')->class_id : $classId;
            
            if (empty($classId)) {
                throw new ValidationException(['class_id' => ['Class ID is required']], 'Validation failed');
            }

            $result = $this->classActivityAssignmentService->listIndividualClassActivities($classId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while listing class activities', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Creates a new grading scheme entry.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function createGradingScheme(Request $request, Response $response): Response
    {
        try {
            $data = (array)$request->getPost();
            $userId = $this->getUserId();

            $result = $this->gradingSchemeService->createGrading($data, $userId);

            $this->loggingService->logAudit('grading_scheme', "Grading entry created: " . ($data['grade'] ?? ''), $userId);

            $response->setStatusCode(201);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while creating grading entry', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Lists all grading scheme entries.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listGradingSchemes(Request $request, Response $response): Response
    {
        try {
            $result = $this->gradingSchemeService->listGrading();

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while listing grading scheme', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Updates an existing grading scheme entry.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateGradingScheme(Request $request, Response $response): Response
    {
        try {
            $data = (array)$request->getPost();
            $id = (int)($data['id'] ?? 0);
            $userId = $this->getUserId();

            if (empty($data['grade'])) {
                throw new ValidationException(['grade' => ['Grade is required']], 'Validation failed');
            }

            $result = $this->gradingSchemeService->updateGrading($id, $data);

            $this->loggingService->logAudit('grading_scheme', "Grading entry updated: " . ($data['grade'] ?? ''), $userId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while updating grading entry', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Deletes a grading scheme entry.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deleteGradingScheme(Request $request, Response $response): Response
    {
        try {
            $id = (int)($request->getPost('id') ?? 0);
            $userId = $this->getUserId();

            if (!$id) {
                throw new ValidationException(['id' => ['ID is required']], 'Validation failed');
            }

            $result = $this->gradingSchemeService->deleteGrading($id);

            $this->loggingService->logAudit('grading_scheme', "Grading entry deleted: ID {$id}", $userId);

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode($result));
            return $response;
        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'An error occurred while deleting grading entry', 'error' => $e->getMessage()]));
            return $response;
        }
    }

    /**
     * Submits scores for summarization.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function submitScores(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $userId = $this->getUserId();

            
            
            $academicYear = $data['academic_year'] ?? $data['year'] ?? '';
            $term = $data['term'] ?? '';

            if (empty($academicYear) || empty($term)) {
                 throw new ValidationException([
                    'academic_year' => ['Academic year is required'],
                    'term' => ['Term is required']
                 ], 'Validation failed');
            }

            // Trigger queue worker to process pending jobs
            // Trigger queue worker to process pending jobs
            // Use --stop-when-empty to run all pending jobs and exit
            $serverScript = dirname(__DIR__, 4) . '/worker';
            $debugLog = dirname(__DIR__, 4) . '/worker_debug.log';
            $phpBinary = PHP_BINARY;

            // Log the attempt
            $commandToLog = "";

            // Windows background execution
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Normalize paths to Windows style backslashes
                $phpBinary = str_replace('/', '\\', $phpBinary);
                $serverScript = str_replace('/', '\\', $serverScript);
                $debugLog = str_replace('/', '\\', $debugLog);
                
                // Construct command
                // cmd /c ""path with spaces" "arg with spaces" ..."
                // capture entire inner command in quotes for cmd /c
                
                $innerCmd = "\"$phpBinary\" \"$serverScript\" queue:work --stop-when-empty > \"$debugLog\" 2>&1";
                $cmd = "start \"QueueWorker\" /B cmd /c \"$innerCmd\"";
                
                $commandToLog = $cmd;
                pclose(popen($cmd, "r"));
            } else {
                $cmd = "\"$phpBinary\" \"$serverScript\" queue:work --stop-when-empty > \"$debugLog\" 2>&1 &";
                $commandToLog = $cmd;
                exec($cmd);
            }
            
            $this->loggingService->logAudit(
                'student_scores',
                "Triggered Worker Command: {$commandToLog}",
                $userId
            );

            $this->loggingService->logAudit(
                'student_scores',
                "Scores submitted for summarization (Processing Triggered): {$academicYear} - {$term}",
                $userId
            );

            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => true, 
                'message' => 'Processing started. Scores are being summarized in the background.'
            ]));
            return $response;

        } catch (ValidationException $e) {
            $response->setStatusCode(400);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'Error submitting scores: ' . $e->getMessage()]));
            return $response;
        }
    }
    #[OA\Post(
        path: "/academic/scores/summary/list",
        summary: "Get student summary reports",
        description: "Retrieves summary reports based on filters.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "student_no", type: "string"),
                    new OA\Property(property: "academic_year", type: "string"),
                    new OA\Property(property: "term", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "List of summary reports")
        ]
    )]
    public function getSummaryReports(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            
            $studentNo = $data['student_no'] ?? null;
            $academicYear = $data['academic_year'] ?? null;
            $term = $data['term'] ?? null;

            $result = $this->studentScoreService->getSummaryReports($studentNo, $academicYear, $term);
            
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }

    #[OA\Post(
        path: "/academic/scores/report/list",
        summary: "Get aggregated student reports",
        description: "Retrieves aggregated student reports (SBA + Exam) based on filters.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "student_no", type: "string"),
                    new OA\Property(property: "academic_year", type: "string"),
                    new OA\Property(property: "term", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "List of aggregated reports")
        ]
    )]
    public function getStudentReports(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            
            $studentNo = $data['student_no'] ?? null;
            $academicYear = $data['academic_year'] ?? Session::get('user')['academic_year'];
            $term = $data['term'] ?? Session::get('user')['term'];
            $class_id = $data['class_id'] ?? null;

            $result = $this->studentScoreService->getStudentReports($studentNo, $academicYear, $term, $class_id);
            
            $response->setContent(json_encode($result));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode(['success' => false, 'message' => $e->getMessage()]));
            return $response;
        }
    }
}
