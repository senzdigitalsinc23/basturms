<?php

use Database\Migration;

class CreateAssignmentActivitiesTable20250115000009 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS assignment_activities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                activity_id VARCHAR(20) NOT NULL,
                act_name VARCHAR(100) NOT NULL,
                expected_per_term INT(3) NOT NULL,
                weight INT(3) NOT NULL,
                added_by VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL,
                UNIQUE KEY idx_assignment_activities_activity_id (activity_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS assignment_activities;");
    }
}
