-- Add signature_id column to staff table
-- Run this SQL in your database

ALTER TABLE staff 
ADD COLUMN signature_id VARCHAR(100) NULL AFTER phone;

ALTER TABLE staff 
ADD INDEX idx_signature_id (signature_id);

-- Verify the column was added
DESCRIBE staff;
