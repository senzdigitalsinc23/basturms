-- ============================================
-- Add Foreign Key Constraints to Staff Tables
-- Run this in your MySQL/MariaDB client
-- ============================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- 1. staff_address
ALTER TABLE staff_address DROP FOREIGN KEY IF EXISTS fk_staff_address_staff_id;
ALTER TABLE staff_address
ADD CONSTRAINT fk_staff_address_staff_id
FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- 2. staff_academic_history
ALTER TABLE staff_academic_history DROP FOREIGN KEY IF EXISTS fk_staff_academic_history_staff_id;
ALTER TABLE staff_academic_history
ADD CONSTRAINT fk_staff_academic_history_staff_id
FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- 3. staff_appointment_history
ALTER TABLE staff_appointment_history DROP FOREIGN KEY IF EXISTS fk_staff_appointment_history_staff_id;
ALTER TABLE staff_appointment_history
ADD CONSTRAINT fk_staff_appointment_history_staff_id
FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- 4. staff_class
ALTER TABLE staff_class DROP FOREIGN KEY IF EXISTS fk_staff_class_staff_id;
ALTER TABLE staff_class
ADD CONSTRAINT fk_staff_class_staff_id
FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- 5. staff_subjects
ALTER TABLE staff_subjects DROP FOREIGN KEY IF EXISTS fk_staff_subjects_staff_id;
ALTER TABLE staff_subjects
ADD CONSTRAINT fk_staff_subjects_staff_id
FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- 6. staff_roles
ALTER TABLE staff_roles DROP FOREIGN KEY IF EXISTS fk_staff_roles_staff_id;
ALTER TABLE staff_roles
ADD CONSTRAINT fk_staff_roles_staff_id
FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- 7. notification_logs (if exists)
ALTER TABLE notification_logs DROP FOREIGN KEY IF EXISTS fk_notification_logs_staff_id;
ALTER TABLE notification_logs
ADD CONSTRAINT fk_notification_logs_staff_id
FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Verify Foreign Keys
-- ============================================
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    DELETE_RULE,
    UPDATE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE TABLE_NAME IN (
    'staff_address',
    'staff_academic_history',
    'staff_appointment_history',
    'staff_class',
    'staff_subjects',
    'staff_roles',
    'notification_logs'
)
AND CONSTRAINT_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- ============================================
-- Test Cascade Delete (Optional)
-- ============================================
-- Uncomment to test with a dummy record

-- CREATE TEST STAFF
-- INSERT INTO staff (staff_id, first_name, last_name, email, phone, id_type, id_no, status, added_on)
-- VALUES ('TEST999', 'Test', 'Delete', 'test@delete.com', '0000000000', '1', 'TEST-999', 'active', NOW());

-- ADD RELATED RECORDS
-- INSERT INTO staff_address (staff_id, country, hometown, residence, house_no, gps_no, added_on)
-- VALUES ('TEST999', 'GH', 'Test', 'Test', 'T1', 'TEST-999', NOW());

-- INSERT INTO staff_class (staff_id, classes_assigned, assigned_by)
-- VALUES ('TEST999', 'jhs1', 'system');

-- VERIFY RECORDS EXIST
-- SELECT 'Before Delete' as Status, COUNT(*) as Count FROM staff WHERE staff_id = 'TEST999'
-- UNION ALL
-- SELECT 'Address', COUNT(*) FROM staff_address WHERE staff_id = 'TEST999'
-- UNION ALL
-- SELECT 'Classes', COUNT(*) FROM staff_class WHERE staff_id = 'TEST999';

-- DELETE STAFF (CASCADE DELETE SHOULD REMOVE ALL RELATED RECORDS)
-- DELETE FROM staff WHERE staff_id = 'TEST999';

-- VERIFY CASCADE DELETE WORKED
-- SELECT 'After Delete' as Status, COUNT(*) as Count FROM staff WHERE staff_id = 'TEST999'
-- UNION ALL
-- SELECT 'Address', COUNT(*) FROM staff_address WHERE staff_id = 'TEST999'
-- UNION ALL
-- SELECT 'Classes', COUNT(*) FROM staff_class WHERE staff_id = 'TEST999';
-- All counts should be 0
