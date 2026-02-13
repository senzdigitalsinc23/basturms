<?php

use Database\Migration;

class CreateGuardianInfoTable20250115000029 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS guardian_info (
                id INT AUTO_INCREMENT PRIMARY KEY,
                guardian_id VARCHAR(20) NOT NULL,
                guardian_name VARCHAR(100) NOT NULL,
                guardian_phone VARCHAR(13) NOT NULL,
                guardian_email VARCHAR(100) NOT NULL,
                guardian_relationship VARCHAR(50) NOT NULL,
                KEY guardian_id (guardian_id),
                FOREIGN KEY (guardian_id) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS guardian_info;");
    }
}
