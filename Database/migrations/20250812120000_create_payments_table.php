<?php

use Database\Migration;

class CreatePaymentsTable20250812120000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id VARCHAR(100) UNIQUE NOT NULL,
                phone VARCHAR(20) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending','success','failed') DEFAULT 'pending',
                reference VARCHAR(100) NULL,
                reason VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )


        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS transactions;");
    }
}