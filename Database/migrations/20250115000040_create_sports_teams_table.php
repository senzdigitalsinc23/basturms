<?php

use Database\Migration;

class CreateSportsTeamsTable20250115000040 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS sports_teams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description VARCHAR(100) NOT NULL,
                coach VARCHAR(20) NOT NULL,
                team_id VARCHAR(20) NOT NULL,
                created_by VARCHAR(20) NOT NULL,
                created_on DATETIME NOT NULL,
                UNIQUE KEY idx_sports_teams_team_id (team_id),
                FOREIGN KEY (coach) REFERENCES staff(staff_id) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS sports_teams;");
    }
}
