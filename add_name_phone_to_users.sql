-- Add full_name and phone columns to users table
-- Run this SQL in your database

ALTER TABLE users 
ADD COLUMN full_name VARCHAR(100) NULL AFTER username;

ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) NULL AFTER email;

-- Verify the columns were added
DESCRIBE users;
