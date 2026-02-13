<?php

namespace App\Services;

use App\Repositories\StudentPromotionRepository;
use App\Exceptions\AuthException;
use PDO;

/**
 * Service for managing student promotions, graduation, and class movement.
 */
class StudentPromotionService
{
    private StudentPromotionRepository $promotionRepo;
    private \App\Repositories\SubjectRepository $subjectRepo;
    private \App\Repositories\StudentScoreRepository $scoreRepo;

    /**
     * @param StudentPromotionRepository $promotionRepo
     * @param \App\Repositories\SubjectRepository $subjectRepo
     * @param \App\Repositories\StudentScoreRepository $scoreRepo
     */
    public function __construct(
        StudentPromotionRepository $promotionRepo,
        \App\Repositories\SubjectRepository $subjectRepo,
        \App\Repositories\StudentScoreRepository $scoreRepo
    ) {
        if (!isLoggedIn()) {
            throw AuthException::unauthorized();
        }
        $this->promotionRepo = $promotionRepo;
        $this->subjectRepo = $subjectRepo;
        $this->scoreRepo = $scoreRepo;
    }

    /**
     * Promote a student to the next class (normal promotion: one level up only).
     *
     * @param string $studentIdentifier Student ID or number.
     * @param string|null $currentClassId Current class ID (optional).
     * @param string $promotedBy User ID performing the promotion.
     * @param string|null $remarks Optional remarks.
     * @return array The result of the promotion.
     * @throws \Exception If student not found or already promoted.
     */
    public function promoteStudentNormal(string $studentIdentifier, ?string $currentClassId, string $promotedBy, ?string $remarks = null): array
    {
        // Resolve student_no (accept id or student_no)
        $studentNo = $this->promotionRepo->resolveStudentNo($studentIdentifier);

        
        if (!$studentNo) {
            throw new \Exception('Student not found');
        }

        // Check if student has already been promoted this academic year
        $existingPromotion = $this->promotionRepo->hasBeenPromotedThisAcademicYear($studentNo);
        if ($existingPromotion) {
            throw new \Exception('Student already promoted for this term. Use assign class if you still want to change student class');
        }

        // If current class id/code not provided, resolve from promotions/admission
        if (empty($currentClassId)) {
            $current = $this->promotionRepo->getStudentCurrentClass($studentNo);
            
            if (!$current || empty($current['class_assigned'])) {
                throw new \Exception('Current class for student could not be determined');
            }
            $currentClassId = (string)$current['class_assigned'];
        }

        // CRITERIA CHECK
        $criteriaCheck = $this->checkPromotionCriteria($studentNo, $currentClassId);
        if (!$criteriaCheck['passed']) {
            throw new \Exception("Promotion criteria not met: " . $criteriaCheck['reason']);
        }

        // Get next class
        $nextClass = $this->promotionRepo->getNextClass($currentClassId);
        if (!$nextClass) {
            throw new \Exception('Student is already at the highest class level (graduation only)');
        }

        // Record promotion
        $this->promotionRepo->recordPromotion(
            (string)$studentNo,
            (int)$nextClass['id'],
            'normal',
            $remarks,
            $promotedBy
        );

        return [
            'success' => true,
            'message' => 'Student promoted successfully',
            'data' => [
                'student_no' => $studentNo,
                'from_class' => $currentClassId,
                'to_class' => $nextClass['id'],
                'to_class_name' => $nextClass['class_name'],
                'type' => 'normal',
            ]
        ];
    }

    /**
     * Bulk promote students (normal).
     *
     * @param array $students Array of student identifiers (id or student_no).
     * @param string|null $currentClassId Current class ID (optional).
     * @param string|null $promotedBy User ID performing the promotion.
     * @param string|null $remarks Optional remarks.
     * @return array Summary of the operation.
     */
    public function bulkPromoteNormal(array $students, ?string $currentClassId = null, ?string $promotedBy = null, ?string $remarks = null): array
    {
        $entries = [];

        foreach ($students as $studentIdentifier) {
            $studentNo = $this->promotionRepo->resolveStudentNo($studentIdentifier);
            
            if (!$studentNo) {
                $entries[] = [
                    'student_identifier' => $studentIdentifier,
                    'error' => 'student_not_found'
                ];
                continue;
            }

            // Check if student has already been promoted this academic year
            $existingPromotion = $this->promotionRepo->hasBeenPromotedThisAcademicYear($studentNo);
            if ($existingPromotion) {
                $entries[] = [
                    'student_identifier' => $studentNo,
                    'error' => 'already_promoted_this_term'
                ];
                continue;
            }

            // Determine current class for this student if not provided
            $currentForStudent = $currentClassId;

            
            if (empty($currentForStudent)) {
                $current = $this->promotionRepo->getStudentCurrentClass($studentNo);
                if (!$current || empty($current['class_assigned'])) {
                    $entries[] = [
                        'student_identifier' => $studentNo,
                        'error' => 'current_class_not_found'
                    ];
                    continue;
                }
                $currentForStudent = (string)$current['class_assigned'];
            }

            // CRITERIA CHECK
            $criteriaCheck = $this->checkPromotionCriteria($studentNo, $currentForStudent);
            if (!$criteriaCheck['passed']) {
                $entries[] = [
                    'student_identifier' => $studentNo,
                    'error' => 'criteria_failed',
                    'message' => $criteriaCheck['reason']
                ];
                continue;
            }

            $nextClass = $this->promotionRepo->getNextClass($currentForStudent);
            if (!$nextClass) {
                $entries[] = [
                    'student_identifier' => $studentNo,
                    'error' => 'no_next_class'
                ];
                continue;
            }

            $entries[] = [
                'student_identifier' => $studentNo,
                'from_class_id' => $currentForStudent,
                'to_class_id' => $nextClass['class_id'],
                'to_class_numeric_id' => $nextClass['id'],
                'promotion_type' => 'normal',
                'remarks' => $remarks,
                'promoted_by' => $promotedBy,
            ];
        }

        // Filter entries with errors and prepare valid entries
        $valid = array_filter($entries, function ($e) { return empty($e['error']); });
        $result = $this->promotionRepo->bulkRecordPromotions(array_values($valid));
        $skipped = array_values(array_filter($entries, function ($e) { return !empty($e['error']); }));

        $errorMessages = [
            'student_not_found' => 'Student not found',
            'already_promoted_this_term' => "Student already promoted for this term. Use assign class if you still want to change student class.\n",
            'current_class_not_found' => 'Current class not found for student',
            'no_next_class' => 'Student is already at the highest class level',
            'criteria_failed' => 'Promotion criteria not met',
        ];

        $skippedWithMessages = array_map(function ($entry) use ($errorMessages) {
            return [
                'student_identifier' => $entry['student_identifier'],
                'error' => $entry['error'],
                'message' => $entry['message'] ?? ($errorMessages[$entry['error']] ?? 'Unknown error')
            ];
        }, $skipped);


        if (count($skipped) > 0) {
            return [
                'success' => false,
                'message' => $skippedWithMessages,
                'data' => $skipped
            ];
        }

        //echo json_encode($skipped);exit;

        return [
            'success' => true,
            'summary' => $result,
            'skipped' => $skippedWithMessages,
        ];
    }

    /**
     * Checks if a student meets the promotion criteria for their current level.
     *
     * @param string $studentNo
     * @param string $classId
     * @return array {passed: bool, reason: ?string}
     */
    private function checkPromotionCriteria(string $studentNo, string $classId): array
    {
        // 1. Fetch Class details to get the level_id (level_code)
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT level_id FROM classes WHERE class_id = ? LIMIT 1");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        $levelCode = $class['level_id'] ?? $classId; // Fallback to classId for backward compatibility if null

        // 2. Fetch Criteria for this level
        $stmt = $db->prepare("SELECT * FROM promotion_criteria WHERE level_id = ? LIMIT 1");
        $stmt->execute([$levelCode]);
        $criteria = $stmt->fetch(PDO::FETCH_ASSOC);

        // If no criteria defined, assuming allowed
        if (!$criteria) {
            return ['passed' => true, 'reason' => null];
        }

        // 3. Fetch Student Scores for the current active Academic Year
        $stmtIdx = $db->query("SELECT id, academic_year FROM academic_years WHERE status = 'active' LIMIT 1");
        $activeYear = $stmtIdx->fetch(PDO::FETCH_ASSOC);
        
        if (!$activeYear) {
            // But usually there should be one. If not, fail safe?
            return ['passed' => true, 'reason' => 'No active academic year found to check scores'];
        }

        $scores = $this->scoreRepo->getStudentScores($studentNo, $activeYear['academic_year']);
        
        if (empty($scores)) {
             // No scores recorded -> Fail? Or Pass? 
             // Logic: If criteria requires min_score, and no scores -> Fail.
             return ['passed' => false, 'reason' => 'No scores recorded for this academic year'];
        }



        // 3. Calculate Average Score
        $totalScore = 0;
        $count = count($scores);
        foreach ($scores as $s) {
            $totalScore += (float)$s['score'];
        }
        $average = $count > 0 ? $totalScore / $count : 0;

        if ($average < (float)$criteria['min_score']) {
            return [
                'passed' => false, 
                'reason' => "Average score ({$average}) is below minimum required ({$criteria['min_score']})"
            ];
        }

        // 4. Check Electives
        // We need to know which subjects are 'core' and which are 'elective'.
        // The `scores` result has `subject_id`. fetching subject details from repo.
        // Optimized: fetching all subjects involved in scores or bulk fetch. 
        // `scoreRepo->getStudentScores` joins `subjects` table, but returns subject_name etc.
        // It does NOT seem to return `category`. I need to check `getStudentScores` in repo.
        // If it doesn't return category, I need to fetch it.
        // Or I can fetch all subject IDs and query SubjectRepository.

        $passedElectives = 0;
        $minPassMark = (float)$criteria['min_pass_mark'];

        foreach ($scores as $s) {
            $scoreVal = (float)$s['score'];
            
            // We need category. Let's fetch subject details if not in $s
            // $s comes from getStudentScores which usually joins subjects on id
            // Let's assume we need to fetch category if not present.
            // Checking getStudentScores SQL... `SELECT r.*, s.subject_id as subject_code, s.subject_name ...`
            // It does not select `s.category`.
            
            $subject = $this->subjectRepo->getById((int)$s['subject_id']);
            if ($subject && strtolower($subject['category']) === 'elective') {
                if ($scoreVal >= $minPassMark) {
                    $passedElectives++;
                }
            }
        }

        if ($passedElectives < (int)$criteria['min_electives']) {
            return [
                'passed' => false,
                'reason' => "Passed electives count ({$passedElectives}) is below minimum ({$criteria['min_electives']})"
            ];
        }

        return ['passed' => true, 'reason' => null];
    }


    /**
     * Bulk special promotion: students array and target class id.
     *
     * @param array $students Array of student identifiers.
     * @param mixed $targetClassId The target class ID or code.
     * @param string $promotedBy User ID performing the promotion.
     * @param string $remarks Reason for special promotion.
     * @return array Summary of the operation.
     * @throws \Exception If reason missing or class not found.
     */
    public function bulkPromoteSpecial(array $students, mixed $targetClassId, string $promotedBy, string $remarks): array
    {
        if (empty($remarks)) {
            throw new \Exception('Reason for special promotion is required');
        }

        $targetClass = null;
        
        if (is_numeric($targetClassId) && (int)$targetClassId > 0) {
            $stmt = $this->promotionRepo->getDb()->prepare("SELECT id, class_id FROM classes WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => (int)$targetClassId]);
            $targetClass = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $targetClass = (array)$this->promotionRepo->getClassById((string)$targetClassId);
        }
        
        if (!$targetClass) {
            throw new \Exception('Target class not found');
        }

        $toClassNumericId = (int)$targetClass['id'];
        $toClassStringId = (string)$targetClass['class_id'];

        $entries = [];
        foreach ($students as $studentIdentifier) {
            $studentNo = $this->promotionRepo->resolveStudentNo($studentIdentifier);
            if (!$studentNo) {
                $entries[] = [
                    'student_identifier' => $studentIdentifier,
                    'error' => 'student_not_found'
                ];
                continue;
            }

            // Check if student has already been promoted this academic year
            $existingPromotion = $this->promotionRepo->hasBeenPromotedThisAcademicYear($studentNo);
            if ($existingPromotion) {
                $entries[] = [
                    'student_identifier' => $studentNo,
                    'error' => 'already_promoted_this_term'
                ];
                continue;
            }

            $current = $this->promotionRepo->getStudentCurrentClass($studentNo);
            if (!$current) {
                $entries[] = [
                    'student_identifier' => $studentIdentifier,
                    'error' => 'current_class_not_found'
                ];
                continue;
            }

            $entries[] = [
                'student_identifier' => $studentNo,
                'from_class_id' => $current['class_assigned'] ?? null,
                'to_class_id' => $toClassStringId,
                'to_class_numeric_id' => $toClassNumericId,
                'promotion_type' => 'special',
                'remarks' => $remarks,
                'promoted_by' => $promotedBy,
            ];
        }

        $valid = array_filter($entries, function ($e) { return empty($e['error']); });
        $result = $this->promotionRepo->bulkRecordPromotions(array_values($valid));
        $skipped = array_values(array_filter($entries, function ($e) { return !empty($e['error']); }));

        $errorMessages = [
            'student_not_found' => 'Student not found',
            'already_promoted_this_term' => 'Student already promoted for this term. Use assign class if you still want to change student class',
            'current_class_not_found' => 'Current class not found for student',
        ];

        $skippedWithMessages = array_map(function ($entry) use ($errorMessages) {
            return [
                'student_identifier' => $entry['student_identifier'],
                'error' => $entry['error'],
                'message' => $errorMessages[$entry['error']] ?? 'Unknown error'
            ];
        }, $skipped);

        return [
            'success' => true,
            'summary' => $result,
            'skipped' => $skippedWithMessages,
        ];
    }

    /**
     * Bulk graduate students.
     *
     * @param array $students Array of student identifiers.
     * @param string $promotedBy User ID performing the graduation.
     * @param string|null $remarks Optional remarks.
     * @return array Summary of the operation.
     */
    public function bulkGraduate(array $students, string $promotedBy, ?string $remarks = null): array
    {
        $results = ['total' => count($students), 'success' => 0, 'failed' => 0, 'errors' => []];
        foreach ($students as $studentIdentifier) {
            try {
                $studentNo = $this->promotionRepo->resolveStudentNo($studentIdentifier);
                if (!$studentNo) {
                    throw new \Exception('Student not found: ' . json_encode($studentIdentifier));
                }

                // Check if student has already graduated this academic year
                $existingGraduation = $this->promotionRepo->hasBeenPromotedThisAcademicYear($studentNo);
                if ($existingGraduation && $existingGraduation['promotion_type'] === 'graduation') {
                    throw new \Exception('Student already graduated: ' . $studentNo);
                }

                $current = $this->promotionRepo->getStudentCurrentClass($studentNo);
                $fromClass = (string)($current['class_assigned'] ?? '');
                if (!$fromClass) {
                    throw new \Exception('Current class not found for student: ' . $studentNo);
                }

                // Validate that student is in JHS3 (final class)
                if (!$this->promotionRepo->isGraduationClass($fromClass)) {
                    throw new \Exception('Student not due for graduation: ' . $studentNo);
                }

                $this->promotionRepo->recordPromotion(
                    (string)$studentNo,
                    $fromClass,
                    'graduation',
                    $remarks ?? 'Graduated',
                    $promotedBy
                );

                // Update admission_status to Graduated
                $this->promotionRepo->updateAdmissionStatusToGraduated((string)$studentNo);

                $results['success']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
            }
        }

        return ['success' => true, 'summary' => $results];
    }

    /**
     * Promote a student to any class (special promotion).
     *
     * @param string|int $studentId Student ID or number.
     * @param string $currentClassId Current class ID.
     * @param string $targetClassId Target class ID.
     * @param string $promotedBy User ID performing the promotion.
     * @param string $remarks Reason for special promotion.
     * @return array The result of the promotion.
     * @throws \Exception If validation fails.
     */
    public function promoteStudentSpecial($studentId, string $currentClassId, string $targetClassId, string $promotedBy, string $remarks): array
    {
        if (empty($remarks)) {
            throw new \Exception('Reason for special promotion is required');
        }

        // Resolve student_no to check duplicate promotion
        $studentNo = $this->promotionRepo->resolveStudentNo($studentId);
        if (!$studentNo) {
            throw new \Exception('Student not found');
        }

        // Check if student has already been promoted this academic year
        $existingPromotion = $this->promotionRepo->hasBeenPromotedThisAcademicYear($studentNo);
        if ($existingPromotion) {
            throw new \Exception('Student already promoted for this term. Use assign class if you still want to change student class');
        }

        if ($currentClassId === $targetClassId) {
            throw new \Exception('Target class must be different from current class');
        }

        $targetClass = (array)$this->promotionRepo->getClassById($targetClassId);
        if (!$targetClass) {
            throw new \Exception('Target class not found');
        }

        // Record promotion
        $this->promotionRepo->recordPromotion(
            (string)$studentId,
            $targetClassId,
            'special',
            $remarks,
            $promotedBy
        );

        return [
            'success' => true,
            'message' => 'Student moved to target class successfully (special promotion)',
            'data' => [
                'student_id' => $studentId,
                'from_class' => $currentClassId,
                'to_class' => $targetClassId,
                'to_class_name' => $targetClass['class_name'] ?? 'Unknown',
                'type' => 'special',
            ]
        ];
    }

    /**
     * Graduate a student (move to graduation status).
     *
     * @param string|int $studentId Student ID or number.
     * @param string|null $currentClassId Current class ID (optional).
     * @param string $promotedBy User ID performing the graduation.
     * @param string|null $remarks Optional remarks.
     * @return array The result of the graduation.
     * @throws \Exception If student not found or not due for graduation.
     */
    public function graduateStudent($studentId, ?string $currentClassId, string $promotedBy, ?string $remarks = null): array
    {
        // Resolve student_no (accept id or student_no)
        $studentNo = $this->promotionRepo->resolveStudentNo($studentId);
        
        if (!$studentNo) {
            throw new \Exception('Student not found');
        }

        // Check if student has already graduated this academic year
        $existingGraduation = $this->promotionRepo->hasBeenPromotedThisAcademicYear($studentNo);
        if ($existingGraduation && $existingGraduation['promotion_type'] === 'graduation') {
            throw new \Exception('Student already graduated. Use assign class if you still want to change student class');
        }

        // If current class id/code not provided, resolve from promotions/admission
        if (empty($currentClassId)) {
            $current = $this->promotionRepo->getStudentCurrentClass($studentNo);
            
            if (!$current || empty($current['class_assigned'])) {
                throw new \Exception('Current class for student could not be determined');
            }
            $currentClassId = (string)$current['class_assigned'];
        }

        // Validate that student is in JHS3 (final class)
        if (!$this->promotionRepo->isGraduationClass($currentClassId)) {
            throw new \Exception('Student not due for graduation');
        }

        // For graduation, we can record a promotion to a null/special class or just record the graduation event
        // For now, recording with to_class_id = currentClassId and type = 'graduation'
        $this->promotionRepo->recordPromotion(
            (string)$studentNo,
            $currentClassId,
            'graduation',
            $remarks ?? 'Student graduated',
            $promotedBy
        );

        // Update admission_status to Graduated
        $this->promotionRepo->updateAdmissionStatusToGraduated((string)$studentNo);

        return [
            'success' => true,
            'message' => 'Student graduated successfully',
            'data' => [
                'student_id' => $studentNo,
                'from_class' => $currentClassId,
                'status' => 'graduated',
            ]
        ];
    }

    /**
     * Get promotion history for a student.
     *
     * @param string $studentId The student ID or number.
     * @return array The promotion history.
     */
    public function getPromotionHistory(string $studentId): array
    {
        $history = $this->promotionRepo->getPromotionHistory($studentId);
        return [
            'success' => true,
            'data' => $history,
        ];
    }

    /**
     * Get all available classes for special promotion.
     *
     * @return array The list of available classes.
     */
    public function getAvailableClasses(): array
    {
        $classes = $this->promotionRepo->getAllClasses();
        return [
            'success' => true,
            'data' => $classes,
        ];
    }
}
