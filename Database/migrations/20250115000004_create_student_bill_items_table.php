<?php

use Database\Migration;

class CreateStudentBillItemsTable20250115000004 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS student_bill_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(50) NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                amount DECIMAL(10,2) NOT NULL,
                due_date DATE NULL,
                status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student (student_no),
                INDEX idx_status (status),
                INDEX idx_due_date (due_date),
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS student_bill_items;");
    }
}
