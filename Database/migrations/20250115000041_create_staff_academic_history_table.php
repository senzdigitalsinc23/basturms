<?php

use Database\Migration;

class CreateStaffAcademicHistoryTable20250115000041 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS staff_academic_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id VARCHAR(20) NOT NULL,
                school_name VARCHAR(100) NOT NULL,
                program_offered VARCHAR(100) NOT NULL,
                qualification VARCHAR(30) NOT NULL,
                year_completed VARCHAR(4) NOT NULL,
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS staff_academic_history;");
    }
}
