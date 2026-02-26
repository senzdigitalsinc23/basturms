# Join Full Name from Students/Staff Tables ✓

## Requirement
When fetching user profile:
- If user role is "student", join full_name and phone from the `students` table
- If user role is not "student" (staff, teacher, admin), join from the `staff` table
- Use the `user_id` field in users table to match with `student_no` or `staff_id`

## Implementation

### Updated Query Logic

The `getCurrentUser()` method in ProfileService now uses conditional joins based on the user's role:

```sql
SELECT 
    u.*,
    r.name as role_name,
    up.id as profile_picture_upload_id,
    up.doc_name as profile_picture_name,
    up.url as profile_picture_url,
    up.file_type as profile_picture_type,
    up.file_size as profile_picture_size,
    up.uploaded_at as profile_picture_uploaded_at,
    CASE 
        WHEN LOWER(r.name) = 'student' THEN CONCAT(st.first_name, ' ', COALESCE(st.other_name, ''), ' ', st.last_name)
        ELSE CONCAT(sf.first_name, ' ', COALESCE(sf.other_name, ''), ' ', sf.last_name)
    END as full_name_from_table,
    CASE 
        WHEN LOWER(r.name) = 'student' THEN st.phone
        ELSE sf.phone
    END as phone_from_table
FROM users u 
LEFT JOIN roles r ON u.role_id = r.role_id 
LEFT JOIN uploads up ON u.profile_picture_id = up.doc_id
LEFT JOIN students st ON u.user_id = st.student_no AND LOWER(r.name) = 'student'
LEFT JOIN staff sf ON u.user_id = sf.staff_id AND LOWER(r.name) != 'student'
WHERE u.id = ?
```

### How It Works

1. **Role Detection**: Checks the user's role name from the `roles` table
2. **Conditional Join**: 
   - If role is "student": joins with `students` table using `user_id = student_no`
   - If role is anything else: joins with `staff` table using `user_id = staff_id`
3. **Name Concatenation**: Combines `first_name`, `other_name`, and `last_name` into full_name
4. **Phone Extraction**: Gets phone from the appropriate table
5. **Fallback**: If no match found in students/staff tables, uses data from users table (if available)

### Field Mapping

#### Students Table
- `student_no` → matches `users.user_id`
- `first_name` + `other_name` + `last_name` → `full_name`
- `phone` → `phone`

#### Staff Table
- `staff_id` → matches `users.user_id`
- `first_name` + `other_name` + `last_name` → `full_name`
- `phone` → `phone`

## API Response Examples

### Student User Profile
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 2169,
    "user_id": "STU001",
    "username": "john_student",
    "full_name": "John Michael Doe",
    "email": "john@example.com",
    "phone": "0244000001",
    "role_id": 5,
    "role_name": "student",
    "status": "active",
    "is_super_admin": false,
    "created_at": "2025-09-11 16:04:33",
    "updated_at": "2026-02-22 23:34:33",
    "profile_picture_id": "STU001_fccc757d",
    "profile_picture": {
      "doc_id": "STU001_fccc757d",
      "upload_id": 5,
      "name": "profile.jpg",
      "url": "http://localhost:8000/api/v1/uploads/public/5",
      "type": "image/jpeg",
      "size": 38645,
      "uploaded_at": "2026-02-24 01:31:06"
    }
  }
}
```

### Staff User Profile
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 100,
    "user_id": "STF001",
    "username": "jane_teacher",
    "full_name": "Jane Mary Smith",
    "email": "jane@example.com",
    "phone": "0244000002",
    "role_id": 3,
    "role_name": "teacher",
    "status": "active",
    "is_super_admin": false,
    "created_at": "2025-01-15 10:00:00",
    "updated_at": "2026-02-22 15:30:00",
    "profile_picture_id": "STF001_abc123",
    "profile_picture": {
      "doc_id": "STF001_abc123",
      "upload_id": 10,
      "name": "teacher_photo.jpg",
      "url": "http://localhost:8000/api/v1/uploads/public/10",
      "type": "image/jpeg",
      "size": 42000,
      "uploaded_at": "2026-02-20 09:15:00"
    }
  }
}
```

## Role Handling

### Supported Roles
- **student** → Joins with `students` table
- **staff** → Joins with `staff` table
- **teacher** → Joins with `staff` table
- **admin** → Joins with `staff` table
- Any other role → Joins with `staff` table

### Case Insensitive
The query uses `LOWER(r.name)` to ensure case-insensitive role matching:
- "Student", "STUDENT", "student" all match correctly

## Data Priority

1. **Primary Source**: Students or Staff table (based on role)
2. **Fallback**: Users table (if full_name/phone columns exist there)
3. **Default**: NULL if no data found

## Benefits

1. **Single Source of Truth**: Name and phone come from the authoritative table (students/staff)
2. **No Duplication**: No need to maintain full_name/phone in users table
3. **Automatic Updates**: Changes in students/staff tables reflect immediately in profile
4. **Role-Based**: Correctly handles different user types
5. **Backward Compatible**: Still works if users table has full_name/phone columns

## Database Requirements

### Students Table Must Have:
- `student_no` (VARCHAR) - matches users.user_id
- `first_name` (VARCHAR)
- `last_name` (VARCHAR)
- `other_name` (VARCHAR, nullable)
- `phone` (VARCHAR)

### Staff Table Must Have:
- `staff_id` (VARCHAR) - matches users.user_id
- `first_name` (VARCHAR)
- `last_name` (VARCHAR)
- `other_name` (VARCHAR)
- `phone` (VARCHAR)

### Users Table Must Have:
- `user_id` (VARCHAR) - links to student_no or staff_id
- `role_id` (INT) - links to roles table

## Testing

### Test Student Profile
```bash
# Login as student
curl -X POST "http://localhost:8000/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "student@example.com", "password": "password"}'

# Get profile - should show name from students table
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer STUDENT_TOKEN"
```

### Test Staff Profile
```bash
# Login as staff/teacher
curl -X POST "http://localhost:8000/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "teacher@example.com", "password": "password"}'

# Get profile - should show name from staff table
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer STAFF_TOKEN"
```

## Edge Cases Handled

1. **User not in students/staff table**: Returns NULL for full_name and phone
2. **Missing other_name**: Handles gracefully with COALESCE
3. **Role name case variations**: Uses LOWER() for case-insensitive matching
4. **Profile picture missing**: Still returns user data without profile picture
5. **Multiple spaces in name**: Trimmed in the response

## Files Modified

- `App/Services/ProfileService.php` - Updated getCurrentUser() method with conditional joins

## SQL Query Explanation

### Main Query (with profile_picture_id)
```sql
LEFT JOIN students st ON u.user_id = st.student_no AND LOWER(r.name) = 'student'
LEFT JOIN staff sf ON u.user_id = sf.staff_id AND LOWER(r.name) != 'student'
```

- Both joins are LEFT JOIN so they don't filter out users
- Join condition includes role check to ensure only one table matches
- If role is 'student', only students join succeeds
- If role is anything else, only staff join succeeds

### CASE Statement
```sql
CASE 
    WHEN LOWER(r.name) = 'student' THEN CONCAT(st.first_name, ' ', COALESCE(st.other_name, ''), ' ', st.last_name)
    ELSE CONCAT(sf.first_name, ' ', COALESCE(sf.other_name, ''), ' ', sf.last_name)
END as full_name_from_table
```

- Selects the appropriate name based on role
- COALESCE handles NULL other_name
- Result is aliased as full_name_from_table
- Post-processed in PHP to trim extra spaces

## Performance Considerations

- Uses LEFT JOIN to avoid filtering users
- Indexes on `student_no` and `staff_id` improve join performance
- Role check in join condition reduces unnecessary data fetching
- Single query fetches all needed data (no N+1 problem)

## Future Enhancements

Consider adding:
- Caching for frequently accessed profiles
- Separate endpoint for bulk profile fetching
- Profile picture optimization/resizing
- Additional fields from students/staff tables (e.g., department, class)
