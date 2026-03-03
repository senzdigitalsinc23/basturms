# Staff Cascade Delete Documentation

## Overview
Foreign key constraints with CASCADE DELETE have been added to all staff-related tables. This ensures data integrity and automatic cleanup when a staff member is deleted.

## How It Works

When a staff record is deleted from the `staff` table, all related records in the following tables are automatically deleted:

### Tables with CASCADE DELETE

1. **staff_address** - Residential address information
2. **staff_academic_history** - Educational background
3. **staff_appointment_history** - Appointment dates and status
4. **staff_class** - Class assignments
5. **staff_subjects** - Subject teaching assignments
6. **staff_roles** - Role assignments
7. **notification_logs** - Notification history
8. **users** - User login account

## Migration

### Run Migration
```bash
php kiro migrate
```

### Rollback Migration
```bash
php kiro migrate:rollback
```

## Example: Delete Staff Member
```sql
DELETE FROM staff WHERE staff_id = 'LBAST26001';
```

**Automatic Actions:**
- ✅ Deletes address from staff_address
- ✅ Deletes all academic records
- ✅ Deletes appointment history
- ✅ Deletes all class assignments
- ✅ Deletes all subject assignments
- ✅ Deletes all role assignments
- ✅ Deletes notification logs
- ✅ Deletes user account

## Important Notes

### ⚠️ Warning
- CASCADE DELETE is **permanent** and **irreversible**
- All related data is deleted immediately
- Consider implementing soft delete for safety

### 🔒 Benefits
- ✅ Prevents orphaned records
- ✅ Maintains referential integrity
- ✅ Automatic cleanup
- ✅ Reduces manual queries

## Soft Delete (Recommended)

Instead of hard deleting, use soft delete:

```sql
-- Add columns
ALTER TABLE staff ADD COLUMN deleted_at TIMESTAMP NULL;
ALTER TABLE staff ADD COLUMN deleted_by VARCHAR(50) NULL;

-- Soft delete
UPDATE staff 
SET deleted_at = NOW(), 
    deleted_by = 'admin_id',
    status = 'deleted'
WHERE staff_id = 'LBAST26001';

-- Query active staff only
SELECT * FROM staff WHERE deleted_at IS NULL;
```

## Testing

```sql
-- Create test staff
INSERT INTO staff (staff_id, first_name, last_name, email, phone, id_type, id_no, status)
VALUES ('TEST001', 'Test', 'User', 'test@test.com', '0000000000', '1', 'TEST-001', 'active');

-- Add related records
INSERT INTO staff_address (staff_id, country, hometown, residence, house_no, gps_no)
VALUES ('TEST001', 'GH', 'Test', 'Test', 'T1', 'TEST-001');

-- Delete and verify cascade
DELETE FROM staff WHERE staff_id = 'TEST001';
SELECT COUNT(*) FROM staff_address WHERE staff_id = 'TEST001'; -- Should be 0
```
