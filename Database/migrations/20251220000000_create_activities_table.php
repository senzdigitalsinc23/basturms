<?php

use Database\Migration;

class CreateActivitiesTable20251220000000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS activities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                act_id VARCHAR(20) NOT NULL,
                activity_name VARCHAR(100) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                added_on DATETIME NOT NULL,
                FOREIGN KEY (act_id) REFERENCES assignment_activities(activity_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS activities;");
    }
}
