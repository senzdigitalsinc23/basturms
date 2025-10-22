<?php

use Database\Migration;

class CreateStudentClubsTable20250115000005 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS student_clubs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(50) NOT NULL,
                club_name VARCHAR(255) NOT NULL,
                club_description TEXT NULL,
                position VARCHAR(100) NULL,
                join_date DATE NOT NULL,
                status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student (student_no),
                INDEX idx_club_name (club_name),
                INDEX idx_status (status),
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS student_clubs;");
    }
}
