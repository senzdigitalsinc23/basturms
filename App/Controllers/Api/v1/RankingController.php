<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\RankingService;
use App\Services\LoggingService;
use App\Exceptions\ValidationException;
use App\Models\ClassActivityAssignment;
use App\Models\Classes;
use App\Models\Student;
use OpenApi\Attributes as OA;

/**
 * Controller for student, class, and subject ranking endpoints.
 */
#[OA\Tag(
    name: "Rankings",
    description: "Endpoints for retrieving subject-level and class-level student rankings"
)]
class RankingController
{
    private RankingService $rankingService;
    private LoggingService $loggingService;

    public function __construct(RankingService $rankingService, LoggingService $loggingService)
    {
        $this->rankingService = $rankingService;
        $this->loggingService = $loggingService;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function getUserId(): string
    {
        $user = Session::get('user');
        if (is_array($user) && isset($user['user_id'])) return $user['user_id'];
        if (is_array($user) && isset($user['id']))      return $user['id'];
        return Session::get('user_id', 'system');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Endpoints
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get per-subject rankings for a class in a given academic year and term.
     *
     * POST /academic/rankings/subjects
     *
     * Body: { class_id, academic_year, term, subject_id? }
     */
    #[OA\Post(
        path: "/academic/rankings/subjects",
        summary: "Get subject rankings for a class",
        description: "Returns each student's score and position for each subject in the class for the specified term.",
        tags: ["Rankings"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["class_id", "academic_year", "term"],
                properties: [
                    new OA\Property(property: "class_id",      type: "integer", example: 1),
                    new OA\Property(property: "academic_year", type: "string",  example: "2024/2025"),
                    new OA\Property(property: "term",          type: "string",  example: "Term 1"),
                    new OA\Property(property: "subject_id",    type: "integer", description: "Optional: filter to a specific subject", example: 3),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Subject rankings retrieved successfully"),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 404, description: "Class not found"),
            new OA\Response(response: 500, description: "Server error"),
        ]
    )]
    public function subjectRankings(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            // Resolve class string ID to internal integer ID if needed
            if (isset($data['class_id']) && is_string($data['class_id'])) {
                $class = Classes::where('class_id', $data['class_id'], 'classes');
                if ($class) {
                    $data['class_id'] = $class->id;
                } else {
                    // Try by name as fallback
                    $class = Classes::where('class_name', $data['class_id'], 'classes');
                    if ($class) {
                        $data['class_id'] = $class->id;
                    }
                }
            }

            $classId      = (int)($data['class_id']      ?? 0);
            $academicYear = trim($data['academic_year']  ?? '');
            $term         = trim($data['term']           ?? '');
            $subjectId    = isset($data['subject_id']) && $data['subject_id'] ? (int)$data['subject_id'] : null;

            if (!$classId || empty($academicYear) || empty($term)) {
                throw new ValidationException(
                    [
                        'class_id'      => ['Valid Class ID is required (resolved from ' . ($request->getPost('class_id') ?? 'null') . ')'],
                        'academic_year' => ['Academic year is required'],
                        'term'          => ['Term is required'],
                    ],
                    'Validation failed'
                );
            }

            $result = $this->rankingService->getSubjectRankings($classId, $academicYear, $term, $subjectId);

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
                'errors'  => $e->getErrors() ?? [],
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(404);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while retrieving subject rankings',
            ]));
            return $response;
        }
    }

    /**
     * Get overall class rankings for a term.
     *
     * POST /academic/rankings/class
     *
     * Body: { class_id, academic_year, term }
     */
    #[OA\Post(
        path: "/academic/rankings/class",
        summary: "Get class rankings for a term",
        description: "Returns sorted class positions for all students aggregated across all subjects for the given term.",
        tags: ["Rankings"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["class_id", "academic_year", "term"],
                properties: [
                    new OA\Property(property: "class_id",      type: "integer", example: 1),
                    new OA\Property(property: "academic_year", type: "string",  example: "2024/2025"),
                    new OA\Property(property: "term",          type: "string",  example: "Term 1"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Class rankings retrieved successfully"),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 404, description: "Class not found"),
            new OA\Response(response: 500, description: "Server error"),
        ]
    )]
    public function classRankings(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            // Resolve class string ID to internal integer ID if needed
            if (isset($data['class_id']) && is_string($data['class_id'])) {
                $class = Classes::where('class_id', $data['class_id'], 'classes');
                if ($class) {
                    $data['class_id'] = $class->id;
                } else {
                    // Try by name as fallback
                    $class = Classes::where('class_name', $data['class_id'], 'classes');
                    if ($class) {
                        $data['class_id'] = $class->id;
                    }
                }
            }

            $classId      = (int)($data['class_id']      ?? 0);
            $academicYear = trim($data['academic_year']  ?? '');
            $term         = trim($data['term']           ?? '');

            if (!$classId || empty($academicYear) || empty($term)) {
                throw new ValidationException(
                    [
                        'class_id'      => ['Valid Class ID is required (resolved from ' . ($request->getPost('class_id') ?? 'null') . ')'],
                        'academic_year' => ['Academic year is required'],
                        'term'          => ['Term is required'],
                    ],
                    'Validation failed'
                );
            }

            $result = $this->rankingService->getClassRankings($classId, $academicYear, $term);

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
                'errors'  => $e->getErrors() ?? [],
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(404);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while retrieving class rankings',
            ]));
            return $response;
        }
    }

    /**
     * Get a single student's full ranking summary (overall + per-subject).
     *
     * POST /academic/rankings/student
     *
     * Body: { student_no, academic_year, term }
     */
    #[OA\Post(
        path: "/academic/rankings/student",
        summary: "Get ranking summary for a student",
        description: "Returns the student's class position and per-subject positions for the specified term.",
        tags: ["Rankings"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["student_no", "academic_year", "term"],
                properties: [
                    new OA\Property(property: "student_no",    type: "string",  example: "STU-0001"),
                    new OA\Property(property: "academic_year", type: "string",  example: "2024/2025"),
                    new OA\Property(property: "term",          type: "string",  example: "Term 1"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Student ranking retrieved successfully"),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 404, description: "No ranking data found for the student"),
            new OA\Response(response: 500, description: "Server error"),
        ]
    )]
    public function studentRanking(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $studentNo    = trim($data['student_no']    ?? '');
            $academicYear = trim($data['academic_year'] ?? '');
            $term         = trim($data['term']           ?? '');

            if (empty($studentNo) || empty($academicYear) || empty($term)) {
                throw new ValidationException(
                    [
                        'student_no'    => ['Student number is required'],
                        'academic_year' => ['Academic year is required'],
                        'term'          => ['Term is required'],
                    ],
                    'Validation failed'
                );
            }

            $result = $this->rankingService->getStudentRanking($studentNo, $academicYear, $term);

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
                'errors'  => $e->getErrors() ?? [],
            ]));
            return $response;
        } catch (\RuntimeException $e) {
            $response->setStatusCode(404);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while retrieving student ranking',
            ]));
            return $response;
        }
    }

    /**
     * Get rankings for all students in a specific school level.
     *
     * POST /academic/rankings/level
     *
     * Body: { level_id, academic_year, term }
     */
    #[OA\Post(
        path: "/academic/rankings/level",
        summary: "Get rankings for a school level",
        description: "Returns sorted positions for all students in a specific school level (e.g., JHS) for the given term.",
        tags: ["Rankings"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["level_id", "academic_year", "term"],
                properties: [
                    new OA\Property(property: "level_id",       type: "string",  example: "JHS"),
                    new OA\Property(property: "academic_year",  type: "string",  example: "2024/2025"),
                    new OA\Property(property: "term",           type: "string",  example: "Term 1"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Level rankings retrieved successfully"),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 500, description: "Server error"),
        ]
    )]
    public function levelRankings(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            

            $levelId      = trim($data['level_id']       ?? '');
            $academicYear = trim($data['academic_year']  ?? Session::get('user')['academic_year']);
            $term         = trim($data['term']           ?? Session::get('user')['term']);
            
            //echo json_encode($term);exit;

            if (empty($levelId) || empty($academicYear) || empty($term)) {
                throw new ValidationException(
                    [
                        'level_id'      => ['School level is required'],
                        'academic_year' => ['Academic year is required'],
                        'term'          => ['Term is required'],
                    ],
                    'Validation failed'
                );
            }

            $result = $this->rankingService->getLevelRankings($levelId, $academicYear, $term);

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
                'errors'  => $e->getErrors() ?? [],
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e,
            ]));
            return $response;
        }
    }

    /**
     * Get rankings for the entire school.
     *
     * POST /academic/rankings/school
     *
     * Body: { academic_year, term }
     */
    #[OA\Post(
        path: "/academic/rankings/school",
        summary: "Get school-wide rankings",
        description: "Returns sorted positions for all students in the entire school for the given term.",
        tags: ["Rankings"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["academic_year", "term"],
                properties: [
                    new OA\Property(property: "academic_year",  type: "string",  example: "2024/2025"),
                    new OA\Property(property: "term",           type: "string",  example: "Term 1"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "School rankings retrieved successfully"),
            new OA\Response(response: 400, description: "Validation error"),
            new OA\Response(response: 500, description: "Server error"),
        ]
    )]
    public function schoolRankings(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $academicYear = trim($data['academic_year']  ??  Session::get('user')['academic_year']);
            $term         = trim($data['term']           ??  Session::get('user')['term']);

            if (empty($academicYear) || empty($term)) {
                throw new ValidationException(
                    [
                        'academic_year' => ['Academic year is required'],
                        'term'          => ['Term is required'],
                    ],
                    'Validation failed'
                );
            }

            $result = $this->rankingService->getSchoolRankings($academicYear, $term);

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
                'errors'  => $e->getErrors() ?? [],
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'message' => 'An error occurred while retrieving school rankings',
            ]));
            return $response;
        }
    }
}
