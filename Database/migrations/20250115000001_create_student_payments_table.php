<?php

use Database\Migration;

class CreateStudentPaymentsTable20250115000001 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS student_payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(50) NOT NULL,
                payment_type VARCHAR(100) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) NULL,
                reference VARCHAR(100) NULL,
                status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                payment_date DATE NULL,
                due_date DATE NULL,
                description TEXT NULL,
                created_by VARCHAR(50) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student (student_no),
                INDEX idx_payment_date (payment_date),
                INDEX idx_status (status),
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS student_payments;");
    }
}
