<?php

use Database\Migration;

class CreateAuthLogsTable20250115000012 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS auth_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(20) NOT NULL,
                event VARCHAR(50) NOT NULL,
                event_status VARCHAR(50) NOT NULL,
                details TEXT NOT NULL,
                event_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                client_info TEXT NOT NULL,
                ip_address VARCHAR(15) NOT NULL,
                KEY idx_auth_logs_user_id (user_id),
                KEY idx_auth_logs_event (event),
                KEY idx_auth_logs_event_status (event_status),
                KEY idx_auth_logs_event_date (event_date),
                KEY idx_auth_logs_ip_address (ip_address),
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            PARTITION BY RANGE (YEAR(event_date)) (
                PARTITION p2024 VALUES LESS THAN (2025),
                PARTITION p2025 VALUES LESS THAN (2026),
                PARTITION p2026 VALUES LESS THAN (2027),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS auth_logs;");
    }
}
