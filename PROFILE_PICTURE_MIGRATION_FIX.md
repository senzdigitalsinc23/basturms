# Profile Picture Migration Fix

**Date:** February 22, 2026  
**Issue:** Profile endpoint failing before migration  
**Status:** ✅ FIXED

---

## Problem

The `/api/v1/profile/details` endpoint was returning an error:
```json
{
  "success": false,
  "message": "An error occurred while retrieving profile",
  "data": null
}
```

**Root Cause:** The SQL query was trying to select the `profile_picture_id` column which doesn't exist until the migration is run.

---

## Solution

Updated `ProfileService::getCurrentUser()` to check if the `profile_picture_id` column exists before including it in the query.

### Implementation

**Before:**
```php
// Always tries to join with uploads table
$stmt = $pdo->prepare("
    SELECT u.*, r.name as role_name, up.*
    FROM users u 
    LEFT JOIN uploads up ON u.profile_picture_id = up.doc_id
    WHERE u.id = ?
");
// ❌ Fails if profile_picture_id column doesn't exist
```

**After:**
```php
// Check if column exists first
$stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_picture_id'");
$columnExists = $stmt->fetch() !== false;

if ($columnExists) {
    // Include profile picture join
    $sql = "SELECT u.*, r.name, up.* FROM users u 
            LEFT JOIN uploads up ON u.profile_picture_id = up.doc_id 
            WHERE u.id = ?";
} else {
    // Fallback without profile picture
    $sql = "SELECT u.*, r.name FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            WHERE u.id = ?";
}
// ✅ Works with or without the column
```

---

## Benefits

✅ **Backward Compatible:** Works before migration is run  
✅ **Forward Compatible:** Automatically uses profile picture when column exists  
✅ **No Breaking Changes:** Existing functionality preserved  
✅ **Graceful Degradation:** Returns profile without picture if column missing  

---

## Behavior

### Before Migration (Column Doesn't Exist)

**Response:**
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "user_id": "user1",
    "username": "john_doe",
    "email": "john@example.com",
    "role_id": 2,
    "role_name": "Teacher",
    "status": "active",
    "is_super_admin": false,
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-22 15:45:00"
  }
}
```

**Note:** No `profile_picture_id` or `profile_picture` fields

### After Migration (Column Exists)

**Response:**
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "user_id": "user1",
    "username": "john_doe",
    "email": "john@example.com",
    "profile_picture_id": null,
    "profile_picture": null,
    "role_id": 2,
    "role_name": "Teacher",
    "status": "active",
    "is_super_admin": false,
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-02-22 15:45:00"
  }
}
```

**Note:** Includes `profile_picture_id` and `profile_picture` fields (null if no picture uploaded)

### After Uploading Profile Picture

**Response:**
```json
{
  "profile_picture_id": "user1_a3f5b2c8",
  "profile_picture": {
    "doc_id": "user1_a3f5b2c8",
    "upload_id": 123,
    "name": "profile.jpg",
    "url": "uploads/profile_pictures/user1_a3f5b2c8_profile_picture_91fe0d0f.jpg",
    "type": "image/jpeg",
    "size": 245678,
    "uploaded_at": "2026-02-22 23:45:00"
  }
}
```

---

## Testing

### Test Without Migration
```bash
# Should work now (no error)
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer TOKEN"

# Response should NOT include profile_picture fields
```

### Run Migration
```sql
ALTER TABLE users ADD COLUMN profile_picture_id VARCHAR(100) NULL AFTER email;
ALTER TABLE users ADD INDEX idx_profile_picture_id (profile_picture_id);
```

### Test After Migration
```bash
# Should still work
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer TOKEN"

# Response should include profile_picture_id and profile_picture (both null)
```

### Upload Profile Picture
```bash
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@profile.jpg" \
  -F "doc_type=profile_picture"
```

### Test With Profile Picture
```bash
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer TOKEN"

# Response should include profile_picture with all data
```

---

## Migration Instructions

### Option 1: Run SQL File
```bash
mysql -u root -p basturms_db < add_profile_picture_column.sql
```

### Option 2: Run SQL Manually
```sql
-- Connect to database
mysql -u root -p basturms_db

-- Add column
ALTER TABLE users 
ADD COLUMN profile_picture_id VARCHAR(100) NULL 
AFTER email;

-- Add index
ALTER TABLE users 
ADD INDEX idx_profile_picture_id (profile_picture_id);

-- Verify
DESCRIBE users;
```

### Option 3: Use Migration Command (if available)
```bash
php bin/console make:db migrate
```

---

## Files Modified

1. **App/Services/ProfileService.php**
   - `getCurrentUser()` - Added column existence check
   - `sanitizeUserData()` - Conditional profile picture fields

---

## Status

✅ **Profile endpoint now works with or without migration**  
✅ **No breaking changes**  
✅ **Backward compatible**  
✅ **Ready for production**

---

## Recommendation

**Run the migration when convenient:**
- The profile endpoint works without it
- Profile picture feature requires it
- No downtime needed
- Can be run during normal operation

---

**Issue Resolved:** February 22, 2026  
**Solution:** Conditional column check  
**Impact:** Zero downtime migration