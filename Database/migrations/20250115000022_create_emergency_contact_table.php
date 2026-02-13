<?php

use Database\Migration;

class CreateEmergencyContactTable20250115000022 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS emergency_contact (
                id INT AUTO_INCREMENT PRIMARY KEY,
                emergency_id VARCHAR(20) NOT NULL,
                emergency_name VARCHAR(100) NOT NULL,
                emergency_phone VARCHAR(13) NOT NULL,
                emergency_email VARCHAR(100) NOT NULL,
                emergency_relationship VARCHAR(20) NOT NULL,
                KEY emergency_id (emergency_id, emergency_email, emergency_phone),
                FOREIGN KEY (emergency_id) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS emergency_contact;");
    }
}
