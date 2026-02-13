<?php

use Database\Migration;

/**
 * Migration for creating the academic_year_terms table.
 * 
 * Stores the number of terms configuration for each academic year.
 */
class CreateAcademicYearTermsTable20250115000000 extends Migration
{
    /**
     * Creates the academic_year_terms table.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS academic_year_terms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                academic_year VARCHAR(9) NOT NULL,
                number_of_terms INT NOT NULL DEFAULT 3,
                added_by VARCHAR(20) NOT NULL,
                added_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_by VARCHAR(20) NULL,
                updated_on DATETIME NULL,
                UNIQUE KEY idx_academic_year (academic_year),
                INDEX idx_number_of_terms (number_of_terms),
                CONSTRAINT chk_terms_range CHECK (number_of_terms >= 1 AND number_of_terms <= 3)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Drops the academic_year_terms table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS academic_year_terms;");
    }
}
