<?php

use Database\Migration;

/**
 * Migration to add account lockout tracking fields to users table.
 * 
 * Adds columns for tracking failed login attempts and account lockout status.
 */
class AddAccountLockoutFields20251226000000 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Add failed login attempts counter
        $this->execute("
            ALTER TABLE users 
            ADD COLUMN failed_login_attempts INT NOT NULL DEFAULT 0 
            AFTER is_super_admin
        ");

        // Add lockout expiration timestamp
        $this->execute("
            ALTER TABLE users 
            ADD COLUMN locked_until DATETIME NULL 
            AFTER failed_login_attempts
        ");

        // Add admin lock flag
        $this->execute("
            ALTER TABLE users 
            ADD COLUMN locked_by_admin TINYINT(1) NOT NULL DEFAULT 0 
            AFTER locked_until
        ");

        // Add index for performance on lockout queries
        $this->execute("
            ALTER TABLE users 
            ADD INDEX idx_locked_until (locked_until)
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute("ALTER TABLE users DROP INDEX idx_locked_until");
        $this->execute("ALTER TABLE users DROP COLUMN locked_by_admin");
        $this->execute("ALTER TABLE users DROP COLUMN locked_until");
        $this->execute("ALTER TABLE users DROP COLUMN failed_login_attempts");
    }
}
