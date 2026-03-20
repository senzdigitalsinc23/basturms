# Incharge Unit Filtering - Complete ✓

## What Was Implemented

When an incharge user logs in, they can now only see and validate staff in their own unit.

## Changes Made

### 1. Staff List (Already Implemented) ✓
**File:** `ValidationStaffController.php`

Incharge users only see staff in their unit:
```php
if ($userRole === 'incharge') {
    // Filter by unit_id
    WHERE s.unit_id = (SELECT unit_id FROM validation_staff WHERE id = :user_id)
}
```

### 2. Validation Security Check (NEW) ✓
**File:** `ValidationController.php` - `validate()` method

Added security check to prevent incharge from validating staff outside their unit:
```php
if ($userRole === 'incharge' && $userUnitId) {
    // Check if any staff ID belongs to different unit
    if (staff outside unit detected) {
        return 403: "You can only validate staff in your unit"
    }
}
```

### 3. Validation History Filtering (NEW) ✓
**File:** `ValidationController.php` - `getValidations()` method

Incharge users only see validations for staff in their unit:
```php
if ($userRole === 'incharge' && $userUnitId) {
    WHERE s.unit_id = :unit_id
}
```

## How It Works

### Login Flow
1. Incharge logs in with credentials
2. Backend returns JWT token containing:
   ```json
   {
     "user_id": 5,
     "role": "incharge",
     "unit_id": 2,
     "email": "incharge1@validation.com"
   }
   ```
3. Frontend stores token

### Staff List Request
1. Frontend sends: `GET /api/v1/validation/staff` with token
2. Backend extracts role and unit_id from token
3. If role is "incharge", query filters by unit_id
4. Returns only staff in incharge's unit

### Validation Request
1. Frontend sends: `POST /api/v1/validations` with staffIds
2. Backend checks if user is incharge
3. If incharge, verifies all staffIds belong to their unit
4. If any staff outside unit: Returns 403 error
5. If all staff in unit: Proceeds with validation

### Validation History
1. Frontend sends: `GET /api/v1/validations?month=March&year=2026`
2. Backend checks if user is incharge
3. If incharge, filters validations by staff unit_id
4. Returns only validations for staff in their unit

## Test Scenarios

### Scenario 1: Incharge Views Staff List
```bash
# Login as incharge
curl -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"incharge1@validation.com","password":"incharge123"}'

# Get staff (will only return staff in their unit)
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123"
```

**Expected:** Only staff with same unit_id as incharge

### Scenario 2: Incharge Validates Own Unit Staff
```bash
# Validate staff in own unit (should succeed)
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123" \
  -H "Content-Type: application/json" \
  -d '{"staffIds":[5,6,7],"month":"March","year":2026}'
```

**Expected:** Success response

### Scenario 3: Incharge Tries to Validate Other Unit Staff
```bash
# Try to validate staff from different unit (should fail)
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123" \
  -H "Content-Type: application/json" \
  -d '{"staffIds":[1,2,3],"month":"March","year":2026}'
```

**Expected:** 403 Forbidden with message "You can only validate staff in your unit"

### Scenario 4: Incharge Views Validation History
```bash
# Get validations (will only return validations for their unit's staff)
curl -X GET "http://localhost:8000/api/v1/validations?month=March&year=2026" \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123"
```

**Expected:** Only validations where staff.unit_id matches incharge's unit_id

## Database Query Examples

### Staff List Query (Incharge)
```sql
SELECT s.id, s.name, s.email, s.role, s.unit_id, u.name as unitName
FROM validation_staff s
LEFT JOIN units u ON s.unit_id = u.id
WHERE s.deleted_at IS NULL 
AND s.unit_id = (SELECT unit_id FROM validation_staff WHERE id = 5)
ORDER BY s.name
```

### Validation Security Check
```sql
-- Check if any staff ID is outside incharge's unit
SELECT COUNT(*) as count 
FROM validation_staff 
WHERE id IN (1, 2, 3) 
AND (unit_id != 2 OR unit_id IS NULL)
AND deleted_at IS NULL
```

### Validation History Query (Incharge)
```sql
SELECT v.*, s.name as staffName, u.name as unitName
FROM validations v
INNER JOIN validation_staff s ON v.staff_id = s.id
LEFT JOIN units u ON s.unit_id = u.id
WHERE v.month = 'March' 
AND v.year = 2026
AND s.unit_id = 2
ORDER BY v.validated_at DESC
```

## Frontend Considerations

### Display Unit Name
Since incharge can only see their unit, you might want to display it:
```typescript
const user = JSON.parse(localStorage.getItem('user') || '{}');

if (user.role === 'incharge') {
  return (
    <div>
      <h2>My Unit: {user.unitName}</h2>
      <p>You can only validate staff in your unit</p>
    </div>
  );
}
```

### Disable Validation for Other Units
The backend already blocks it, but for better UX:
```typescript
const canValidate = (staffMember: Staff) => {
  if (user.role === 'incharge') {
    return staffMember.unitId === user.unitId;
  }
  return true; // Admin/Accountant can validate anyone
};
```

## Test Credentials

### Incharge Accounts
| Email | Password | Unit |
|-------|----------|------|
| incharge1@validation.com | incharge123 | Finance |
| incharge2@validation.com | incharge123 | Human Resources |
| incharge3@validation.com | incharge123 | IT Department |

### Admin (for comparison)
| Email | Password | Access |
|-------|----------|--------|
| admin@validation.com | admin123 | All units |

## Summary

✓ **Staff List**: Filtered by unit for incharge
✓ **Validation**: Security check prevents cross-unit validation
✓ **Validation History**: Filtered by unit for incharge
✓ **Error Handling**: Clear 403 response for unauthorized attempts
✓ **Database Security**: Queries include unit_id filtering
✓ **JWT Token**: Contains role and unit_id for authorization

The system now properly restricts incharge users to only view and validate staff within their assigned unit.
