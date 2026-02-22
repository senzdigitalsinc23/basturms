<?php

use Database\Migration;

class AddRankingTables20260221000001 extends Migration
{
    public function up(): void
    {
        // ─── 1. Add subject_position to student_report (if missing) ───────────
        $colExists = $this->db->query("
            SELECT COUNT(*) as cnt
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name   = 'student_report'
              AND column_name  = 'subject_position'
        ");

        if ($colExists->fetch(\PDO::FETCH_ASSOC)['cnt'] == 0) {
            $this->execute("
                ALTER TABLE student_report
                ADD COLUMN subject_position INT DEFAULT NULL
                    COMMENT 'Rank within class for this subject/term (1 = top)',
                ADD INDEX idx_subject_position (class_id, subject_id, academic_year, term, subject_position)
            ");
        }

        // ─── 2. Create student_term_rankings (if missing) ─────────────────────
        $tblExists = $this->db->query("
            SELECT COUNT(*) as cnt
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name   = 'student_term_rankings'
        ");

        if ($tblExists->fetch(\PDO::FETCH_ASSOC)['cnt'] == 0) {
            $this->execute("
                CREATE TABLE student_term_rankings (
                    id             INT AUTO_INCREMENT PRIMARY KEY,
                    student_no     VARCHAR(20)   NOT NULL,
                    class_id       INT           NOT NULL,
                    academic_year  VARCHAR(9)    NOT NULL,
                    term           VARCHAR(20)   NOT NULL,
                    total_score_sum DECIMAL(10,2) NOT NULL DEFAULT 0
                        COMMENT 'Sum of total_score_100% across all subjects this term',
                    average_score  DECIMAL(6,2)  NOT NULL DEFAULT 0
                        COMMENT 'total_score_sum / subjects_count',
                    subjects_count INT           NOT NULL DEFAULT 0
                        COMMENT 'Number of subjects used in aggregation',
                    class_position INT           DEFAULT NULL
                        COMMENT 'Class rank this term (1 = top, dense ranking)',
                    computed_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,

                    FOREIGN KEY (student_no) REFERENCES students(student_no)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (class_id)   REFERENCES classes(id)
                        ON DELETE CASCADE ON UPDATE CASCADE,

                    UNIQUE KEY uq_student_term (student_no, class_id, academic_year, term),
                    INDEX idx_class_term      (class_id, academic_year, term, class_position)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                  COMMENT='Pre-computed per-term class rankings for each student'
            ");
        }
    }

    public function down(): void
    {
        // Drop table first (FK dependency order)
        $this->execute("DROP TABLE IF EXISTS student_term_rankings");

        // Remove column from student_report
        $colExists = $this->db->query("
            SELECT COUNT(*) as cnt
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name   = 'student_report'
              AND column_name  = 'subject_position'
        ");

        if ($colExists->fetch(\PDO::FETCH_ASSOC)['cnt'] > 0) {
            $this->execute("
                ALTER TABLE student_report
                DROP INDEX idx_subject_position,
                DROP COLUMN subject_position
            ");
        }
    }
}
