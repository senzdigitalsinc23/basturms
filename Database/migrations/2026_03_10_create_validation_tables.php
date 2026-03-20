<?php

use Database\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS units (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                INDEX idx_name (name),
                INDEX idx_deleted_at (deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Create staff table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS validation_staff (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('staff', 'incharge', 'accountant', 'admin') NOT NULL DEFAULT 'staff',
                unit_id VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
                INDEX idx_email (email),
                INDEX idx_role (role),
                INDEX idx_unit_id (unit_id),
                INDEX idx_deleted_at (deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Create validations table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS validations (
                id VARCHAR(36) PRIMARY KEY,
                staff_id VARCHAR(36) NOT NULL,
                month VARCHAR(20) NOT NULL,
                year INT NOT NULL,
                validated BOOLEAN DEFAULT FALSE,
                validated_by VARCHAR(36),
                validated_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (staff_id) REFERENCES validation_staff(id) ON DELETE CASCADE,
                FOREIGN KEY (validated_by) REFERENCES validation_staff(id) ON DELETE SET NULL,
                UNIQUE KEY unique_staff_month_year (staff_id, month, year),
                INDEX idx_staff_id (staff_id),
                INDEX idx_month_year (month, year),
                INDEX idx_validated (validated),
                INDEX idx_validated_by (validated_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->db->exec("DROP TABLE IF EXISTS validations");
        $this->db->exec("DROP TABLE IF EXISTS validation_staff");
        $this->db->exec("DROP TABLE IF EXISTS units");
    }
};
