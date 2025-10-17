<?php

use Database\Migration;

class CreateBlacklistedTokensTable20250811120000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE blacklisted_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token TEXT NOT NULL,
            blacklisted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS users;");
    }
}
