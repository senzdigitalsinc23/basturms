<?php

use Database\Migration;

class FixStudentReportScoreColumns20260208000001 extends Migration
{
    public function up(): void
    {
        // First check if the table exists, if not create it
        $tableExists = $this->execute("
            SELECT COUNT(*) as count 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'student_report'
        ");
        
        $exists = $tableExists->fetch(\PDO::FETCH_ASSOC)['count'] > 0;
        
        if (!$exists) {
            // Create the student_report table if it doesn't exist
            $this->execute("
                CREATE TABLE student_report (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    student_no VARCHAR(20) NOT NULL,
                    subject_id INT NOT NULL,
                    class_id INT NOT NULL,
                    academic_year VARCHAR(9) NOT NULL,
                    term VARCHAR(20) NOT NULL,
                    sba_raw_score DECIMAL(6,2) NOT NULL DEFAULT 0,
                    `sba_50%` DECIMAL(6,2) NOT NULL DEFAULT 0,
                    exam_raw_score DECIMAL(6,2) NOT NULL DEFAULT 0,
                    `exam_50%` DECIMAL(6,2) NOT NULL DEFAULT 0,
                    `total_score_100%` DECIMAL(6,2) NOT NULL DEFAULT 0,
                    grade VARCHAR(2) NOT NULL DEFAULT '9',
                    remarks VARCHAR(50) NOT NULL DEFAULT 'N/A',
                    entered_by VARCHAR(20) NOT NULL,
                    entered_on DATETIME NOT NULL,
                    
                    -- Foreign keys
                    FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
                    
                    -- Indexes for performance
                    INDEX idx_student_subject (student_no, subject_id),
                    INDEX idx_academic_term (academic_year, term),
                    INDEX idx_class (class_id),
                    
                    -- Unique constraint to prevent duplicate report entries
                    UNIQUE KEY unique_report_entry (student_no, subject_id, class_id, academic_year, term)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        } else {
            // Alter existing columns to fix the numeric range issue
            // Using DECIMAL(6,2) for ALL score columns to handle larger sums (up to 9999.99)
            // All columns store summed values from multiple assignments, not individual percentages
            $this->execute("
                ALTER TABLE student_report 
                MODIFY COLUMN sba_raw_score DECIMAL(6,2) NOT NULL DEFAULT 0,
                MODIFY COLUMN `sba_50%` DECIMAL(6,2) NOT NULL DEFAULT 0,
                MODIFY COLUMN exam_raw_score DECIMAL(6,2) NOT NULL DEFAULT 0,
                MODIFY COLUMN `exam_50%` DECIMAL(6,2) NOT NULL DEFAULT 0,
                MODIFY COLUMN `total_score_100%` DECIMAL(6,2) NOT NULL DEFAULT 0
            ");
        }
    }

    public function down(): void
    {
        // Revert to smaller data types (not recommended, but provided for rollback)
        // Note: This may fail if there are values that don't fit in the smaller types
        $this->execute("
            ALTER TABLE student_report 
            MODIFY COLUMN sba_raw_score TINYINT NOT NULL DEFAULT 0,
            MODIFY COLUMN `sba_50%` TINYINT NOT NULL DEFAULT 0,
            MODIFY COLUMN exam_raw_score TINYINT NOT NULL DEFAULT 0,
            MODIFY COLUMN `exam_50%` TINYINT NOT NULL DEFAULT 0,
            MODIFY COLUMN `total_score_100%` TINYINT NOT NULL DEFAULT 0
        ");
    }
}
