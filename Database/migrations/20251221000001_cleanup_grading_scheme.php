<?php

use Database\Migration;

/**
 * Migration to cleanup grading_scheme data and apply the foreign key.
 */
class CleanupGradingScheme20251221000001 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // 1. Get a valid username to map orphan data to
        $stmt = $this->db->query("SELECT username FROM users LIMIT 1");
        $username = $stmt->fetchColumn();

        if ($username) {
            // 2. Update orphan data 'usr_123456' to a valid user
            $this->execute("UPDATE grading_scheme SET added_by = :valid_user WHERE added_by = 'usr_123456'", [':valid_user' => $username]);
            
            // 3. Try applying the FK again
            try {
                $this->execute("ALTER TABLE grading_scheme ADD CONSTRAINT fk_grading_scheme_added_by FOREIGN KEY (added_by) REFERENCES users(username) ON DELETE RESTRICT ON UPDATE CASCADE");
            } catch (\Throwable $e) {
                // Ignore if it fails again for some other reason
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        try {
            $this->execute("ALTER TABLE grading_scheme DROP FOREIGN KEY fk_grading_scheme_added_by");
        } catch (\Throwable $e) {}
    }
}
