<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\StudentPromotionService;
use App\Repositories\StudentPromotionRepository;
use App\Services\LoggingService;
use App\Core\Logger;
use App\Exceptions\PromotionException;

/**
 * Controller for student promotion API requests.
 */
class PromotionController
{
    private StudentPromotionService $promotionService;
    private StudentPromotionRepository $promotionRepo;
    private LoggingService $loggingService;

    /**
     * @param LoggingService $loggingService
     */
    public function __construct(LoggingService $loggingService)
    {
        $this->promotionRepo = new StudentPromotionRepository();
        $subjectRepo = new \App\Repositories\SubjectRepository();
        $scoreRepo = new \App\Repositories\StudentScoreRepository();
        
        $this->promotionService = new StudentPromotionService(
            $this->promotionRepo,
            $subjectRepo,
            $scoreRepo
        );
        $this->loggingService = $loggingService;
    }

    /**
     * Handles normal student promotion.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function promoteNormal(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $studentId = (string)($data['student_no'] ?? '');
            $currentClassId = isset($data['current_class_id']) ? (string)$data['current_class_id'] : null;
            $remarks = isset($data['remarks']) ? (string)$data['remarks'] : null;
            
            $userSession = (array)Session::get('user');
            $promotedBy = (string)($userSession['user_id'] ?? $data['promoted_by'] ?? 'system');

            // bulk path: accept `students` array
            if (isset($data['students']) && is_array($data['students'])) {
                $students = (array)$data['students'];
                $result = $this->promotionService->bulkPromoteNormal(
                    $students,
                    $currentClassId,
                    $promotedBy,
                    $remarks
                );

                $this->loggingService->logAudit('promotion', "Bulk promotion executed by {$promotedBy}", $promotedBy);
                $response->setContent(json_encode($result));
                return $response;
            }

            if (!$studentId) {
                $response->setStatusCode(400);
                $this->loggingService->logApiDebugError("Missing required field: student_no");
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'Missing required field: student_no',
                ]));
                return $response;
            }

            // currentClassId is optional; the service will attempt to resolve it automatically
            $result = $this->promotionService->promoteStudentNormal(
                $studentId,
                $currentClassId,
                $promotedBy,
                $remarks
            );


            $this->loggingService->logAudit('promotion', "Student {$studentId} promoted to next class", $promotedBy);
            $response->setContent(json_encode($result));
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('promotion_error', "Promotion failed: " . $e->getMessage());
            $response->setStatusCode(400);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
            return $response;
        }
    }

    /**
     * Handles special student promotion.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function promoteSpecial(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            $studentId = (string)($data['student_no'] ?? '');
            $currentClassId = (string)($data['current_class_id'] ?? '');
            $targetClassId = (string)($data['target_class_id'] ?? '');
            $remarks = (string)($data['remarks'] ?? '');
            
            $userSession = (array)Session::get('user');
            $promotedBy = (string)($userSession['user_id'] ?? $data['promoted_by'] ?? 'system');

            // bulk special: accept students array
            if (isset($data['students']) && is_array($data['students'])) {
                $students = (array)$data['students'];
                $result = $this->promotionService->bulkPromoteSpecial($students, $targetClassId, $promotedBy, $remarks);
                $this->loggingService->logAudit('promotion', "Bulk special promotion executed by {$promotedBy}", $promotedBy);
                $response->setContent((string)json_encode($result));
                return $response;
            }

            if (!$studentId) {
                $response->setStatusCode(400);
                $this->loggingService->logAudit('promotion_error', "Missing required fields: student_no");
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: student_no',
                ]));
                return $response;
            } else if (!$targetClassId) {
                $response->setStatusCode(400);
                $this->loggingService->logAudit('promotion_error', "Missing required fields: target_class_id");
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: target_class_id',
                ]));
                return $response;
            } else if (empty($remarks)) {
                $response->setStatusCode(400);
                $this->loggingService->logAudit('promotion_error', "Remarks is required for special promotion");
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Remarks is required for special promotion',
                ]));
                return $response;
            }

            $result = $this->promotionService->promoteStudentSpecial(
                $studentId,
                $currentClassId,
                $targetClassId,
                $promotedBy,
                $remarks
            );

            $this->loggingService->logAudit('promotion', "Student {$studentId} moved to class {$targetClassId} (special)", $promotedBy);
            $response->setContent((string)json_encode($result));
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('promotion_error', "Special promotion failed: " . $e->getMessage());
            $response->setStatusCode(400);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
            return $response;
        }
    }

    /**
     * Handles student graduation.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function graduate(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            $studentId = (string)($data['student_no'] ?? '');
            $currentClassId = isset($data['current_class_id']) ? (string)$data['current_class_id'] : null;
            $remarks = isset($data['remarks']) ? (string)$data['remarks'] : null;
            
            $userSession = (array)Session::get('user');
            $promotedBy = (string)($userSession['user_id'] ?? $data['promoted_by'] ?? 'system');

            // bulk graduate support
            if (isset($data['students']) && is_array($data['students'])) {
                $students = (array)$data['students'];
                $result = $this->promotionService->bulkGraduate($students, $promotedBy, $remarks);
                $this->loggingService->logAudit('graduation', "Bulk graduation executed by {$promotedBy}", $promotedBy);
                $response->setContent((string)json_encode($result));
                return $response;
            }

            if (!$studentId) {
                $response->setStatusCode(400);
                $this->loggingService->logApiDebugError("Missing required field: student_no");
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Missing required field: student_no',
                ]));
                return $response;
            }

            // currentClassId is optional; the service will attempt to resolve it automatically
            $result = $this->promotionService->graduateStudent(
                $studentId,
                $currentClassId,
                $promotedBy,
                $remarks
            );

            $this->loggingService->logAudit('graduation', "Student {$studentId} graduated", $promotedBy);
            $response->setContent((string)json_encode($result));
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('promotion_error', "Graduation failed: " . $e->getMessage());
            $response->setStatusCode(400);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
            return $response;
        }
    }

    /**
     * Retrieves promotion history for a student.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function history(Request $request, Response $response): Response
    {
        try {
            $studentId = (string)($request->getPost('student_no') ?? '');
            if (!$studentId) {
                $response->setStatusCode(400);
                $this->loggingService->logAudit('promotion_error', "Missing student_no parameter for promotion history");
                $response->setContent((string)json_encode([
                    'success' => false,
                    'message' => 'Missing student_id parameter',
                ]));
                return $response;
            }

            $result = $this->promotionService->getPromotionHistory($studentId);
            $response->setContent((string)json_encode($result));
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('system_error', "Failed to fetch promotion history: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Failed to fetch promotion history: ' . $e->getMessage(),
            ]));
            return $response;
        }
    }

    /**
     * Retrieves all available classes for special promotion.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function availableClasses(Request $request, Response $response): Response
    {
        try {
            $result = $this->promotionService->getAvailableClasses();
            $response->setContent((string)json_encode($result));
            return $response;

        } catch (\Exception $e) {
            $this->loggingService->logAudit('system_error', "Failed to fetch classes: " . $e->getMessage());
            $response->setStatusCode(500);
            $response->setContent((string)json_encode([
                'success' => false,
                'message' => 'Failed to fetch classes: ' . $e->getMessage(),
            ]));
            return $response;
        }
    }
}
