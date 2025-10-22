<?php

use Database\Migration;

class CreateAuditLogsTable20250115000002 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(50) NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT NULL,
                client_info JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS audit_logs;");
    }
}
