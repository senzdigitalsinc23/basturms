<?php

use Database\Migration;

class CreateAdmissionDetailsTable20250115000001 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS admission_details (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(20) NOT NULL,
                admission_no VARCHAR(10) NOT NULL,
                class_assigned VARCHAR(5) NOT NULL,
                enrollment_date DATE NOT NULL,
                admission_status ENUM('Admitted','Stopped','Pending','Graduated','Transferred','Suspended') NOT NULL,
                INDEX idx_student_no (student_no, class_assigned, admission_no, enrollment_date, admission_status),
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS admission_details;");
    }
}
