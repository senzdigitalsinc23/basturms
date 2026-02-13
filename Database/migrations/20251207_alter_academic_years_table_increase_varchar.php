<?php

use Database\Migration;

/**
 * Migration to increase the varchar length for academic_year field
 * to accommodate longer academic year names for testing purposes.
 */
class AlterAcademicYearsTableIncreaseVarchar extends Migration
{
    /**
     * Increases the varchar length for academic_year field from 9 to 50 characters.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute("
            ALTER TABLE academic_years
            MODIFY COLUMN academic_year VARCHAR(50) NOT NULL
        ");
    }

    /**
     * Reverts the varchar length back to 9 characters.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute("
            ALTER TABLE academic_years
            MODIFY COLUMN academic_year VARCHAR(9) NOT NULL
        ");
    }
}
