<?php

use Database\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Column already DATETIME from create migration — nothing to do
        echo "validation_settings time columns already correct, skipping.\n";
    }

    public function down(): void
    {
        $this->db->exec("
            ALTER TABLE validation_settings
            MODIFY COLUMN start_date DATE NOT NULL,
            MODIFY COLUMN end_date DATE NOT NULL
        ");
        
        echo "Removed time support from validation_settings table\n";
    }
};
