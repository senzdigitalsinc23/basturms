<?php

use Database\Migration;

class CreateApiKeysTable20250115000002 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS api_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_value VARCHAR(128) NOT NULL,
                owner VARCHAR(120) DEFAULT NULL,
                scopes LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (JSON_VALID(scopes)),
                active TINYINT(1) NOT NULL DEFAULT 1,
                expires_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_key_value (key_value),
                INDEX idx_owner (owner),
                INDEX idx_active (active),
                INDEX idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS api_keys;");
    }
}
