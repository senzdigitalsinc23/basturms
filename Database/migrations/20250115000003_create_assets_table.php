<?php

use Database\Migration;

class CreateAssetsTable20250115000003 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS assets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                asset_id VARCHAR(20) NOT NULL,
                name VARCHAR(100) NOT NULL,
                category VARCHAR(100) NOT NULL,
                purchase_date DATE NOT NULL,
                purchase_cost DECIMAL(10,0) NOT NULL,
                purchase_condition VARCHAR(30) NOT NULL,
                added_by VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL,
                UNIQUE KEY idx_asset_id (asset_id),
                INDEX idx_category (category),
                INDEX idx_purchase_date (purchase_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS assets;");
    }
}
