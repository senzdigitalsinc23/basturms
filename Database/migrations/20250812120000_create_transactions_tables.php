<?php

use Database\Migration;

class CreateTransactionsTables20250812120000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                gateway VARCHAR(50),
                transaction_id VARCHAR(100),
                amount DECIMAL(10,2),
                currency VARCHAR(10),
                status VARCHAR(20),
                payload JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )

        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS transactions;");
    }
}