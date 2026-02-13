<?php

use Database\Migration;

/**
 * Migration for creating the academic_years table.
 * 
 * Stores academic year configuration with number of terms and status.
 */
class CreateAcademicYearsTable20250115000043 extends Migration
{
    /**
     * Creates the academic_years table.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS academic_years (
                id INT AUTO_INCREMENT PRIMARY KEY,
                academic_year VARCHAR(9) NOT NULL,
                number_of_terms INT NOT NULL DEFAULT 3,
                status ENUM('Active', 'Upcoming', 'Completed', 'Archived') NOT NULL DEFAULT 'Upcoming',
                added_by VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_by VARCHAR(20) NULL,
                updated_on DATETIME NULL,
                UNIQUE KEY idx_academic_year (academic_year),
                INDEX idx_status (status),
                INDEX idx_number_of_terms (number_of_terms),
                CONSTRAINT chk_terms_range CHECK (number_of_terms >= 1 AND number_of_terms <= 3)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Drops the academic_years table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS academic_years;");
    }
}

