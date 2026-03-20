<?php

use Database\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Create validation_settings table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS validation_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                month VARCHAR(20) NOT NULL,
                year INT NOT NULL,
                start_date DATETIME NOT NULL,
                end_date DATETIME NOT NULL,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_month_year (month, year),
                FOREIGN KEY (created_by) REFERENCES validation_staff(id) ON DELETE CASCADE,
                INDEX idx_month_year (month, year),
                INDEX idx_dates (start_date, end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "Created validation_settings table\n";
    }

    public function down(): void
    {
        $this->db->exec("DROP TABLE IF EXISTS validation_settings");
        echo "Dropped validation_settings table\n";
    }
};

