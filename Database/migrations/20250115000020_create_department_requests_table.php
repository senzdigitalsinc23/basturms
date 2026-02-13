<?php

use Database\Migration;

class CreateDepartmentRequestsTable20250115000020 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS department_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id VARCHAR(20) NOT NULL,
                asset_id VARCHAR(20) NOT NULL,
                department VARCHAR(20) NOT NULL,
                req_quantity INT(4) NOT NULL,
                reason VARCHAR(100) NOT NULL,
                req_by VARCHAR(20) NOT NULL,
                req_status ENUM('Approved','Declined','','') NOT NULL,
                req_date DATE NOT NULL,
                approved_by VARCHAR(20) NOT NULL,
                approve_date INT(11) NOT NULL,
                FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS department_requests;");
    }
}
