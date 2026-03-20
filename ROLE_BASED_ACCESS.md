# Role-Based Access Control (RBAC)

## Overview
The validation system implements role-based access control to ensure users can only access and validate staff within their permissions.

## Roles and Permissions

### 1. Admin
**Full Access**
- View all staff across all units
- Validate any staff member
- View all validation records
- Create/manage units and staff

**Endpoints:**
- ✓ GET `/api/v1/validation/staff` - Returns all staff
- ✓ POST `/api/v1/validations` - Can validate any staff
- ✓ GET `/api/v1/validations` - Returns all validations

### 2. Accountant/HR
**Full Access (Same as Admin)**
- View all staff across all units
- Validate any staff member
- View all validation records
- Create/manage units and staff

**Endpoints:**
- ✓ GET `/api/v1/validation/staff` - Returns all staff
- ✓ POST `/api/v1/validations` - Can validate any staff
- ✓ GET `/api/v1/validations` - Returns all validations

### 3. Incharge
**Unit-Restricted Access**
- View only staff in their own unit
- Validate only staff in their own unit
- View only validations for their unit's staff
- Cannot create units or staff outside their unit

**Endpoints:**
- ✓ GET `/api/v1/validation/staff` - Returns only staff in their unit
- ✓ POST `/api/v1/validations` - Can only validate staff in their unit (403 if attempting others)
- ✓ GET `/api/v1/validations` - Returns only validations for their unit's staff

**Security Checks:**
- When fetching staff: Filters by `unit_id` matching incharge's unit
- When validating: Verifies all `staffIds` belong to incharge's unit
- When viewing validations: Filters by staff `unit_id`

### 4. Staff
**Read-Only Self Access**
- View only their own information
- Cannot validate anyone
- View only their own validation status

**Endpoints:**
- ✓ GET `/api/v1/validation/auth/me` - Returns own information
- ✗ Cannot access other endpoints (would need separate implementation)

## Implementation Details

### Staff List Filtering (ValidationStaffController)

```php
if ($userRole === 'incharge') {
    // Only staff in incharge's unit
    $stmt = $this->db->prepare("
        SELECT s.*, u.name as unitName
        FROM validation_staff s
        LEFT JOIN units u ON s.unit_id = u.id
        WHERE s.deleted_at IS NULL 
        AND s.unit_id = (SELECT unit_id FROM validation_staff WHERE id = :user_id)
        ORDER BY s.name
    ");
    $stmt->execute(['user_id' => $userId]);
} else {
    // Admin/Accountant see all staff
    // ... query without unit filter
}
```

### Validation Security Check (ValidationController)

```php
if ($userRole === 'incharge' && $userUnitId) {
    // Verify all staff IDs belong to incharge's unit
    $stmt = $this->db->prepare("
        SELECT COUNT(*) as count 
        FROM validation_staff 
        WHERE id IN (?) 
        AND (unit_id != ? OR unit_id IS NULL)
    ");
    
    if ($result['count'] > 0) {
        return 403 Forbidden: "You can only validate staff in your unit"
    }
}
```

### Validation History Filtering (ValidationController)

```php
if ($userRole === 'incharge' && $userUnitId) {
    // Only validations for staff in incharge's unit
    $stmt = $this->db->prepare("
        SELECT v.*, s.name as staffName
        FROM validations v
        INNER JOIN validation_staff s ON v.staff_id = s.id
        WHERE v.month = :month AND v.year = :year
        AND s.unit_id = :unit_id
    ");
}
```

## Testing Role-Based Access

### Test as Admin
```bash
# Login as admin
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@validation.com","password":"admin123"}' \
  | jq -r '.token')

# Get all staff (should return all)
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-API-Key: devKey123"

# Validate any staff (should succeed)
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-API-Key: devKey123" \
  -H "Content-Type: application/json" \
  -d '{"staffIds":[1,2,3],"month":"March","year":2026}'
```

### Test as Incharge
```bash
# Login as incharge
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"incharge1@validation.com","password":"incharge123"}' \
  | jq -r '.token')

# Get staff (should return only staff in their unit)
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-API-Key: devKey123"

# Try to validate staff from another unit (should fail with 403)
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-API-Key: devKey123" \
  -H "Content-Type: application/json" \
  -d '{"staffIds":[1],"month":"March","year":2026}'

# Validate staff from own unit (should succeed)
# First get staff IDs from the staff list, then validate
```

## Database Structure

### Units Table
```sql
CREATE TABLE units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT
);
```

### Staff Table (with unit_id)
```sql
CREATE TABLE validation_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('staff', 'incharge', 'accountant', 'admin'),
    unit_id INT,
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
```

## Security Considerations

### 1. JWT Token Contains Role and Unit
```json
{
  "user_id": 5,
  "email": "incharge1@validation.com",
  "role": "incharge",
  "unit_id": 2,
  "exp": 1234567890
}
```

### 2. Every Protected Endpoint Checks Role
- AuthMiddleware extracts user data from JWT
- Controllers access via `$request->getAttribute('user')`
- Role and unit_id used for filtering and authorization

### 3. Database-Level Filtering
- Queries include `unit_id` in WHERE clause for incharge
- Prevents data leakage even if frontend bypassed

### 4. Validation Before Action
- Validation endpoint checks staff ownership before allowing validation
- Returns 403 Forbidden if attempting to validate outside unit

## Error Responses

### 403 Forbidden (Incharge validating outside unit)
```json
{
  "success": false,
  "message": "You can only validate staff in your unit"
}
```

### 401 Unauthorized (No token or invalid token)
```json
{
  "success": false,
  "message": "Missing or invalid authorization header"
}
```

## Frontend Implementation

### Check User Role
```typescript
const user = JSON.parse(localStorage.getItem('user') || '{}');

if (user.role === 'incharge') {
  // Show only unit-specific features
  // Disable validation for staff outside unit
} else if (user.role === 'admin' || user.role === 'accountant') {
  // Show all features
}
```

### Filter Staff List
The backend automatically filters, but frontend can also:
```typescript
const response = await fetch('/api/v1/validation/staff', {
  headers: { Authorization: `Bearer ${token}` }
});

// For incharge, this will only return their unit's staff
// For admin/accountant, this returns all staff
```

## Summary

✓ **Admin/Accountant**: Full access to all staff and validations
✓ **Incharge**: Restricted to their unit only
✓ **Staff**: Read-only access to own information
✓ **Security**: Database-level filtering + validation checks
✓ **JWT**: Contains role and unit_id for authorization
✓ **Error Handling**: Clear 403 responses for unauthorized actions
