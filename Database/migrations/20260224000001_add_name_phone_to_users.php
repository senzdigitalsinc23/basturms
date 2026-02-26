<?php

use Database\Migration;

class AddNamePhoneToUsers20260224000001 extends Migration
{
    public function up(): void
    {
        // Add full_name and phone columns to users table
        $this->execute("
            ALTER TABLE users 
            ADD COLUMN full_name VARCHAR(100) NULL AFTER username,
            ADD COLUMN phone VARCHAR(20) NULL AFTER email
        ");
    }

    public function down(): void
    {
        // Remove full_name and phone columns
        $this->execute("
            ALTER TABLE users 
            DROP COLUMN full_name,
            DROP COLUMN phone
        ");
    }
}
