# Staff Management API - Complete Endpoint Summary

## All Available Staff Endpoints

### CRUD Operations

| Method | Endpoint | Description | Documentation |
|--------|----------|-------------|---------------|
| POST | `/api/v1/staff/register` | Register new staff member | See registration docs |
| GET | `/api/v1/staff` | Get all staff members | Returns paginated list |
| GET | `/api/v1/staff/filter` | Filter staff by role/class/subject | See filter docs |
| POST | `/api/v1/staff/details` | Get specific staff details | Requires staff_id in body |
| PUT | `/api/v1/staff/{id}` | Update staff member | See STAFF_CRUD_ENDPOINTS.md |
| DELETE | `/api/v1/staff/{id}` | Soft delete (archive) staff | See STAFF_CRUD_ENDPOINTS.md |
| DELETE | `/api/v1/staff/{id}/permanent` | Permanently delete staff | ⚠️ IRREVERSIBLE |

### Assignment Operations

| Method | Endpoint | Description | Documentation |
|--------|----------|-------------|---------------|
| POST | `/api/v1/staff/{id}/assign-classes` | Assign classes to staff | See STAFF_ASSIGNMENT_ENDPOINTS.md |
| POST | `/api/v1/staff/{id}/assign-subjects` | Assign subjects to staff | Supports multiple formats |
| GET | `/api/v1/staff/{id}/assignments` | Get all assignments | Returns classes & subjects |
| DELETE | `/api/v1/staff/{id}/remove-class/{class_id}` | Remove class assignment | Cascades to subjects |
| DELETE | `/api/v1/staff/{id}/remove-subject/{subject_id}/{class_id}` | Remove subject assignment | Specific subject-class pair |

### Other Operations

| Method | Endpoint | Description | Documentation |
|--------|----------|-------------|---------------|
| POST | `/api/v1/staff/share-credentials` | Share login credentials via email | Requires staff_id in body |

---

## Quick Reference

### Register Staff
```bash
POST /api/v1/staff/register
Body: {personal_contact, address, academic_history, appointment_history}
```

### Update Staff
```bash
PUT /api/v1/staff/LBAST26001
Body: {personal_contact, address, academic_history, appointment_history}
```

### Soft Delete Staff
```bash
DELETE /api/v1/staff/LBAST26001
Body: {reason: "Resignation"} (optional)
```

### Permanently Delete Staff
```bash
DELETE /api/v1/staff/LBAST26001/permanent
⚠️ WARNING: This cannot be undone!
```

### Assign Classes
```bash
POST /api/v1/staff/LBAST26001/assign-classes
Body: {class_ids: ["jhs1", "jhs2", "jhs3"]}
```

### Assign Subjects (Format 1 - Detailed)
```bash
POST /api/v1/staff/LBAST26001/assign-subjects
Body: {
  assignments: [
    {subject_id: "INT-SCI", class_id: "jhs1"},
    {subject_id: "INT-SCI", class_id: "jhs2"}
  ]
}
```

### Assign Subjects (Format 2 - Simplified)
```bash
POST /api/v1/staff/LBAST26001/assign-subjects
Body: {
  assignments: [
    {subject_id: "INT-SCI", class_ids: ["jhs1", "jhs2", "jhs3"]}
  ]
}
```

### Get Assignments
```bash
GET /api/v1/staff/LBAST26001/assignments
```

### Remove Class (Cascades to subjects)
```bash
DELETE /api/v1/staff/LBAST26001/remove-class/jhs1
```

### Remove Subject
```bash
DELETE /api/v1/staff/LBAST26001/remove-subject/INT-SCI/jhs1
```

---

## Authentication

All endpoints require:
- `X-API-Key` header with valid API key
- `Authorization: Bearer {token}` header with valid JWT token

---

## Key Features

✅ **Nested Payload Structure**: Registration and update use grouped data (personal_contact, address, etc.)

✅ **Transaction Safety**: All operations are atomic - either all succeed or all fail

✅ **Cascade Delete**: Deleting staff removes all related records automatically

✅ **Cascade Unassignment**: Removing a class removes all subjects for that class

✅ **Flexible Subject Assignment**: Supports both detailed and simplified formats

✅ **Duplicate Prevention**: System checks for existing assignments

✅ **Soft Delete**: Archive staff without losing data

✅ **Audit Logging**: All operations are logged with user and timestamp

✅ **Auto-generated IDs**: Staff IDs follow format LBAST{YY}{XXX}

✅ **Email Notifications**: Login credentials can be shared via email

---

## Documentation Files

- `STAFF_CRUD_ENDPOINTS.md` - Update, soft delete, permanent delete operations
- `STAFF_ASSIGNMENT_ENDPOINTS.md` - Class and subject assignment operations
- `NOTIFICATION_SETUP.md` - Email service configuration
- `ADD_FULLNAME_PHONE_TO_PROFILE.md` - User profile fields

---

## Database Tables

- `staff` - Main staff records
- `staff_address` - Staff addresses
- `staff_academic_history` - Educational background
- `staff_appointment_history` - Appointment details
- `staff_class` - Class assignments
- `staff_subjects` - Subject assignments
- `staff_roles` - Role assignments
- `users` - User accounts
- `notification_logs` - Email/SMS logs

All tables have CASCADE DELETE foreign keys to staff table.

---

## Common Response Codes

- `200` - Success
- `201` - Created (registration)
- `400` - Bad Request (missing parameters)
- `404` - Not Found (staff doesn't exist)
- `422` - Validation Error (invalid data)
- `500` - Internal Server Error

---

## Notes

1. Staff ID format: `LBAST{YY}{XXX}` (e.g., LBAST26001)
2. All dates should be in `YYYY-MM-DD` format
3. Phone numbers should be 10-15 digits
4. Email must be unique across all staff
5. Ghana Card (id_no) must be unique
6. SSNIT number must be unique
7. Username must be unique
8. Soft deleted staff have `is_archived = 1` and `status = 'deleted'`
9. Permanent delete uses CASCADE DELETE for all related records
10. Subject assignments are class-specific (same subject can be taught to different classes)
