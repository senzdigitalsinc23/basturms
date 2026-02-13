<?php

use Database\Migration;

class CreatePaymentHistoryTable20250115000035 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS payment_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(20) NOT NULL,
                bill_no VARCHAR(20) NOT NULL,
                payment_amount DECIMAL(5,2) NOT NULL,
                payment_mode VARCHAR(20) NOT NULL,
                receipt_no VARCHAR(20) NOT NULL,
                payment_status ENUM('Fully Paid','Partially Paid','Unpaid') NOT NULL,
                payment_date DATETIME NOT NULL,
                received_by VARCHAR(20) NOT NULL,
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            PARTITION BY RANGE (YEAR(payment_date)) (
                PARTITION p2024 VALUES LESS THAN (2025),
                PARTITION p2025 VALUES LESS THAN (2026),
                PARTITION p2026 VALUES LESS THAN (2027),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS payment_history;");
    }
}
