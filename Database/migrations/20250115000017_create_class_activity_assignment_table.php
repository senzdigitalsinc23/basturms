<?php

use Database\Migration;

class CreateClassActivityAssignmentTable20250115000017 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS class_activity_assignment (
                id INT AUTO_INCREMENT PRIMARY KEY,
                class_id VARCHAR(20) NOT NULL,
                act_id VARCHAR(20) NOT NULL,
                assigned_by VARCHAR(20) NOT NULL,
                assigned_on DATETIME NOT NULL,
                FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (act_id) REFERENCES assignment_activities(activity_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS class_activity_assignment;");
    }
}
