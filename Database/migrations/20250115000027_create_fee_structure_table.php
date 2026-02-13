<?php

use Database\Migration;

class CreateFeeStructureTable20250115000027 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS fee_structure (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fee_type VARCHAR(50) NOT NULL,
                description VARCHAR(100) NOT NULL,
                is_miscellenous ENUM('true','false','','') NOT NULL,
                level_applied VARCHAR(20) NOT NULL,
                setup_by VARCHAR(20) NOT NULL,
                setup_on DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS fee_structure;");
    }
}
