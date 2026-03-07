# Staff CRUD Operations API Documentation

## Overview
Complete documentation for staff Create, Read, Update, and Delete operations.

---

## 1. Update Staff
**PUT** `/api/v1/staff/{id}`

Update complete staff information including personal details, address, academic history, and appointments.

**Path Parameters:**
- `id` (string, required): Staff ID (e.g., LBAST26001)

**Request Body:** (Same format as registration)
```json
{
  "personal_contact": {
    "first_name": "Joseph",
    "last_name": "Konnie",
    "other_name": "",
    "email": "joseph.konnie@basturms.com",
    "phone": "0247760226",
    "id_type": "1",
    "id_no": "GHA-718881425-1",
    "snnit_no": "1234567879898987",
    "date_of_joining": "2026-01-01",
    "status": "active"
  },
  "address": {
    "country": "GH",
    "city": "Tarkwa",
    "hometown": "Dompim Pepesa",
    "residence": "Dompim",
    "house_no": "DP21",
    "gps_no": "WT-2018-0191"
  },
  "academic_history": [
    {
      "school_name": "University of Ghana",
      "program_offered": "Bsc. Agricultural Science",
      "qualification": "Bsc Agric",
      "year_completed": "2020"
    }
  ],
  "appointment_history": {
    "appointment_date": "2026-02-20",
    "appointment_status": "appointed",
    "class_teacher_for": "jhs1",
    "assigned_classes": [
      {"class_id": "jhs1"},
      {"class_id": "jhs2"}
    ],
    "assigned_subjects": [
      {"subject_id": "INT-SCI", "class_id": "jhs1"},
      {"subject_id": "INT-SCI", "class_id": "jhs2"}
    ],
    "roles": [19]
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Staff updated successfully",
  "data": {
    "staff_id": "LBAST26001",
    "email": "joseph.konnie@basturms.com",
    "message": "Staff updated successfully"
  }
}
```

**Notes:**
- All nested structures (address, academic_history, appointment_history) are replaced, not merged
- Academic history is completely replaced with the new array
- Class, subject, and role assignments are replaced with new values
- Transaction-safe: all changes are rolled back if any part fails

---

## 2. Soft Delete Staff (Archive)
**DELETE** `/api/v1/staff/{id}`

Archive/soft delete a staff member. The record is marked as archived but not permanently removed from the database.

**Path Parameters:**
- `id` (string, required): Staff ID (e.g., LBAST26001)

**Request Body:** (Optional)
```json
{
  "reason": "Resignation"
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

**What Happens:**
- Complete staff data is backed up to `staff_archive` table (JSON format)
- Staff record is marked as `is_archived = 1`
- Status is set to `deleted`
- `archived_at` timestamp is recorded
- `archived_by` is set to current user
- Optional `archive_reason` is stored
- User account is deactivated
- All related records remain intact (can be restored)
- Archive includes: personal info, address, academic history, appointments, classes, subjects, roles

**Archive Data Structure:**
The `staff_archive` table stores a complete JSON snapshot including:
- Personal information (name, email, phone, ID numbers)
- Address details
- Academic history
- Appointment details
- All class assignments
- All subject assignments
- All role assignments
- Metadata (added_on, added_by, etc.)

**Notes:**
- Soft deleted staff can be restored later
- Staff won't appear in normal queries (filtered by is_archived = 0)
- All assignments and history are preserved

---

## 3. Permanently Delete Staff
**DELETE** `/api/v1/staff/{id}/permanent`

Permanently delete a staff member and ALL related records. This action CANNOT be undone!

**Path Parameters:**
- `id` (string, required): Staff ID (e.g., LBAST26001)

**Response:**
```json
{
  "success": true,
  "message": "Staff permanently deleted successfully",
  "data": {
    "staff_id": "LBAST26001",
    "message": "Staff permanently deleted successfully"
  }
}
```

**What Gets Deleted (CASCADE DELETE):**
- Staff record from `staff` table
- Staff address from `staff_address` table
- Academic history from `staff_academic_history` table
- Appointment history from `staff_appointment_history` table
- Class assignments from `staff_class` table
- Subject assignments from `staff_subjects` table
- Role assignments from `staff_roles` table
- User account from `users` table
- Notification logs from `notification_logs` table

**⚠️ WARNING:**
- This action is IRREVERSIBLE
- All related data is permanently removed
- Use soft delete instead if you might need to restore the record
- Recommended only for test data or compliance requirements

---

## Usage Examples

### Update Staff
```bash
curl -X PUT http://localhost/api/v1/staff/LBAST26001 \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -H "Authorization: Bearer your-jwt-token" \
  -d '{
    "personal_contact": {
      "first_name": "Joseph",
      "last_name": "Konnie",
      "email": "joseph.konnie@basturms.com",
      "phone": "0247760226",
      "id_type": "1",
      "id_no": "GHA-718881425-1",
      "snnit_no": "1234567879898987",
      "date_of_joining": "2026-01-01",
      "status": "active"
    },
    "address": {
      "country": "GH",
      "city": "Tarkwa",
      "hometown": "Dompim Pepesa",
      "residence": "Dompim",
      "house_no": "DP21",
      "gps_no": "WT-2018-0191"
    }
  }'
```

### Soft Delete Staff
```bash
curl -X DELETE http://localhost/api/v1/staff/LBAST26001 \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -H "Authorization: Bearer your-jwt-token" \
  -d '{
    "reason": "Resignation"
  }'
```

### Permanently Delete Staff
```bash
curl -X DELETE http://localhost/api/v1/staff/LBAST26001/permanent \
  -H "X-API-Key: your-api-key" \
  -H "Authorization: Bearer your-jwt-token"
```

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Staff ID is required"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Staff not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "first_name": ["First name is required"],
    "email": ["Valid email is required"]
  }
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "An error occurred during staff update"
}
```

---

## Best Practices

1. **Always use soft delete first**: Unless you have a specific reason to permanently delete, use soft delete to preserve data integrity

2. **Provide deletion reasons**: When soft deleting, always provide a reason for audit purposes

3. **Validate before permanent delete**: Confirm with users before permanently deleting records

4. **Update incrementally**: When updating, you can send only the sections that changed (personal_contact, address, etc.)

5. **Transaction safety**: All operations are wrapped in transactions - if any part fails, everything is rolled back

6. **Audit logging**: All operations are automatically logged with user information and timestamps

---

## Related Endpoints

- **Register Staff**: `POST /api/v1/staff/register`
- **Get All Staff**: `GET /api/v1/staff`
- **Get Staff Details**: `POST /api/v1/staff/details`
- **Filter Staff**: `GET /api/v1/staff/filter`
- **Assign Classes**: `POST /api/v1/staff/{id}/assign-classes`
- **Assign Subjects**: `POST /api/v1/staff/{id}/assign-subjects`
- **Get Assignments**: `GET /api/v1/staff/{id}/assignments`

See `STAFF_ASSIGNMENT_ENDPOINTS.md` for assignment operations documentation.
