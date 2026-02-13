<?php

use Database\Migration;

class CreateGradingSchemeTable20250115000028 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS grading_scheme (
                id INT AUTO_INCREMENT PRIMARY KEY,
                grade VARCHAR(3) NOT NULL,
                grade_from INT(3) NOT NULL,
                grade_to INT(3) NOT NULL,
                remarks VARCHAR(20) NOT NULL,
                added_by VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS grading_scheme;");
    }
}
