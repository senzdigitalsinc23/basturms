<?php

use Database\Migration;

class CreateBackupConfigTable20250115000002 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS backup_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                backup_type ENUM('FULL','INCREMENTAL','DIFFERENTIAL') NOT NULL DEFAULT 'FULL',
                schedule VARCHAR(50) NOT NULL,
                retention_days INT(11) NOT NULL DEFAULT 30,
                destination VARCHAR(255) NOT NULL,
                compression ENUM('NONE','GZIP','BZIP2') DEFAULT 'GZIP',
                encryption ENUM('NONE','AES256') DEFAULT 'AES256',
                last_backup DATETIME DEFAULT NULL,
                next_backup DATETIME DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_by VARCHAR(20) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS backup_config;");
    }
}
