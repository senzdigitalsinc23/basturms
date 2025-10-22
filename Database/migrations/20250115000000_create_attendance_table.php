<?php

use Database\Migration;

class CreateAttendanceTable20250115000000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(50) NOT NULL,
                date DATE NOT NULL,
                status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
                notes TEXT NULL,
                recorded_by VARCHAR(50) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student_date (student_no, date),
                INDEX idx_date (date),
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS attendance;");
    }
}
