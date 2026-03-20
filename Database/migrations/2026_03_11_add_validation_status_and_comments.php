<?php

use Database\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Add validation_status and comments columns to validations table
        $this->db->exec("
            ALTER TABLE validations
            ADD COLUMN validation_status ENUM('At Post', 'Not At Post') DEFAULT NULL AFTER validated,
            ADD COLUMN comments TEXT NULL AFTER validation_status
        ");
        
        echo "Added validation_status and comments columns to validations table\n";
    }

    public function down(): void
    {
        $this->db->exec("
            ALTER TABLE validations
            DROP COLUMN validation_status,
            DROP COLUMN comments
        ");
        
        echo "Removed validation_status and comments columns from validations table\n";
    }
};
