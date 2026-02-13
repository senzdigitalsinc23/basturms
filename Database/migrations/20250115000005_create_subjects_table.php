<?php

use Database\Migration;

class CreateSubjectsTable20250115000005 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                subject_name VARCHAR(100) NOT NULL,
                subject_code VARCHAR(10) NULL,
                level ENUM('Creche', 'KG', 'Primary', 'JHS') NOT NULL DEFAULT 'Primary',
                category ENUM('Core', 'Elective', 'Optional') NOT NULL DEFAULT 'Core',
                description TEXT NULL,
                status ENUM('active', 'dormant') NOT NULL DEFAULT 'active',
                added_by VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_subject_level (subject_name, level),
                UNIQUE KEY idx_subjects_subject_code (subject_code),
                INDEX idx_level (level),
                INDEX idx_category (category),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS subjects;");
    }
}
