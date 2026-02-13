<?php

use Database\Migration;

class CreateSchoolDetailsTable20250115000039 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS school_details (
                id INT AUTO_INCREMENT PRIMARY KEY,
                school_id VARCHAR(5) NOT NULL,
                school_name VARCHAR(150) NOT NULL,
                district_id VARCHAR(5) NOT NULL,
                Location VARCHAR(100) NOT NULL,
                phone VARCHAR(13) NOT NULL,
                email VARCHAR(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS school_details;");
    }
}
