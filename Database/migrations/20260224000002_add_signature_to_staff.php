<?php

use Database\Migration;

class AddSignatureToStaff20260224000002 extends Migration
{
    public function up(): void
    {
        // Add signature_id column to staff table
        $this->execute("
            ALTER TABLE staff 
            ADD COLUMN signature_id VARCHAR(100) NULL AFTER phone,
            ADD INDEX idx_signature_id (signature_id)
        ");
    }

    public function down(): void
    {
        // Remove signature_id column
        $this->execute("
            ALTER TABLE staff 
            DROP INDEX idx_signature_id,
            DROP COLUMN signature_id
        ");
    }
}
