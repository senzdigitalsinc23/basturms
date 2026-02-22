<?php

namespace Jobs;

use App\Core\Database;
use PDO;

/**
 * ComputeRankingsJob
 *
 * Runs after GenerateStudentReportJob for the same (academic_year, term).
 *
 * Step 1 – subject_position
 * Step 2 – class_position
 * Step 3 – level_position
 * Step 4 – school_position
 */
class ComputeRankingsJob
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function handle(string $academicYear, string $term): void
    {
        echo "Computing rankings for {$academicYear} – {$term}...\n";

        $this->computeSubjectPositions($academicYear, $term);
        $this->computeClassPositions($academicYear, $term);
        $this->computeLevelPositions($academicYear, $term);
        $this->computeSchoolPositions($academicYear, $term);

        echo "Ranking computation complete.\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 1 : subject_position
    // ─────────────────────────────────────────────────────────────────────────

    private function computeSubjectPositions(string $academicYear, string $term): void
    {
        // Fetch all rows we need to rank, grouped by class+subject
        $stmt = $this->db->prepare("
            SELECT id, class_id, subject_id, student_no, `total_score_100%` AS total_score
            FROM   student_report
            WHERE  academic_year = :ay
              AND  term          = :term
            ORDER  BY class_id ASC, subject_id ASC, `total_score_100%` DESC
        ");
        $stmt->execute([':ay' => $academicYear, ':term' => $term]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo "  [subject_position] No report rows found.\n";
            return;
        }

        // Group by (class_id, subject_id)
        $groups = [];
        foreach ($rows as $row) {
            $key = "{$row['class_id']}_{$row['subject_id']}";
            $groups[$key][] = $row;
        }

        $updateStmt = $this->db->prepare("
            UPDATE student_report
            SET    subject_position = :pos
            WHERE  id = :id
        ");

        $total = 0;
        foreach ($groups as $key => $members) {
            $position  = 0;
            $prevScore = null;

            foreach ($members as $member) {
                $score = (float)$member['total_score'];
                if ($score !== $prevScore) {
                    $position++;
                }

                $updateStmt->execute([
                    ':pos' => $position,
                    ':id'  => $member['id'],
                ]);

                $prevScore = $score;
                $total++;
            }
        }

        echo "  [subject_position] Updated {$total} rows across " . count($groups) . " class-subject groups.\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 2 : student_term_rankings  (class_position)
    // ─────────────────────────────────────────────────────────────────────────

    private function computeClassPositions(string $academicYear, string $term): void
    {
        // Aggregate each student's scores per class
        $stmt = $this->db->prepare("
            SELECT  student_no,
                    class_id,
                    SUM(`total_score_100%`)  AS total_score_sum,
                    AVG(`total_score_100%`)  AS average_score,
                    COUNT(*)                 AS subjects_count
            FROM    student_report
            WHERE   academic_year = :ay
              AND   term          = :term
            GROUP   BY student_no, class_id
            ORDER   BY class_id ASC, total_score_sum DESC
        ");
        $stmt->execute([':ay' => $academicYear, ':term' => $term]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo "  [class_position] No aggregated data found.\n";
            return;
        }

        // Group by class_id
        $classes = [];
        foreach ($rows as $row) {
            $classes[$row['class_id']][] = $row;
        }

        $upsertStmt = $this->db->prepare("
            INSERT INTO student_term_rankings
                (student_no, class_id, academic_year, term,
                 total_score_sum, average_score, subjects_count, class_position, updated_at)
            VALUES
                (:student_no, :class_id, :ay, :term,
                 :total_score_sum, :average_score, :subjects_count, :class_position, NOW())
            ON DUPLICATE KEY UPDATE
                total_score_sum = VALUES(total_score_sum),
                average_score   = VALUES(average_score),
                subjects_count  = VALUES(subjects_count),
                class_position  = VALUES(class_position),
                updated_at      = NOW()
        ");

        $total = 0;
        foreach ($classes as $classId => $members) {
            $position  = 0;
            $prevScore = null;

            foreach ($members as $member) {
                $score = (float)$member['total_score_sum'];
                if ($score !== $prevScore) {
                    $position++;
                }

                $upsertStmt->execute([
                    ':student_no'     => $member['student_no'],
                    ':class_id'       => $classId,
                    ':ay'             => $academicYear,
                    ':term'           => $term,
                    ':total_score_sum'=> round($score, 2),
                    ':average_score'  => round((float)$member['average_score'], 2),
                    ':subjects_count' => (int)$member['subjects_count'],
                    ':class_position' => $position,
                ]);

                $prevScore = $score;
                $total++;
            }
        }

        echo "  [class_position] Upserted {$total} rows across " . count($classes) . " classes.\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 3 : level_position
    // ─────────────────────────────────────────────────────────────────────────

    private function computeLevelPositions(string $academicYear, string $term): void
    {
        echo "  [level_position] Computing level rankings...\n";

        $stmt = $this->db->prepare("
            SELECT str.id, str.student_no, str.average_score, c.level_id
            FROM   student_term_rankings str
            JOIN   classes c ON str.class_id = c.id
            WHERE  str.academic_year = :ay
              AND  str.term          = :term
            ORDER  BY c.level_id ASC, str.average_score DESC
        ");
        $stmt->execute([':ay' => $academicYear, ':term' => $term]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo "    No data found for level rankings.\n";
            return;
        }

        $levels = [];
        foreach ($rows as $row) {
            $levelId = $row['level_id'] ?: 'Unknown';
            $levels[$levelId][] = $row;
        }

        $updateStmt = $this->db->prepare("
            UPDATE student_term_rankings
            SET    level_position = :pos
            WHERE  id = :id
        ");

        $total = 0;
        foreach ($levels as $levelId => $members) {
            $position  = 0;
            $prevScore = null;

            foreach ($members as $member) {
                $score = (float)$member['average_score'];
                if ($score !== $prevScore) {
                    $position++;
                }

                $updateStmt->execute([
                    ':pos' => $position,
                    ':id'  => $member['id'],
                ]);

                $prevScore = $score;
                $total++;
            }
        }

        echo "    Updated {$total} rows across " . count($levels) . " school levels.\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 4 : school_position
    // ─────────────────────────────────────────────────────────────────────────

    private function computeSchoolPositions(string $academicYear, string $term): void
    {
        echo "  [school_position] Computing school-wide rankings...\n";

        $stmt = $this->db->prepare("
            SELECT id, student_no, average_score
            FROM   student_term_rankings
            WHERE  academic_year = :ay
              AND  term          = :term
            ORDER  BY average_score DESC
        ");
        $stmt->execute([':ay' => $academicYear, ':term' => $term]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo "    No data found for school rankings.\n";
            return;
        }

        $updateStmt = $this->db->prepare("
            UPDATE student_term_rankings
            SET    school_position = :pos
            WHERE  id = :id
        ");

        $position  = 0;
        $prevScore = null;
        $total     = 0;

        foreach ($rows as $row) {
            $score = (float)$row['average_score'];
            if ($score !== $prevScore) {
                $position++;
            }

            $updateStmt->execute([
                ':pos' => $position,
                ':id'  => $row['id'],
            ]);

            $prevScore = $score;
            $total++;
        }

        echo "    Updated {$total} school-wide positions.\n";
    }
}
