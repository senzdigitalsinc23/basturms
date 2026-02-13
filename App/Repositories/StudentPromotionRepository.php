<?php

namespace App\Repositories;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class StudentPromotionRepository
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger(__DIR__ . '/../../storage/logs');
    }

    /**
     * Get the database connection (for internal use)
     */
    public function getDb(): PDO
    {
        return $this->db;
    }

    /**
     * Resolve a student identifier (id or student_no) to student_no (string)
     */
    public function resolveStudentNo(mixed $studentIdentifier): ?string
    {
        //echo json_encode(is_array($studentIdentifier));exit;
        $studentIdentifier = is_array($studentIdentifier) ? (trim((string)($studentIdentifier)['student_no']) ?? (string)$studentIdentifier) : trim((string)$studentIdentifier);
        //echo json_encode($studentIdentifier);exit;
        if (is_int($studentIdentifier) || ctype_digit((string)$studentIdentifier)) {
            $sql = "SELECT student_no FROM students WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => (int)$studentIdentifier]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['student_no'] : null;
        }

        // assume identifier is student_no; verify it exists
        $sql = "SELECT student_no FROM students WHERE student_no = :student_no LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentIdentifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['student_no'] : null;
    }

    /**
     * Get the next class level for normal promotion (one level up)
     */
    public function getNextClass(string $currentClassId): ?array
    {
        $sql = "
            SELECT c.id, c.class_id, c.class_name
            FROM classes c
            WHERE c.id > (SELECT id FROM classes WHERE class_id = :current_id)
            ORDER BY c.id ASC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['current_id' => $currentClassId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get class by ID
     */
    public function getClassById(string $classId): ?array
    {
        $sql = "SELECT id, class_id, class_name FROM classes WHERE class_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $classId]);

        //echo json_encode($classId);exit;
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Record a student promotion
     */
    public function recordPromotion(
        string $studentIdentifier,
        string $toClassId,
        string $promotionType,
        ?string $remarks,
        string $promotedBy
    ): bool {
        // Resolve student_no (accept id or student_no)
        $studentNo = $this->resolveStudentNo($studentIdentifier);
        if (!$studentNo) {
            throw new \Exception('Student not found');
        }

        // Automatically get from_class_id from student's current class
        $current = $this->getStudentCurrentClass($studentNo);
        if (!$current || empty($current['class_assigned'])) {
            throw new \Exception('Current class for student could not be determined');
        }
        $fromClassId = $current['class_assigned'];

        // Get numeric ID of the target class (handle both string class_id and numeric id)
        $targetClass = null;
        if (is_numeric($toClassId)) {
            // Already numeric ID, fetch the class to verify it exists
            $stmt = $this->db->prepare("SELECT id, class_id FROM classes WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => (int)$toClassId]);
            $targetClass = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // String class_id, fetch to get numeric ID
            $targetClass = $this->getClassById($toClassId);
        }

        if (!$targetClass) {
            throw new \Exception('Target class not found');
        }

        $toClassNumericId = (int)$targetClass['id'];
        $toClassStringId = $targetClass['class_id'];

        $sql = "
            INSERT INTO student_promotions (student_no, from_class_id, to_class_id, promotion_type, remarks, promoted_by)
            VALUES (:student_no, :from_class_id, :to_class_id, :promotion_type, :remarks, :promoted_by)
        ";

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                'student_no' => $studentNo,
                'from_class_id' => $fromClassId,
                'to_class_id' => $toClassStringId,
                'promotion_type' => $promotionType,
                'remarks' => $remarks,
                'promoted_by' => $promotedBy,
            ]);

            if ($ok) {
                // Also update admission_details so student's current class reflects the promotion
                try {
                    $this->updateAdmissionClass($studentNo, $toClassNumericId);

                    $this->db->commit();
                } catch (\Throwable $e) {
                    $this->logger->dbError("Failed to update admission_details for student {$studentNo}: " . $e->getMessage());
                    $this->db->rollback();
                    // Log or ignore; do not break promotion on admission_details update failure
                }
            }

            return $ok;
        } catch (\Throwable $e) {
            $this->logger->dbError("Database error during promotion recording: " . $e->getMessage(), ['student_no' => $studentNo]);
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update admission_details for the given student to set the new class.
     * Supports both `class_assigned` (varchar storing class_id string) and `class_id` numeric column.
     */
    public function updateAdmissionClass(string $studentNo, int $toClassId): bool
    {
        // studentNo is already provided

        // Get classes.class_id (string) for class_assigned column
        $stmt = $this->db->prepare("SELECT class_id FROM classes WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $toClassId]);
        $classRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $classCode = $classRow['class_id'] ?? null;

        // Determine which columns exist in admission_details
        $colCheck = $this->db->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admission_details' AND COLUMN_NAME IN ('class_assigned','class_id')"
        );
        $colCheck->execute();
        $cols = $colCheck->fetchAll(PDO::FETCH_COLUMN);

        $updated = false;
        if (in_array('class_assigned', $cols) && $classCode !== null) {
            $u = $this->db->prepare("UPDATE admission_details SET class_assigned = :class_assigned WHERE student_no = :student_no");
            $updated = $u->execute(['class_assigned' => $classCode, 'student_no' => $studentNo]) || $updated;
        }

        if (in_array('class_id', $cols)) {
            $u2 = $this->db->prepare("UPDATE admission_details SET class_id = :class_id WHERE student_no = :student_no");
            $updated = $u2->execute(['class_id' => $toClassId, 'student_no' => $studentNo]) || $updated;
        }

        return $updated;
    }

    /**
     * Bulk record promotions in a single transaction.
     * Each entry should be an assoc array containing: student_identifier (id or student_no), from_class_id, to_class_id, promotion_type, remarks, promoted_by
     */
    public function bulkRecordPromotions(array $entries): array
    {
        $results = [
            'total' => count($entries),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            $this->db->beginTransaction();
            $sql = "
                INSERT INTO student_promotions (student_no, from_class_id, to_class_id, promotion_type, remarks, promoted_by)
                VALUES (:student_no, :from_class_id, :to_class_id, :promotion_type, :remarks, :promoted_by)
            ";
            $stmt = $this->db->prepare($sql);

            foreach ($entries as $i => $entry) {
                try {
                    $studentIdentifier = $entry['student_identifier'] ?? ($entry['student_id'] ?? ($entry['student_no'] ?? null));
                    $resolvedStudentNo = $this->resolveStudentNo($studentIdentifier);
                    if (!$resolvedStudentNo) {
                        throw new \Exception('Student not found: ' . json_encode($studentIdentifier));
                    }

                    $ok = $stmt->execute([
                        'student_no' => $resolvedStudentNo,
                        'from_class_id' => $entry['from_class_id'],
                        'to_class_id' => $entry['to_class_id'],
                        'promotion_type' => $entry['promotion_type'] ?? 'special',
                        'remarks' => $entry['remarks'] ?? null,
                        'promoted_by' => $entry['promoted_by'] ?? 'system',
                    ]);

                    if ($ok) {
                        // update admission details, ignore errors
                        try {
                            $toClassNumericId = $entry['to_class_numeric_id'] ?? null;
                            if (!$toClassNumericId && is_numeric($entry['to_class_id'])) {
                                $toClassNumericId = (int)$entry['to_class_id'];
                            } elseif (!$toClassNumericId) {
                                $classRow = $this->db->prepare("SELECT id FROM classes WHERE class_id = :class_id LIMIT 1");
                                $classRow->execute(['class_id' => $entry['to_class_id']]);
                                $row = $classRow->fetch(PDO::FETCH_ASSOC);
                                $toClassNumericId = $row ? (int)$row['id'] : null;
                            }
                            
                            if ($toClassNumericId) {
                                $this->updateAdmissionClass($resolvedStudentNo, $toClassNumericId);
                            }
                        } catch (\Throwable $e) {
                            $this->logger->dbError("Failed to update admission_details for student {$resolvedStudentNo}: " . $e->getMessage());
                        }
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Failed to insert for student: " . json_encode($studentIdentifier);
                    }
                } catch (\Throwable $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->logger->dbError("Bulk promotion transaction failed: " . $e->getMessage());
            $this->db->rollBack();
            $results['failed'] = $results['total'];
            $results['success'] = 0;
            $results['errors'][] = 'Transaction failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get promotion history for a student
     */
    public function getPromotionHistory(string $studentId): array
    {
        $sql = "
            SELECT sp.id, sp.student_no, sp.from_class_id, sp.to_class_id,
                   sp.promotion_type, sp.remarks, sp.promoted_by, sp.promoted_on,
                   fc.class_name AS from_class_name, tc.class_name AS to_class_name
            FROM student_promotions sp
            LEFT JOIN classes fc ON sp.from_class_id = fc.id
            LEFT JOIN classes tc ON sp.to_class_id = tc.id
            WHERE sp.student_no = :student_no
            ORDER BY sp.promoted_on DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the current class of a student (last promotion or initial)
     */
    public function getStudentCurrentClass(string $studentNo): ?array
    {
        $sql = "
            SELECT class_assigned FROM admission_details
            WHERE student_no = :student_no
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        //echo json_encode($result);exit;
        return $result ?: null;
    }

    /**
     * Get all classes (for listing available destination classes)
     */
    public function getAllClasses(): array
    {
        $sql = "SELECT id, class_id, class_name FROM classes ORDER BY id ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a class is the final graduation class (JHS3)
     */
    public function isGraduationClass(string $classId): bool
    {
        $sql = "SELECT class_id FROM classes WHERE class_id = :class_id AND class_name = 'Junior High School 3'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['class_id' => $classId]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update admission_status to Graduated for a student
     */
    public function updateAdmissionStatusToGraduated(string $studentNo): bool
    {
        $sql = "UPDATE admission_details SET admission_status = :status WHERE student_no = :student_no";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => 'Graduated',
            'student_no' => $studentNo
        ]);
    }

    /**
     * Check if a student has been promoted or graduated in the current academic year
     * Academic year: Sep-Aug (if Sep-Dec, academic year is current year; if Jan-Aug, academic year is previous year)
     */
    public function hasBeenPromotedThisAcademicYear(string $studentNo, ?string $excludePromotionType = null): ?array
    {
        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');

        // Determine academic year start and end
        if ($currentMonth >= 9) {
            // Sep-Dec: academic year is current year to next year
            $academicYearStart = "{$currentYear}-09-01";
            $academicYearEnd = date('Y-m-d', strtotime($currentYear . '-09-01 +1 year -1 day'));
        } else {
            // Jan-Aug: academic year is previous year to current year
            $previousYear = $currentYear - 1;
            $academicYearStart = "{$previousYear}-09-01";
            $academicYearEnd = date('Y-m-d', strtotime($previousYear . '-09-01 +1 year -1 day'));
        }

        $sql = "
            SELECT id, promotion_type, to_class_id, promoted_on
            FROM student_promotions
            WHERE student_no = :student_no
            AND (promotion_type = 'normal' OR promotion_type = 'special' OR promotion_type = 'graduation')
            AND DATE(promoted_on) >= :start_date
            AND DATE(promoted_on) <= :end_date
        ";

        // Exclude specific promotion type if needed
        if ($excludePromotionType) {
            $sql .= " AND promotion_type != :exclude_type";
        }

        $sql .= " ORDER BY promoted_on DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $params = [
            'student_no' => $studentNo,
            'start_date' => $academicYearStart,
            'end_date' => $academicYearEnd,
        ];

        if ($excludePromotionType) {
            $params['exclude_type'] = $excludePromotionType;
        }

        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
