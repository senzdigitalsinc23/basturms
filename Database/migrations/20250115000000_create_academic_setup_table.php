<?php

use Database\Migration;

/**
 * Migration for creating the academic_setup table.
 * 
 * Stores academic year terms with their dates, status, and audit information.
 */
class CreateAcademicSetupTable20250115000000 extends Migration
{
    /**
     * Creates the academic_setup table.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS academic_setup (
                id INT AUTO_INCREMENT PRIMARY KEY,
                academic_year VARCHAR(9) NOT NULL,
                term VARCHAR(20) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                status ENUM('Active', 'Upcoming', 'Completed') NOT NULL DEFAULT 'Upcoming',
                number_of_terms INT NOT NULL DEFAULT 3,
                added_by VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_by VARCHAR(20) NULL,
                updated_on DATETIME NULL,
                INDEX idx_academic_year (academic_year),
                INDEX idx_term (term),
                INDEX idx_status (status),
                INDEX idx_start_date (start_date),
                INDEX idx_end_date (end_date),
                UNIQUE KEY idx_year_term (academic_year, term),
                CONSTRAINT chk_dates CHECK (end_date >= start_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Drops the academic_setup table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS academic_setup;");
    }
}
