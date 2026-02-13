<?php

use Database\Migration;

class CreateStudentSummaryReportTable20251231000001 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE student_summary_report (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(20) NOT NULL,
                subject_id INT NOT NULL,
                class_id INT NOT NULL,
                academic_year VARCHAR(9) NOT NULL,
                term VARCHAR(20) NOT NULL,
                assignment_activity_id INT NOT NULL,
                total_score DECIMAL(5,2) NOT NULL DEFAULT 0,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Foreign keys
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (assignment_activity_id) REFERENCES assignment_activities(id) ON DELETE CASCADE ON UPDATE CASCADE,
                
                -- Unique constraint
                UNIQUE KEY unique_report_entry (student_no, subject_id, assignment_activity_id, academic_year, term)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS student_summary_report");
    }
}
