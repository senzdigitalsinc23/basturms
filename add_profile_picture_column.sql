-- Add profile_picture_id column to users table
-- Run this SQL in your database

ALTER TABLE users 
ADD COLUMN profile_picture_id VARCHAR(100) NULL 
AFTER email;

-- Add index for better query performance
ALTER TABLE users 
ADD INDEX idx_profile_picture_id (profile_picture_id);

-- Verify the column was added
DESCRIBE users;