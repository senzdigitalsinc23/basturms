# Staff Archive Feature Documentation

## Overview
When a staff member is soft deleted (archived), the system now creates a complete backup of their data in the `staff_archive` table before marking them as archived in the `staff` table.

---

## Database Schema

### staff_archive Table
```sql
CREATE TABLE staff_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL,
    archive_reason TEXT NULL,
    archived_by VARCHAR(20) NOT NULL,
    archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    staff_data JSON NOT NULL,
    
    INDEX idx_staff_id (staff_id),
    INDEX idx_archived_at (archived_at),
    INDEX idx_archived_by (archived_by)
);
```

### Fields Description
- `id`: Auto-increment primary key
- `staff_id`: The staff member's ID (e.g., LBAST26001)
- `archive_reason`: Optional reason for archiving (e.g., "Resignation", "Retirement")
- `archived_by`: User ID who performed the archive operation
- `archived_at`: Timestamp when the staff was archived
- `staff_data`: Complete JSON snapshot of staff data at time of archiving

---

## What Gets Archived

The `staff_data` JSON field contains a complete snapshot including:

1. **Personal Information**
   - staff_id, first_name, last_name, other_name
   - email, phone, id_type, id_no, snnit_no
   - date_of_joining, status
   - signature_id, profile_picture_id

2. **Address Information**
   - country, city, hometown, residence
   - house_no, gps_no

3. **Academic History**
   - All educational records
   - school_name, program_offered, qualification, year_completed

4. **Appointment Details**
   - appointment_date, appointment_status
   - class_teacher_for

5. **Assignments**
   - All class assignments
   - All subject assignments
   - All role assignments

6. **Metadata**
   - added_on, added_by
   - archived_at, archived_by, archive_reason

---

## Soft Delete Process

When `DELETE /api/v1/staff/{id}` is called:

### Step 1: Retrieve Complete Data
```php
$staffData = $this->getCompleteStaffData($staffId);
```
Fetches all staff information from multiple tables:
- staff (with address)
- staff_academic_history
- staff_appointment_history
- staff_class
- staff_subjects
- staff_roles

### Step 2: Insert into Archive Table
```sql
INSERT INTO staff_archive (staff_id, archive_reason, archived_by, staff_data) 
VALUES (?, ?, ?, ?)
```
Creates a permanent backup with complete JSON data.

### Step 3: Update Staff Table
```sql
UPDATE staff SET 
    is_archived = 1, 
    archived_at = NOW(), 
    archived_by = ?, 
    archive_reason = ?,
    status = 'deleted'
WHERE staff_id = ?
```
Marks the staff as archived without deleting the record.

### Step 4: Transaction Safety
All operations are wrapped in a transaction:
- If any step fails, everything is rolled back
- Ensures data consistency

---

## API Usage

### Soft Delete Staff
**DELETE** `/api/v1/staff/{id}`

**Request:**
```json
{
  "reason": "Resignation - Moving to another institution"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Staff archived successfully",
  "data": {
    "staff_id": "LBAST26001",
    "message": "Staff archived successfully"
  }
}
```

---

## Benefits

### 1. Complete Data Preservation
- Full snapshot of staff data at time of archiving
- Includes all relationships (classes, subjects, roles)
- Preserves historical records for compliance

### 2. Easy Restoration
- All data available in JSON format
- Can be restored if needed
- Maintains data integrity

### 3. Audit Trail
- Who archived the staff (archived_by)
- When they were archived (archived_at)
- Why they were archived (archive_reason)

### 4. Compliance
- Meets data retention requirements
- Supports legal and HR needs
- Historical record keeping

### 5. Performance
- Archived staff don't appear in normal queries (is_archived = 0)
- Archive table separate from active data
- Indexed for fast retrieval

---

## Querying Archived Staff

### Get All Archived Staff
```sql
SELECT 
    staff_id,
    archive_reason,
    archived_by,
    archived_at,
    JSON_EXTRACT(staff_data, '$.first_name') as first_name,
    JSON_EXTRACT(staff_data, '$.last_name') as last_name,
    JSON_EXTRACT(staff_data, '$.email') as email
FROM staff_archive
ORDER BY archived_at DESC;
```

### Get Specific Archived Staff
```sql
SELECT * FROM staff_archive 
WHERE staff_id = 'LBAST26001'
ORDER BY archived_at DESC
LIMIT 1;
```

### Get Archive History for Staff
```sql
SELECT 
    id,
    archive_reason,
    archived_by,
    archived_at
FROM staff_archive 
WHERE staff_id = 'LBAST26001'
ORDER BY archived_at DESC;
```

---

## Restoration Process

To restore an archived staff member:

1. **Retrieve from Archive**
```sql
SELECT staff_data FROM staff_archive 
WHERE staff_id = ? 
ORDER BY archived_at DESC 
LIMIT 1;
```

2. **Parse JSON Data**
```php
$staffData = json_decode($archiveRecord['staff_data'], true);
```

3. **Update Staff Table**
```sql
UPDATE staff SET 
    is_archived = 0,
    status = 'active',
    archived_at = NULL,
    archived_by = NULL,
    archive_reason = NULL
WHERE staff_id = ?;
```

4. **Optionally Restore Assignments**
- Re-assign classes, subjects, and roles from archived data
- Update appointment information if needed

---

## Differences from Permanent Delete

| Feature | Soft Delete (Archive) | Permanent Delete |
|---------|----------------------|------------------|
| Data Preservation | ✅ Complete backup in staff_archive | ❌ All data removed |
| Reversible | ✅ Can be restored | ❌ Cannot be undone |
| Related Records | ✅ Preserved | ❌ CASCADE deleted |
| Audit Trail | ✅ Full history | ❌ No history |
| Compliance | ✅ Meets requirements | ⚠️ May violate policies |
| Use Case | Resignation, retirement, leave | Test data, duplicates |

---

## Migration

To create the staff_archive table, run:

```bash
php cli.php migrate
```

Or manually execute:
```bash
mysql -u root -p basturms_db < create_staff_archive_table.sql
```

---

## Best Practices

1. **Always Provide Reason**: Include a meaningful reason when archiving staff
2. **Use Soft Delete First**: Only use permanent delete for test data
3. **Regular Backups**: Backup the staff_archive table regularly
4. **Retention Policy**: Define how long to keep archived records
5. **Access Control**: Limit who can view/restore archived staff
6. **Audit Logging**: Log all archive and restore operations

---

## Related Files

- `Database/migrations/20260304000001_create_staff_archive_table.php` - Migration file
- `create_staff_archive_table.sql` - SQL creation script
- `App/Repositories/StaffRepository.php` - Archive implementation
- `App/Services/StaffService.php` - Archive service logic
- `App/Controllers/Api/v1/StaffController.php` - Archive endpoint

---

## Example Archive Data Structure

```json
{
  "staff_id": "LBAST26001",
  "first_name": "Joseph",
  "last_name": "Konnie",
  "email": "joseph.konnie@basturms.com",
  "phone": "0247760226",
  "profile_picture_id": "upload_12345.jpg",
  "country": "GH",
  "city": "Tarkwa",
  "academic_history": [
    {
      "school_name": "University of Ghana",
      "qualification": "Bsc Agric",
      "year_completed": "2020"
    }
  ],
  "appointment": {
    "appointment_date": "2026-02-20",
    "class_teacher_for": "jhs1"
  },
  "classes": [
    {"class_id": "jhs1", "class_name": "JHS 1"},
    {"class_id": "jhs2", "class_name": "JHS 2"}
  ],
  "subjects": [
    {"subject_id": "INT-SCI", "class_id": "jhs1"},
    {"subject_id": "INT-SCI", "class_id": "jhs2"}
  ],
  "roles": [
    {"role_id": 19, "role_name": "Teacher"}
  ]
}
```
