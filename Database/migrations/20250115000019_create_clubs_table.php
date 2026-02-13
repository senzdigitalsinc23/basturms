<?php

use Database\Migration;

class CreateClubsTable20250115000019 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS clubs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                description INT(11) NOT NULL,
                assigned_staff VARCHAR(20) NOT NULL,
                club_id INT(11) NOT NULL,
                FOREIGN KEY (assigned_staff) REFERENCES staff(staff_id) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS clubs;");
    }
}
