<?php

use Database\Migration;

class CreateClassSubjectsTable20250115000018 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS class_subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                class_id INT NOT NULL,
                subject_id INT NOT NULL,
                added_by VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL,
                UNIQUE KEY idx_class_subject (class_id, subject_id),
                FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
                INDEX idx_class_id (class_id),
                INDEX idx_subject_id (subject_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS class_subjects;");
    }
}
