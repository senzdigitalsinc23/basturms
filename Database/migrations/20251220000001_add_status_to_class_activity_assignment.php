<?php

use Database\Migration;

class AddStatusToClassActivityAssignment20251220000001 extends Migration
{
    public function up(): void
    {
        $this->execute("
            ALTER TABLE class_activity_assignment 
            ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER act_id
        ");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE class_activity_assignment DROP COLUMN status");
    }
}
