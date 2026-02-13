<?php

use Database\Migration;

class CreateClassLevelsTable20250116000000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS class_levels (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                category VARCHAR(50) NOT NULL,
                level_code VARCHAR(10) NOT NULL,
                `rank` INT NOT NULL,
                UNIQUE KEY idx_class_levels_code (level_code),
                KEY idx_class_levels_category (category)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        // Populate with Ghana basic school categories
        $levels = [
            // Pre school
            ['name' => 'Creche', 'category' => 'Pre school', 'level_code' => 'CRE', 'rank' => 1],
            ['name' => 'Nursery 1', 'category' => 'Pre school', 'level_code' => 'NUR1', 'rank' => 2],
            ['name' => 'Nursery 2', 'category' => 'Pre school', 'level_code' => 'NUR2', 'rank' => 3],
            ['name' => 'Kindergarten 1', 'category' => 'Pre school', 'level_code' => 'KG1', 'rank' => 4],
            ['name' => 'Kindergarten 2', 'category' => 'Pre school', 'level_code' => 'KG2', 'rank' => 5],
            
            // Lower primary
            ['name' => 'Basic 1', 'category' => 'Lower primary', 'level_code' => 'BS1', 'rank' => 6],
            ['name' => 'Basic 2', 'category' => 'Lower primary', 'level_code' => 'BS2', 'rank' => 7],
            ['name' => 'Basic 3', 'category' => 'Lower primary', 'level_code' => 'BS3', 'rank' => 8],
            
            // Upper primary
            ['name' => 'Basic 4', 'category' => 'Upper primary', 'level_code' => 'BS4', 'rank' => 9],
            ['name' => 'Basic 5', 'category' => 'Upper primary', 'level_code' => 'BS5', 'rank' => 10],
            ['name' => 'Basic 6', 'category' => 'Upper primary', 'level_code' => 'BS6', 'rank' => 11],
            
            // Junior High School
            ['name' => 'JHS 1', 'category' => 'Junior High School', 'level_code' => 'JHS1', 'rank' => 12],
            ['name' => 'JHS 2', 'category' => 'Junior High School', 'level_code' => 'JHS2', 'rank' => 13],
            ['name' => 'JHS 3', 'category' => 'Junior High School', 'level_code' => 'JHS3', 'rank' => 14],
        ];

        foreach ($levels as $level) {
            $this->execute("
                INSERT IGNORE INTO class_levels (name, category, level_code, `rank`) 
                VALUES ('{$level['name']}', '{$level['category']}', '{$level['level_code']}', {$level['rank']});
            ");
        }
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS class_levels;");
    }
}
