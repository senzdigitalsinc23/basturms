<?php

use Database\Migration;

class CreatePromotionCriteriaTable20250115000037 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS promotion_criteria (
                id INT AUTO_INCREMENT PRIMARY KEY,
                level_id VARCHAR(20) NOT NULL,
                min_score INT(3) NOT NULL,
                min_pass_mark INT(3) NOT NULL,
                min_electives INT(3) NOT NULL,
                added_by VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL,
                FOREIGN KEY (level_id) REFERENCES classes(class_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS promotion_criteria;");
    }
}
