<?php

use Database\Migration;

class AddStatusToAssignmentActivities20251219000001 extends Migration
{
    public function up(): void
    {
        $this->execute("
            ALTER TABLE assignment_activities 
            ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER added_on;
        ");
    }

    public function down(): void
    {
        $this->execute("
            ALTER TABLE assignment_activities 
            DROP COLUMN status;
        ");
    }
}
