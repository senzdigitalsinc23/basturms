<?php

use Database\Migration;

class CreateBlacklistedTokensTable20250115000014 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS blacklisted_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token TEXT NOT NULL,
                blacklisted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS blacklisted_tokens;");
    }
}
