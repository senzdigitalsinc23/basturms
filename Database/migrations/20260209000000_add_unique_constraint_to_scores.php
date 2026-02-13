<?php

use Database\Migration;

class AddUniqueConstraintToScores20260209000000 extends Migration
{
    public function up(): void
    {
        // 1. Remove any existing duplicates just in case
        $this->execute("
            DELETE s1 FROM scores s1
            INNER JOIN scores s2 
            WHERE s1.id < s2.id 
            AND s1.student_no = s2.student_no 
            AND s1.subject_id <=> s2.subject_id 
            AND s1.activity_id = s2.activity_id 
            AND s1.academic_year = s2.academic_year 
            AND s1.term = s2.term
        ");

        // 2. Add the unique constraint
        $this->execute("
            ALTER TABLE scores 
            ADD UNIQUE KEY unique_score_entry (student_no, subject_id, activity_id, academic_year, term)
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE scores 
            DROP INDEX unique_score_entry
        ");
    }
}
