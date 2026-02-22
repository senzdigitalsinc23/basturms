<?php

use Database\Migration;

class AddSchoolLevelPositions20260221000002 extends Migration
{
    public function up(): void
    {
        // Add level_position and school_position to student_term_rankings
        $this->execute("
            ALTER TABLE student_term_rankings
            ADD COLUMN level_position INT DEFAULT NULL COMMENT 'Rank within school level (1 = top, dense ranking)',
            ADD COLUMN school_position INT DEFAULT NULL COMMENT 'Rank within entire school (1 = top, dense ranking)',
            ADD INDEX idx_level_position (academic_year, term, level_position),
            ADD INDEX idx_school_position (academic_year, term, school_position)
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE student_term_rankings
            DROP INDEX idx_level_position,
            DROP INDEX idx_school_position,
            DROP COLUMN level_position,
            DROP COLUMN school_position
        ");
    }
}
