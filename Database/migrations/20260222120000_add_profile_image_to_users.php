<?php

use Database\Migration;

/**
 * Migration to add profile image field to users table.
 * 
 * Adds a profile_image_id column that references the uploads table.
 */
class AddProfileImageToUsers20260222120000 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Add profile_image_id column
        $this->execute("
            ALTER TABLE users 
            ADD COLUMN profile_image_id INT NULL 
            AFTER email
        ");

        // Add foreign key to uploads table
        try {
            $this->execute("
                ALTER TABLE users 
                ADD CONSTRAINT fk_users_profile_image 
                FOREIGN KEY (profile_image_id) 
                REFERENCES uploads(id) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE
            ");
        } catch (\Throwable $e) {
            // If uploads table doesn't exist yet, skip FK creation
            error_log("Warning: Could not add foreign key for profile_image_id: " . $e->getMessage());
        }

        // Add index for performance
        $this->execute("
            ALTER TABLE users 
            ADD INDEX idx_profile_image_id (profile_image_id)
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Drop foreign key if exists
        try {
            $this->execute("ALTER TABLE users DROP FOREIGN KEY fk_users_profile_image");
        } catch (\Throwable $e) {
            // ignore
        }

        // Drop index
        try {
            $this->execute("ALTER TABLE users DROP INDEX idx_profile_image_id");
        } catch (\Throwable $e) {
            // ignore
        }

        // Drop column
        $this->execute("ALTER TABLE users DROP COLUMN profile_image_id");
    }
}
