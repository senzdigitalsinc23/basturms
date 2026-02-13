<?php

use Database\Migration;

class CreateBackupSettingsTable20250115000013 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS backup_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                autobackup_enabled ENUM('true','false','','') NOT NULL,
                frequency VARCHAR(20) NOT NULL,
                backup_time TIME NOT NULL,
                last_backup DATETIME NOT NULL,
                setup_by VARCHAR(20) NOT NULL,
                setup_on DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS backup_settings;");
    }
}
