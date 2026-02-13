<?php

use Database\Migration;

class CreateSecurityAuditTable20250115000001 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS security_audit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_type ENUM('LOGIN_ATTEMPT','PASSWORD_CHANGE','PERMISSION_CHANGE','DATA_ACCESS','SUSPICIOUS_ACTIVITY') NOT NULL,
                user_id VARCHAR(20) DEFAULT NULL,
                ip_address VARCHAR(15) NOT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                event_details TEXT,
                event_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                severity ENUM('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'LOW'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS security_audit;");
    }
}
