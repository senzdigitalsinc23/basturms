<?php

use Database\Migration;

class CreateAttendanceHistoryTable20250115000010 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS attendance_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(20) DEFAULT NULL,
                att_status BIT(1) NOT NULL,
                att_date DATE DEFAULT NULL,
                KEY idx_attendance_student_no (student_no),
                KEY idx_attendance_date (att_date),
                KEY idx_attendance_status (att_status),
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS attendance_history;");
    }
}
