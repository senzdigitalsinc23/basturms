<?php

use Database\Migration;

class CreateStudentSportsTeamsTable20250115000006 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS student_sports_teams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(50) NOT NULL,
                team_name VARCHAR(255) NOT NULL,
                sport VARCHAR(100) NOT NULL,
                position VARCHAR(100) NULL,
                join_date DATE NOT NULL,
                status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
                achievements TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student (student_no),
                INDEX idx_team_name (team_name),
                INDEX idx_sport (sport),
                INDEX idx_status (status),
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS student_sports_teams;");
    }
}
