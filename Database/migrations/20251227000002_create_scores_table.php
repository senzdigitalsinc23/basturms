<?php

use Database\Migration;

class CreateScoresTable20251227000002 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE scores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(20) NOT NULL,
                subject_id INT NOT NULL,
                activity_id INT NOT NULL,
                class_id INT NOT NULL,
                academic_year VARCHAR(9) NOT NULL,
                term VARCHAR(20) NOT NULL,
                score DECIMAL(5,2) NOT NULL DEFAULT 0,
                entered_by VARCHAR(20) NOT NULL,
                entered_on DATETIME NOT NULL,
                
                -- Foreign keys
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
                
                -- Indexes for performance
                INDEX idx_student_subject (student_no, subject_id),
                INDEX idx_activity (activity_id),
                INDEX idx_academic_term (academic_year, term),
                INDEX idx_class (class_id),
                
                -- Unique constraint to prevent duplicate score entries
                UNIQUE KEY unique_score_entry (student_no, subject_id, activity_id, academic_year, term),
                
                -- Check constraint for score range
                CONSTRAINT chk_score_range CHECK (score >= 0 AND score <= 100)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS scores");
    }
}
