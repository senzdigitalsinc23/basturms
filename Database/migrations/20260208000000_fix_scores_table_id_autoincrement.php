<?php

use Database\Migration;

/**
 * Ensures the scores table id column is AUTO_INCREMENT.
 * Fixes: "Field 'id' doesn't have a default value" when inserting scores.
 */
class FixScoresTableIdAutoincrement20260208000000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            ALTER TABLE scores
            MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT
        ");
    }

    public function down(): void
    {
        // Reverting would remove AUTO_INCREMENT; leave as-is (no down).
    }
}
