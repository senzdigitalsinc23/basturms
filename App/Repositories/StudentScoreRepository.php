<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class StudentScoreRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function addScore(string $studentNo, int $subjectId, string $academicYear, string $term, int $classId, float $score, ?string $grade, ?string $remarks, string $enteredBy): array
    {
        $sql = "
            INSERT INTO scores (student_no, subject_id, academic_year, term, class_id, score, grade, remarks, entered_by, entered_on)
            VALUES (:student_no, :subject_id, :academic_year, :term, :class_id, :score, :grade, :remarks, :entered_by, NOW())
            ON DUPLICATE KEY UPDATE score = :u_score, grade = :u_grade, remarks = :u_remarks, entered_by = :u_entered_by, entered_on = NOW()
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_no' => $studentNo,
            ':subject_id' => $subjectId,
            ':academic_year' => $academicYear,
            ':term' => $term,
            ':class_id' => $classId,
            ':score' => $score,
            ':grade' => $grade,
            ':remarks' => $remarks,
            ':entered_by' => $enteredBy,
            ':u_score' => $score,
            ':u_grade' => $grade,
            ':u_remarks' => $remarks,
            ':u_entered_by' => $enteredBy,
        ]);

        return [
            'student_no' => $studentNo,
            'subject_id' => $subjectId,
            'score' => $score,
            'grade' => $grade,
        ];
    }

    public function getStudentScores(string $studentNo, ?string $academicYear = null, ?string $term = null): array
    {
        
        $sql = "
            SELECT r.id, r.student_no, r.subject_id, r.activity_id, r.academic_year, r.term, r.class_id, r.score,
                   s.subject_code AS subject_code, s.subject_name,
                   c.class_id AS class_code, c.class_name, a.activity_name
            FROM scores r
            JOIN subjects s ON r.subject_id = s.id
            JOIN classes c ON r.class_id = c.id
            JOIN activities a ON r.activity_id = a.id
            WHERE r.student_no = :student_no
        ";
        $params = [':student_no' => $studentNo];

        if ($academicYear) {
            $sql .= " AND r.academic_year = :academic_year";
            $params[':academic_year'] = $academicYear;
        }

        if ($term) {
            $sql .= " AND r.term = :term";
            $params[':term'] = $term;
        }

        $sql .= " ORDER BY r.academic_year DESC, r.term DESC, s.subject_name ASC";
 
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClassScores(int $classId, string $academicYear, string $term, ?int $subjectId = null): array
    {
        $sql = "
            SELECT r.id, r.student_no, r.subject_id, r.activity_id, r.academic_year, r.term, r.class_id, r.score,
                   s.subject_code AS subject_code, s.subject_name, aa.activity_name
            FROM scores r
            JOIN subjects s ON r.subject_id = s.id
            JOIN activities aa ON r.activity_id = aa.id
            WHERE r.class_id = :class_id AND r.academic_year = :academic_year AND r.term = :term
        ";
        $params = [':class_id' => $classId, ':academic_year' => $academicYear, ':term' => $term];

        if ($subjectId) {
            $sql .= " AND r.subject_id = :subject_id";
            $params[':subject_id'] = $subjectId;
        }

        $sql .= " ORDER BY r.student_no ASC, s.subject_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubjectScores(int $subjectId, string $academicYear, string $term, ?int $classId = null): array
    {
        $sql = "
            SELECT r.id, r.student_no, r.subject_id, r.academic_year, r.term, r.class_id, r.score, r.grade, r.remarks, r.entered_by, r.entered_on,
                   c.class_id AS class_code, c.class_name
            FROM scores r
            JOIN classes c ON r.class_id = c.id
            WHERE r.subject_id = :subject_id AND r.academic_year = :academic_year AND r.term = :term
        ";
        $params = [':subject_id' => $subjectId, ':academic_year' => $academicYear, ':term' => $term];

        if ($classId) {
            $sql .= " AND r.class_id = :class_id";
            $params[':class_id'] = $classId;
        }

        $sql .= " ORDER BY r.student_no ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function bulkAddScores(array $scores, string $enteredBy): array
    {
        $results = ['total' => count($scores), 'success' => 0, 'failed' => 0, 'errors' => []];

        try {
            $this->db->beginTransaction();

                $sql = "
                    INSERT INTO scores (student_no, subject_id, academic_year, term, class_id, score, grade, remarks, entered_by, entered_on)
                    VALUES (:student_no, :subject_id, :academic_year, :term, :class_id, :score, :grade, :remarks, :entered_by, NOW())
                    ON DUPLICATE KEY UPDATE score = :u_score, grade = :u_grade, remarks = :u_remarks, entered_by = :u_entered_by, entered_on = NOW()
                ";
                $stmt = $this->db->prepare($sql);

                foreach ($scores as $index => $score) {
                    try {
                        if (empty($score['student_no']) || empty($score['subject_id']) || empty($score['academic_year']) || empty($score['term']) || empty($score['class_id']) || !isset($score['score'])) {
                            throw new \Exception('Missing required fields');
                        }

                        $ok = $stmt->execute([
                            ':student_no' => $score['student_no'],
                            ':subject_id' => (int)$score['subject_id'],
                            ':academic_year' => $score['academic_year'],
                            ':term' => $score['term'],
                            ':class_id' => (int)$score['class_id'],
                            ':score' => (float)$score['score'],
                            ':grade' => $score['grade'] ?? null,
                            ':remarks' => $score['remarks'] ?? null,
                            ':entered_by' => $enteredBy,
                            ':u_score' => (float)$score['score'],
                            ':u_grade' => $score['grade'] ?? null,
                            ':u_remarks' => $score['remarks'] ?? null,
                            ':u_entered_by' => $enteredBy,
                        ]);

                    if ($ok) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Row " . ($index + 1) . ": Failed to insert score";
                    }
                } catch (\Throwable $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $results['failed'] = $results['total'];
            $results['success'] = 0;
            $results['errors'][] = 'Transaction failed: ' . $e->getMessage();
        }

        return $results;
    }

    public function deleteScore(int $id): bool
    {
        $sql = "DELETE FROM scores WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function exists(string $studentNo, int $subjectId, string $academicYear, string $term, int $classId): bool
    {
        $sql = "SELECT id FROM scores WHERE student_no = :student_no AND subject_id = :subject_id AND academic_year = :academic_year AND term = :term AND class_id = :class_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_no' => $studentNo,
            ':subject_id' => $subjectId,
            ':academic_year' => $academicYear,
            ':term' => $term,
            ':class_id' => $classId,
        ]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Add an activity score to the scores table.
     * 
     * @param string $studentNo
     * @param int $subjectId
     * @param int $activityId
     * @param int $classId
     * @param string $academicYear
     * @param string $term
     * @param float $score
     * @param string $enteredBy
     * @return array
     */
    public function addActivityScore(
        string $studentNo,
        int $subjectId,
        int $activityId,
        int $classId,
        string $academicYear,
        string $term,
        float $score,
        string $enteredBy
    ): array {
        //
        $sql = "
            INSERT INTO scores (student_no, subject_id, activity_id, class_id, academic_year, term, score, entered_by, entered_on)
            VALUES (:student_no, :subject_id, :activity_id, :class_id, :academic_year, :term, :score, :entered_by, NOW())
            ON DUPLICATE KEY UPDATE 
                score = :u_score, 
                entered_by = :u_entered_by, 
                entered_on = NOW()
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_no' => $studentNo,
            ':subject_id' => $subjectId > 0 ? $subjectId : null,
            ':activity_id' => $activityId,
            ':class_id' => $classId,
            ':academic_year' => $academicYear,
            ':term' => $term,
            ':score' => $score,
            ':entered_by' => $enteredBy,
            ':u_score' => $score,
            ':u_entered_by' => $enteredBy,
        ]);

        //echo json_encode('new');exit;
        return [
            'success' => true,
            'student_no' => $studentNo,
            'subject_id' => $subjectId,
            'activity_id' => $activityId,
            'score' => $score,
        ];
    }

    /**
     * Get activity scores for a student.
     * 
     * @param string $studentNo
     * @param int|null $subjectId
     * @param string|null $academicYear
     * @param string|null $term
     * @return array
     */
    public function getActivityScores(
        string $studentNo,
        ?int $subjectId = null,
        ?string $academicYear = null,
        ?string $term = null
    ): array {
        $sql = "
            SELECT sc.*, s.subject_name, s.subject_code, aa.act_name as activity_name
            FROM scores sc
            LEFT JOIN subjects s ON sc.subject_id = s.id
            JOIN assignment_activities aa ON sc.activity_id = aa.id
            WHERE sc.student_no = :student_no
        ";
        
        $params = [':student_no' => $studentNo];
        
        if ($subjectId) {
            $sql .= " AND sc.subject_id = :subject_id";
            $params[':subject_id'] = $subjectId;
        }
        
        if ($academicYear) {
            $sql .= " AND sc.academic_year = :academic_year";
            $params[':academic_year'] = $academicYear;
        }
        
        if ($term) {
            $sql .= " AND sc.term = :term";
            $params[':term'] = $term;
        }
        
        $sql .= " ORDER BY sc.entered_on DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get summary reports with optional filtering.
     * 
     * @param string|null $studentNo
     * @param string|null $academicYear
     * @param string|null $term
     * @return array
     */
    public function getSummaryReports(?string $studentNo = null, ?string $academicYear = null, ?string $term = null): array
    {
        $sql = "
            SELECT 
                ssr.id, ssr.student_no, ssr.subject_id, ssr.class_id, ssr.academic_year, ssr.term, ssr.assignment_activity_id, ssr.total_score, ssr.percentage_score,
                s.first_name, s.other_name, s.last_name,
                subj.subject_name, subj.subject_code,
                c.class_name,
                aa.act_name as assignment_activity_name
            FROM student_summary_report ssr
            LEFT JOIN students s ON ssr.student_no = s.student_no
            LEFT JOIN subjects subj ON ssr.subject_id = subj.id
            LEFT JOIN classes c ON ssr.class_id = c.id
            LEFT JOIN assignment_activities aa ON ssr.assignment_activity_id = aa.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($studentNo)) {
            $sql .= " AND ssr.student_no = :student_no";
            $params[':student_no'] = $studentNo;
        }
        
        if (!empty($academicYear)) {
            $sql .= " AND ssr.academic_year = :academic_year";
            $params[':academic_year'] = $academicYear;
        }
        
        if (!empty($term)) {
            $sql .= " AND ssr.term = :term";
            $params[':term'] = $term;
        }
        
        $sql .= " ORDER BY ssr.academic_year DESC, ssr.term DESC, s.last_name ASC, subj.subject_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get aggregated student reports with optional filtering.
     * 
     * @param string|null $studentNo
     * @param string|null $academicYear
     * @param string|null $term
     * @return array
     */
    public function getStudentReports(?string $studentNo = null, ?string $academicYear = null, ?string $term = null, ?int $classId = null): array
    {
        $sql = "
            SELECT 
                sr.*,
                s.first_name, s.other_name, s.last_name,
                subj.subject_name, subj.subject_code,
                c.class_name
            FROM student_report sr
            JOIN students s ON sr.student_no = s.student_no
            JOIN subjects subj ON sr.subject_id = subj.id
            JOIN classes c ON sr.class_id = c.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($studentNo)) {
            $sql .= " AND sr.student_no = :student_no";
            $params[':student_no'] = $studentNo;
        }
        
        if (!empty($academicYear)) {
            $sql .= " AND sr.academic_year = :academic_year";
            $params[':academic_year'] = $academicYear;
        }
        
        if (!empty($term)) {
            $sql .= " AND sr.term = :term";
            $params[':term'] = $term;
        }

        if (!empty($classId)) {
            $sql .= " AND sr.class_id = :class_id";
            $params[':class_id'] = $classId;
        }
        
        $sql .= " ORDER BY sr.academic_year DESC, sr.term DESC, s.last_name ASC, subj.subject_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
