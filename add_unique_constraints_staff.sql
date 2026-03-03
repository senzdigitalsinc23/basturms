-- ============================================
-- Add Unique Constraints to Staff and Users Tables
-- Prevents duplicate email, phone, Ghana Card, SSNIT, and username
-- Run this in your MySQL/MariaDB client
-- ============================================

-- Check for existing duplicates before adding constraints
SELECT 'Checking for duplicates...' as Status;

-- Check duplicate emails in staff
SELECT 'Duplicate Emails in Staff:' as Check, email, COUNT(*) as count
FROM staff
WHERE email IS NOT NULL AND email != ''
GROUP BY email
HAVING count > 1;

-- Check duplicate phones in staff
SELECT 'Duplicate Phones in Staff:' as Check, phone, COUNT(*) as count
FROM staff
WHERE phone IS NOT NULL AND phone != ''
GROUP BY phone
HAVING count > 1;

-- Check duplicate Ghana Cards in staff
SELECT 'Duplicate Ghana Cards in Staff:' as Check, id_no, COUNT(*) as count
FROM staff
WHERE id_no IS NOT NULL AND id_no != ''
GROUP BY id_no
HAVING count > 1;

-- Check duplicate SSNIT numbers in staff
SELECT 'Duplicate SSNIT Numbers in Staff:' as Check, snnit_no, COUNT(*) as count
FROM staff
WHERE snnit_no IS NOT NULL AND snnit_no != ''
GROUP BY snnit_no
HAVING count > 1;

-- Check duplicate usernames in users
SELECT 'Duplicate Usernames in Users:' as Check, username, COUNT(*) as count
FROM users
WHERE username IS NOT NULL AND username != ''
GROUP BY username
HAVING count > 1;

-- Check duplicate emails in users
SELECT 'Duplicate Emails in Users:' as Check, email, COUNT(*) as count
FROM users
WHERE email IS NOT NULL AND email != ''
GROUP BY email
HAVING count > 1;

-- ============================================
-- Add Unique Constraints
-- ============================================

-- 1. Add unique constraint to staff.email
ALTER TABLE staff ADD UNIQUE KEY uk_staff_email (email);

-- 2. Add unique constraint to staff.phone
ALTER TABLE staff ADD UNIQUE KEY uk_staff_phone (phone);

-- 3. Add unique constraint to staff.id_no (Ghana Card)
ALTER TABLE staff ADD UNIQUE KEY uk_staff_id_no (id_no);

-- 4. Add unique constraint to staff.snnit_no (SSNIT Number)
-- Note: This allows NULL values but prevents duplicate non-NULL values
ALTER TABLE staff ADD UNIQUE KEY uk_staff_snnit_no (snnit_no);

-- 5. Add unique constraint to users.username
ALTER TABLE users ADD UNIQUE KEY uk_users_username (username);

-- 6. Add unique constraint to users.email
ALTER TABLE users ADD UNIQUE KEY uk_users_email (email);

-- ============================================
-- Verify Constraints
-- ============================================
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    NON_UNIQUE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('staff', 'users')
AND INDEX_NAME LIKE 'uk_%'
ORDER BY TABLE_NAME, INDEX_NAME;

SELECT 'Unique constraints added successfully!' as Status;

-- ============================================
-- Test Duplicate Prevention (Optional)
-- ============================================
-- Uncomment to test

-- This should fail with duplicate email error:
-- INSERT INTO staff (staff_id, first_name, last_name, email, phone, id_type, id_no, status)
-- VALUES ('TEST001', 'Test', 'User', 'existing@email.com', '0000000001', '1', 'TEST-001', 'active');

-- This should fail with duplicate phone error:
-- INSERT INTO staff (staff_id, first_name, last_name, email, phone, id_type, id_no, status)
-- VALUES ('TEST002', 'Test', 'User', 'new@email.com', '0240561313', '1', 'TEST-002', 'active');
