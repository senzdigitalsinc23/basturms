<?php

namespace Jobs;

use App\Core\Database;
use PDO;

class GenerateStudentReportJob
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function handle(string $academicYear, string $term): void
    {
        echo "Starting student report generation for {$academicYear} - {$term}...\n";

        // Query to aggregate summary scores into report format
        // We separate SBA (non-exam) from Exam based on act_name
        $sql = "
            SELECT 
                ssr.student_no,
                ssr.subject_id,
                ssr.class_id,
                ssr.academic_year,
                ssr.term,
                SUM(CASE WHEN aa.act_name NOT LIKE '%Exam%' THEN ssr.total_score ELSE 0 END) as sba_raw_score,
                SUM(CASE WHEN aa.act_name NOT LIKE '%Exam%' THEN ssr.percentage_score ELSE 0 END) as sba_50,
                SUM(CASE WHEN aa.act_name LIKE '%Exam%' THEN ssr.total_score ELSE 0 END) as exam_raw_score,
                SUM(CASE WHEN aa.act_name LIKE '%Exam%' THEN ssr.percentage_score ELSE 0 END) as exam_50
            FROM student_summary_report ssr
            JOIN assignment_activities aa ON ssr.assignment_activity_id = aa.id
            WHERE ssr.academic_year = :academic_year AND ssr.term = :term
            GROUP BY ssr.student_no, ssr.subject_id, ssr.class_id, ssr.academic_year, ssr.term
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':academic_year' => $academicYear,
            ':term' => $term
        ]);
        
        $aggregates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($aggregates) . " student-subject combinations to process.\n";
        
        // Fetch grading scheme for later use
        $gsStmt = $this->db->query("SELECT * FROM grading_scheme ORDER BY grade_from DESC");
        $gradingScheme = $gsStmt->fetchAll(PDO::FETCH_ASSOC);

        $insertSql = "
            INSERT INTO student_report 
                (student_no, subject_id, class_id, academic_year, term, sba_raw_score, `sba_50%`, exam_raw_score, `exam_50%`, `total_score_100%`, grade, remarks, entered_by, entered_on)
            VALUES 
                (:student_no, :subject_id, :class_id, :academic_year, :term, :sba_raw_score, :sba_50, :exam_raw_score, :exam_50, :total_100, :grade, :remarks, :entered_by, NOW())
            ON DUPLICATE KEY UPDATE 
                sba_raw_score = :u_sba_raw_score,
                `sba_50%` = :u_sba_50,
                exam_raw_score = :u_exam_raw_score,
                `exam_50%` = :u_exam_50,
                `total_score_100%` = :u_total_100,
                grade = :u_grade,
                remarks = :u_remarks,
                entered_on = NOW()
        ";
        
        $insertStmt = $this->db->prepare($insertSql);
        
        $count = 0;
        foreach ($aggregates as $row) {
            $sba50 = (float)$row['sba_50'];
            $exam50 = (float)$row['exam_50'];

            $total100 = $sba50 + $exam50;
            $grade = '9';
            $remarks = 'N/A';

            foreach ($gradingScheme as $gs) {
                if ($total100 >= $gs['grade_from'] && $total100 <= $gs['grade_to']) {
                    $grade = $gs['grade'];
                    $remarks = $gs['remarks'];
                    break;
                }
            }

            $insertStmt->execute([
                ':student_no' => $row['student_no'],
                ':subject_id' => $row['subject_id'],
                ':class_id' => $row['class_id'],
                ':academic_year' => $row['academic_year'],
                ':term' => $row['term'],
                ':sba_raw_score' => $row['sba_raw_score'],
                ':sba_50' => $sba50,
                ':exam_raw_score' => $row['exam_raw_score'],
                ':exam_50' => $exam50,
                ':total_100' => $total100,
                ':grade' => $grade,
                ':remarks' => $remarks,
                ':entered_by' => 'system',
                ':u_sba_raw_score' => $row['sba_raw_score'],
                ':u_sba_50' => $sba50,
                ':u_exam_raw_score' => $row['exam_raw_score'],
                ':u_exam_50' => $exam50,
                ':u_total_100' => $total100,
                ':u_grade' => $grade,
                ':u_remarks' => $remarks
            ]);
            $count++;
        }

        echo "Successfully updated {$count} student report records.\n";

        // Compute subject and class rankings now that report scores are finalised
        \App\Core\Queue::dispatch(\Jobs\ComputeRankingsJob::class, [
            'academicYear' => $academicYear,
            'term'         => $term,
        ]);
    }
}
