<?php

use Database\Migration;

/**
 * Migration to add profile_picture_id column to users table
 * 
 * This column stores the doc_id reference from the uploads table
 * for the user's profile picture.
 */
class AddProfilePictureToUsers20260222000001 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Add profile_picture_id column
        $this->execute("
            ALTER TABLE users 
            ADD COLUMN profile_picture_id VARCHAR(100) NULL 
            AFTER email
        ");

        // Add index for better query performance
        $this->execute("
            ALTER TABLE users 
            ADD INDEX idx_profile_picture_id (profile_picture_id)
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute("ALTER TABLE users DROP INDEX idx_profile_picture_id");
        $this->execute("ALTER TABLE users DROP COLUMN profile_picture_id");
    }
}
