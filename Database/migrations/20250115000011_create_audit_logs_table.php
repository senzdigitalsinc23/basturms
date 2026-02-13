<?php

use Database\Migration;

class CreateAuditLogsTable20250115000011 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(20) NOT NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT NOT NULL,
                action_date DATETIME NOT NULL,
                client_info TEXT NOT NULL,
                ip_address VARCHAR(15) NOT NULL,
                user_agent VARCHAR(255) NOT NULL,
                KEY idx_audit_logs_user_id (user_id),
                KEY idx_audit_logs_action (action),
                KEY idx_audit_logs_action_date (action_date),
                KEY idx_audit_logs_ip_address (ip_address),
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            PARTITION BY RANGE (YEAR(action_date)) (
                PARTITION p2024 VALUES LESS THAN (2025),
                PARTITION p2025 VALUES LESS THAN (2026),
                PARTITION p2026 VALUES LESS THAN (2027),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS audit_logs;");
    }
}
