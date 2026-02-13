<?php

use Database\Migration;

class CreateLeaveRequestTable20250115000032 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS leave_request (
                id INT AUTO_INCREMENT PRIMARY KEY,
                leave_type VARCHAR(20) NOT NULL,
                leave_year VARCHAR(4) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATETIME NOT NULL,
                reason VARCHAR(100) NOT NULL,
                status ENUM('Approved','Declined','','') NOT NULL,
                request_date DATETIME NOT NULL,
                approved_by VARCHAR(20) NOT NULL,
                days_approved INT(3) NOT NULL,
                comment TEXT NOT NULL,
                approved_on DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS leave_request;");
    }
}
