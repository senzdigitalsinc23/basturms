<?php

use Database\Migration;

class CreateAuthLogsTable20250115000003 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS auth_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(50) NULL,
                event ENUM('login', 'logout') NOT NULL,
                event_status ENUM('success', 'failure') NOT NULL,
                details TEXT NULL,
                client_info JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_event (event),
                INDEX idx_event_status (event_status),
                INDEX idx_created_at (created_at)
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS auth_logs;");
    }
}
