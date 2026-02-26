# Fix Student Phone Query Error ✓

## Problem
Getting error when fetching profile:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'st.phone' in 'field list'
```

## Root Cause
The query was trying to get phone from `students.phone`, but the `students` table doesn't have a `phone` column. Student phone information is stored in the `student_contact` table.

## Solution
Updated the query to join with `student_contact` table for student phone numbers.

### Updated Query
```sql
-- For students: join with student_contact table
LEFT JOIN students st ON u.user_id = st.student_no AND LOWER(r.name) = 'student'
LEFT JOIN student_contact sc ON u.user_id = sc.student_no AND LOWER(r.name) = 'student'

-- For staff: phone is in staff table
LEFT JOIN staff sf ON u.user_id = sf.staff_id AND LOWER(r.name) != 'student'

-- Select phone based on role
CASE 
    WHEN LOWER(r.name) = 'student' THEN sc.phone
    ELSE sf.phone
END as phone_from_table
```

## Table Structure

### Students
- `students` table: Contains student basic info (first_name, last_name, other_name, etc.)
- `student_contact` table: Contains contact info (email, phone, address, etc.)
- Relationship: `students.student_no = student_contact.student_no`

### Staff
- `staff` table: Contains all staff info including phone
- Phone is directly in the staff table

## Changes Made
- Added `LEFT JOIN student_contact sc` for student phone
- Updated CASE statement to use `sc.phone` instead of `st.phone`
- Applied to both main query and fallback query

## Files Modified
- `App/Services/ProfileService.php` - Updated getCurrentUser() method

## Testing
The profile endpoint should now work correctly:
```bash
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Expected response includes full_name and phone from the correct tables.
