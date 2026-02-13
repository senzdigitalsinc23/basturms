<?php

use Database\Migration;

class CreateResultsTable20250115000038 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS results (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(50) NOT NULL,
                subject_id INT NOT NULL,
                academic_year VARCHAR(9) NOT NULL,
                term VARCHAR(20) NOT NULL,
                class_id INT NOT NULL,
                score DECIMAL(5, 2) NOT NULL,
                grade VARCHAR(2),
                remarks VARCHAR(255),
                entered_by VARCHAR(20) NOT NULL,
                entered_on DATETIME NOT NULL,
                UNIQUE KEY idx_student_subject_term (student_no, subject_id, academic_year, term, class_id),
                FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
                INDEX idx_student_no (student_no),
                INDEX idx_academic_year (academic_year),
                INDEX idx_term (term)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS results;");
    }
}
