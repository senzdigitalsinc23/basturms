<?php

use Database\Migration;

class CreateStudentsTable20250115000002 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(20) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                other_name VARCHAR(100) DEFAULT NULL,
                gender ENUM('Male','Female','Other') NOT NULL,
                dob DATE DEFAULT NULL,
                nhis_no VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_by VARCHAR(10) DEFAULT NULL,
                is_archived TINYINT(1) DEFAULT 0,
                UNIQUE KEY student_no (student_no),
                KEY id (id),
                KEY student_no_2 (student_no,first_name,last_name,other_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS students;");
    }
}
