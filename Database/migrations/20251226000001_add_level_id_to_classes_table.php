<?php

use Database\Migration;

class AddLevelIdToClassesTable20251226000001 extends Migration
{
    public function up(): void
    {
        $this->execute("
            ALTER TABLE classes 
            ADD COLUMN level_id VARCHAR(10) NULL AFTER class_name,
            ADD CONSTRAINT fk_classes_level_id 
            FOREIGN KEY (level_id) REFERENCES class_levels(level_code) 
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE classes DROP FOREIGN KEY fk_classes_level_id");
        $this->execute("ALTER TABLE classes DROP COLUMN level_id");
    }
}
