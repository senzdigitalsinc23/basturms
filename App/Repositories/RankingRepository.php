<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Repository for querying ranking data from student_report and student_term_rankings.
 */
class RankingRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get per-subject rankings for a class in a given term.
     * Returns each student's score and subject_position per subject.
     *
     * @param int $classId
     * @param string $academicYear
     * @param string $term
     * @param int|null $subjectId Optional – filter to single subject
     * @return array
     */
    public function getSubjectRankings(int $classId, string $academicYear, string $term, ?int $subjectId = null): array
    {
        $sql = "
            SELECT
                sr.id,
                sr.student_no,
                sr.subject_id,
                sr.class_id,
                sr.academic_year,
                sr.term,
                sr.`total_score_100%`  AS total_score,
                sr.sba_score,
                sr.exam_score,
                sr.grade,
                sr.remarks,
                sr.subject_position,
                s.first_name,
                s.other_name,
                s.last_name,
                subj.subject_name,
                subj.subject_code,
                c.class_name
            FROM student_report sr
            JOIN students s   ON sr.student_no  = s.student_no
            JOIN subjects subj ON sr.subject_id = subj.id
            JOIN classes  c   ON sr.class_id    = c.id
            WHERE sr.class_id      = :class_id
              AND sr.academic_year = :academic_year
              AND sr.term          = :term
        ";

        $params = [
            ':class_id'      => $classId,
            ':academic_year' => $academicYear,
            ':term'          => $term,
        ];

        if ($subjectId) {
            $sql .= " AND sr.subject_id = :subject_id";
            $params[':subject_id'] = $subjectId;
        }

        $sql .= " ORDER BY subj.subject_name ASC, sr.subject_position ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get overall class rankings for a term (from student_term_rankings).
     *
     * @param int $classId
     * @param string $academicYear
     * @param string $term
     * @return array
     */
    public function getClassRankings(int $classId, string $academicYear, string $term): array
    {
        $sql = "
            SELECT
                str.id,
                str.student_no,
                str.class_id,
                str.academic_year,
                str.term,
                str.total_score_sum,
                str.average_score,
                str.subjects_count,
                str.class_position,
                s.first_name,
                s.other_name,
                s.last_name,
                c.class_name
            FROM student_term_rankings str
            JOIN students s ON str.student_no = s.student_no
            JOIN classes  c ON str.class_id   = c.id
            WHERE str.class_id      = :class_id
              AND str.academic_year = :academic_year
              AND str.term          = :term
            ORDER BY str.class_position ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id'      => $classId,
            ':academic_year' => $academicYear,
            ':term'          => $term,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single student's ranking summary for a term.
     *
     * @param string $studentNo
     * @param string $academicYear
     * @param string $term
     * @return array|null
     */
    public function getStudentTermRanking(string $studentNo, string $academicYear, string $term): ?array
    {
        // Overall (term) ranking
        $sql = "
            SELECT
                str.*,
                s.first_name,
                s.other_name,
                s.last_name,
                c.class_name
            FROM student_term_rankings str
            JOIN students s ON str.student_no = s.student_no
            JOIN classes  c ON str.class_id   = c.id
            WHERE str.student_no   = :student_no
              AND str.academic_year = :academic_year
              AND str.term          = :term
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_no'    => $studentNo,
            ':academic_year' => $academicYear,
            ':term'          => $term,
        ]);
        $termRanking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$termRanking) {
            return null;
        }

        // Per-subject positions
        $subSql = "
            SELECT
                sr.subject_id,
                sr.`total_score_100%`  AS total_score,
                sr.sba_score,
                sr.exam_score,
                sr.grade,
                sr.remarks,
                sr.subject_position,
                subj.subject_name,
                subj.subject_code
            FROM student_report sr
            JOIN subjects subj ON sr.subject_id = subj.id
            WHERE sr.student_no   = :student_no
              AND sr.academic_year = :academic_year
              AND sr.term          = :term
            ORDER BY subj.subject_name ASC
        ";

        $subStmt = $this->db->prepare($subSql);
        $subStmt->execute([
            ':student_no'    => $studentNo,
            ':academic_year' => $academicYear,
            ':term'          => $term,
        ]);

        $termRanking['subject_rankings'] = $subStmt->fetchAll(PDO::FETCH_ASSOC);

        return $termRanking;
    }

    /**
     * Get rankings for all students in a specific school level.
     *
     * @param string $levelId
     * @param string $academicYear
     * @param string $term
     * @return array
     */
    public function getLevelRankings(string $levelId, string $academicYear, string $term): array
    {
        $sql = "
            SELECT
                str.id,
                str.student_no,
                str.class_id,
                str.academic_year,
                str.term,
                str.total_score_sum,
                str.average_score,
                str.subjects_count,
                s.first_name,
                s.other_name,
                s.last_name,
                c.class_name,
                c.level_id
            FROM student_term_rankings str
            JOIN students s ON str.student_no = s.student_no
            JOIN classes  c ON str.class_id   = c.id
            WHERE c.level_id      = :level_id
              AND str.academic_year = :academic_year
              AND str.term          = :term
            ORDER BY str.average_score DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':level_id'      => $levelId,
            ':academic_year' => $academicYear,
            ':term'          => $term,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate rank based on average_score
        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }

        return $rows;
    }

    /**
     * Get rankings for all students in the entire school.
     *
     * @param string $academicYear
     * @param string $term
     * @return array
     */
    public function getSchoolRankings(string $academicYear, string $term): array
    {
        $sql = "
            SELECT
                str.id,
                str.student_no,
                str.class_id,
                str.academic_year,
                str.term,
                str.total_score_sum,
                str.average_score,
                str.subjects_count,
                s.first_name,
                s.other_name,
                s.last_name,
                c.class_name
            FROM student_term_rankings str
            JOIN students s ON str.student_no = s.student_no
            JOIN classes  c ON str.class_id   = c.id
            WHERE str.academic_year = :academic_year
              AND str.term          = :term
            ORDER BY str.average_score DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':academic_year' => $academicYear,
            ':term'          => $term,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate rank based on average_score
        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }

        return $rows;
    }
}
