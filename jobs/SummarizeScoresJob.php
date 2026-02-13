<?php

namespace Jobs;

use App\Core\Database;
use PDO;

class SummarizeScoresJob
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function handle(string $academicYear, string $term): void
    {
        echo "Starting score summarization for {$academicYear} - {$term}...\n";

        // Query to sum up scores for each assignment activity
        // We join scores with activities to get sub_activity_id (which is actually in the activities table, linked to assignment_activities)
        // Wait, activities table has act_id field which holds the activity_id from assignment_activities.
        // Let's check Activity model: act_id matches assignment_activities(activity_id).
        // And scores.activity_id (now referencing activities.id) -> activities.act_id -> assignment_activities.activity_id
        
        // Actually, let's look at the schema again. 
        // assignment_activities has unique activity_id (e.g., ACT123)
        // activities table has act_id (e.g., ACT123, referencing assignment_activities) and sub_activity_id (nullable, or just id).
        // The scores table now links to activities.id.
        
        $sql = "
            SELECT 
                s.student_no,
                s.subject_id,
                s.class_id,
                s.academic_year,
                s.term,
                aa.id as assignment_activity_id,
                aa.weight,
                aa.expected_per_term,
                SUM(s.score) as total_score
            FROM scores s
            JOIN activities a ON s.activity_id = a.id
            JOIN assignment_activities aa ON a.act_id = aa.activity_id
            WHERE s.academic_year = :academic_year AND s.term = :term
            GROUP BY s.student_no, s.subject_id, s.class_id, s.academic_year, s.term, aa.id, aa.weight, aa.expected_per_term
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':academic_year' => $academicYear,
            ':term' => $term
        ]);
        
        $summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($summaries) . " summary records to process.\n";
        
        $insertSql = "
            INSERT INTO student_summary_report 
                (student_no, subject_id, class_id, academic_year, term, assignment_activity_id, total_score, percentage_score)
            VALUES 
                (:student_no, :subject_id, :class_id, :academic_year, :term, :assignment_activity_id, :total_score, :percentage_score)
            ON DUPLICATE KEY UPDATE 
                total_score = :u_total_score,
                percentage_score = :u_percentage_score,
                updated_at = NOW()
        ";
        
        $insertStmt = $this->db->prepare($insertSql);
        
        $count = 0;
        foreach ($summaries as $summary) {
            $totalScore = (float)$summary['total_score'];
            $weight = (float)$summary['weight'];
            
            // Weighted Score for the summary report
            // Example: Raw sum 88 * (50 / 100) = 44.
            $percentageScore = $totalScore * ($weight / 100);

            $insertStmt->execute([
                ':student_no' => $summary['student_no'],
                ':subject_id' => $summary['subject_id'],
                ':class_id' => $summary['class_id'],
                ':academic_year' => $summary['academic_year'],
                ':term' => $summary['term'],
                ':assignment_activity_id' => $summary['assignment_activity_id'],
                ':total_score' => $totalScore,
                ':percentage_score' => $percentageScore,
                ':u_total_score' => $totalScore,
                ':u_percentage_score' => $percentageScore
            ]);
            $count++;
        }

        echo "Successfully updated {$count} summary records.\n";

        // Dispatch student report generation job
        \App\Core\Queue::dispatch(\Jobs\GenerateStudentReportJob::class, [
            'academicYear' => $academicYear,
            'term' => $term
        ]);
    }
}
