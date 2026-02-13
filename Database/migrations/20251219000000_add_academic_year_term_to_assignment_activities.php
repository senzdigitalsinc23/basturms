<?php

use Database\Migration;

class AddAcademicYearTermToAssignmentActivities20251219000000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            ALTER TABLE assignment_activities 
            ADD COLUMN academic_year VARCHAR(20) NOT NULL AFTER weight,
            ADD COLUMN term VARCHAR(20) NOT NULL AFTER academic_year;
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE assignment_activities 
            DROP COLUMN academic_year,
            DROP COLUMN term;
        ");
    }
}
