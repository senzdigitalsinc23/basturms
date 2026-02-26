# Add Full Name and Phone to User Profile ✓

## Requirement
Include full_name and phone number in the user profile response from `/api/v1/profile/details`.

## Implementation

### 1. Database Changes

#### Migration Created
- File: `Database/migrations/20260224000001_add_name_phone_to_users.php`
- SQL: `add_name_phone_to_users.sql`

#### New Columns Added to `users` Table
```sql
ALTER TABLE users 
ADD COLUMN full_name VARCHAR(100) NULL AFTER username;

ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) NULL AFTER email;
```

**Column Details:**
- `full_name`: VARCHAR(100), nullable - User's full name
- `phone`: VARCHAR(20), nullable - User's phone number

### 2. ProfileService Updates

#### Updated Response Structure
The `sanitizeUserData()` method now includes:
```php
$result = [
    'id' => (int)$userData['id'],
    'user_id' => $userData['user_id'],
    'username' => $userData['username'],
    'full_name' => $userData['full_name'] ?? null,  // NEW
    'email' => $userData['email'],
    'phone' => $userData['phone'] ?? null,          // NEW
    'role_id' => $userData['role_id'],
    'role_name' => $userData['role_name'],
    'status' => $userData['status'],
    'is_super_admin' => (bool)$userData['is_super_admin'],
    'created_at' => $userData['created_at'],
    'updated_at' => $userData['updated_at'],
    'profile_picture_id' => $userData['profile_picture_id'],
    'profile_picture' => $profilePicture
];
```

### 3. ProfileUpdateDTO Updates

#### New Fields Added
```php
public ?string $username = null;
public ?string $full_name = null;    // NEW
public ?string $email = null;
public ?string $phone = null;        // NEW
```

#### Validation Rules

**Full Name:**
- Minimum: 2 characters
- Maximum: 100 characters
- Cannot be empty if provided

**Phone:**
- Minimum: 10 characters
- Maximum: 20 characters
- Can only contain: numbers, +, -, spaces, parentheses
- Pattern: `/^[0-9+\-\s()]+$/`
- Cannot be empty if provided

## API Response

### GET /api/v1/profile/details

**Before (without full_name and phone):**
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 2169,
    "user_id": "usr_123456",
    "username": "senzdigitals",
    "email": "senzu.dogi23@gmail.com",
    "role_id": 17,
    "role_name": "Admin",
    "status": "active",
    "is_super_admin": true,
    "created_at": "2025-09-11 16:04:33",
    "updated_at": "2026-02-22 23:34:33"
  }
}
```

**After (with full_name and phone):**
```json
{
  "success": true,
  "message": "Profile retrieved successfully",
  "data": {
    "id": 2169,
    "user_id": "usr_123456",
    "username": "senzdigitals",
    "full_name": "John Doe",
    "email": "senzu.dogi23@gmail.com",
    "phone": "+233244000001",
    "role_id": 17,
    "role_name": "Admin",
    "status": "active",
    "is_super_admin": true,
    "created_at": "2025-09-11 16:04:33",
    "updated_at": "2026-02-22 23:34:33",
    "profile_picture_id": "usr_123456_fccc757d",
    "profile_picture": {
      "doc_id": "usr_123456_fccc757d",
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

## Profile Update API

### PUT /api/v1/profile/update

**Request Body (now accepts full_name and phone):**
```json
{
  "username": "newusername",
  "full_name": "John Doe",
  "email": "newemail@example.com",
  "phone": "+233244000001"
}
```

**Validation:**
- At least one field must be provided
- All fields are optional
- Each field is validated if provided

**Example Requests:**

1. Update only full name:
```json
{
  "full_name": "Jane Smith"
}
```

2. Update phone and email:
```json
{
  "phone": "+233244000002",
  "email": "jane@example.com"
}
```

3. Update all fields:
```json
{
  "username": "janesmith",
  "full_name": "Jane Smith",
  "email": "jane@example.com",
  "phone": "+233244000002"
}
```

## Migration Steps

### Option 1: Run Migration Script
```bash
php bin/migrate
```

### Option 2: Run SQL Manually
```bash
mysql -h 127.0.0.1 -u root -p basturms_db < add_name_phone_to_users.sql
```

Or execute in MySQL client:
```sql
USE basturms_db;

ALTER TABLE users 
ADD COLUMN full_name VARCHAR(100) NULL AFTER username;

ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) NULL AFTER email;

DESCRIBE users;
```

## Backward Compatibility

### Before Migration
- Fields return `null` if columns don't exist
- No errors thrown
- Graceful degradation

### After Migration
- Fields return actual values or `null`
- Fully functional
- Can be updated via profile update endpoint

## Testing

### 1. Get Profile (Before Migration)
```bash
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Expected: `full_name` and `phone` are `null`

### 2. Run Migration
```bash
mysql -h 127.0.0.1 -u root -p basturms_db < add_name_phone_to_users.sql
```

### 3. Update Profile with New Fields
```bash
curl -X PUT "http://localhost:8000/api/v1/profile/update" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Doe",
    "phone": "+233244000001"
  }'
```

### 4. Get Profile (After Update)
```bash
curl -X GET "http://localhost:8000/api/v1/profile/details" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Expected: `full_name` and `phone` contain the updated values

## Files Modified

1. **Database/migrations/20260224000001_add_name_phone_to_users.php** - Migration class
2. **add_name_phone_to_users.sql** - SQL migration script
3. **App/Services/ProfileService.php** - Updated sanitizeUserData() to include new fields
4. **App/DTOs/ProfileUpdateDTO.php** - Added full_name and phone fields with validation

## Validation Error Examples

### Invalid Phone Number
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "phone": "Phone number must be at least 10 characters long"
  }
}
```

### Invalid Full Name
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "full_name": "Full name must be at least 2 characters long"
  }
}
```

### Multiple Errors
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "full_name": "Full name cannot exceed 100 characters",
    "phone": "Phone number can only contain numbers, +, -, spaces, and parentheses"
  }
}
```

## Frontend Integration

### Display Full Name and Phone
```javascript
// React/Vue/Angular example
<div>
  <h2>{user.full_name || user.username}</h2>
  <p>Email: {user.email}</p>
  <p>Phone: {user.phone || 'Not provided'}</p>
</div>
```

### Update Profile Form
```javascript
const updateProfile = async (data) => {
  const response = await fetch('/api/v1/profile/update', {
    method: 'PUT',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      full_name: data.fullName,
      phone: data.phone,
      email: data.email
    })
  });
  
  return response.json();
};
```

## Notes

- Both fields are nullable - users can leave them empty
- Phone validation is flexible to support international formats
- Full name can contain spaces and special characters
- Fields can be updated independently
- No breaking changes to existing functionality
