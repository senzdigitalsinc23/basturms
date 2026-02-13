<?php

use Database\Migration;

class CreateClassesTable20250115000004 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS classes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                class_id VARCHAR(10) NOT NULL,
                class_name VARCHAR(100) NOT NULL,
                UNIQUE KEY idx_classes_class_id (class_id),
                KEY idx_classes_class_name (class_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS classes;");
    }
}
