-- Create staff_archive table for storing archived staff records
-- This table stores complete staff information when they are soft deleted

CREATE TABLE IF NOT EXISTS staff_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL,
    archive_reason TEXT NULL COMMENT 'Reason for archiving the staff member',
    archived_by VARCHAR(20) NOT NULL COMMENT 'User ID who archived the staff',
    archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the staff was archived',
    staff_data JSON NOT NULL COMMENT 'Complete staff record including personal info, address, academic history, appointments',
    
    INDEX idx_staff_id (staff_id),
    INDEX idx_archived_at (archived_at),
    INDEX idx_archived_by (archived_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment to table
ALTER TABLE staff_archive COMMENT = 'Archive table for soft-deleted staff members with complete historical data';

-- Verify table creation
DESCRIBE staff_archive;
