<?php

use Database\Migration;

class UpdatePromotionCriteriaFk20251226000002 extends Migration
{
    public function up(): void
    {
        // 1. Drop existing FK if it exists. 
        // We use a try-catch pattern or just skip since it seems already gone or failed.
        try {
            $this->execute("ALTER TABLE promotion_criteria DROP FOREIGN KEY promotion_criteria_ibfk_1");
        } catch (\Exception $e) {
            // Ignore if it doesn't exist
        }

        // 2. Add new FK referencing class_levels(level_code)
        $this->execute("
            ALTER TABLE promotion_criteria 
            ADD CONSTRAINT fk_promotion_criteria_class_levels 
            FOREIGN KEY (level_id) REFERENCES class_levels(level_code) 
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE promotion_criteria DROP FOREIGN KEY fk_promotion_criteria_class_levels");
        $this->execute("
            ALTER TABLE promotion_criteria 
            ADD CONSTRAINT promotion_criteria_ibfk_1 
            FOREIGN KEY (level_id) REFERENCES classes(class_id) 
            ON DELETE CASCADE ON UPDATE CASCADE
        ");
    }
}
