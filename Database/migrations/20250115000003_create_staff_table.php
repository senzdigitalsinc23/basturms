<?php

use Database\Migration;

class CreateStaffTable20250115000003 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS staff (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id VARCHAR(20) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                other_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(13) NOT NULL,
                id_type VARCHAR(20) NOT NULL,
                id_no VARCHAR(15) NOT NULL,
                snnit_no VARCHAR(20) NOT NULL,
                date_of_joining DATE NOT NULL,
                status VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL,
                added_by VARCHAR(20) NOT NULL,
                is_archived TINYINT(1) DEFAULT 0,
                UNIQUE KEY idx_staff_staff_id (staff_id),
                UNIQUE KEY idx_staff_email (email),
                KEY idx_staff_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS staff;");
    }
}
