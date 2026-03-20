# HR Incharge - Admin Access Rights ✓

## Overview

The HR (Human Resources) incharge has been granted full admin access rights, while other incharges remain restricted to their units.

## Access Rights by Role

### HR Incharge (Human Resources Unit)
**Full Admin Access**
- ✓ View all staff across all units
- ✓ Validate any staff member
- ✓ View all validation records
- ✓ Same permissions as Admin and Accountant

**Credentials:**
- Email: `incharge1@validation.com`
- Password: `incharge123`
- Unit: Human Resources

### Other Incharges (Finance, IT, Operations)
**Unit-Restricted Access**
- ✓ View only staff in their unit
- ✓ Validate only staff in their unit
- ✓ View only validations for their unit
- ✗ Cannot see or validate staff from other units

**Credentials:**
- Finance Incharge: `incharge2@validation.com` / `incharge123`
- IT Incharge: `incharge3@validation.com` / `incharge123`
- Operations Incharge: `incharge4@validation.com` / `incharge123`

## Implementation Details

### How It Works

The system checks if an incharge belongs to the "Human Resources" unit:

```php
// Check if user is HR incharge
$stmt = $this->db->prepare("
    SELECT u.name as unit_name
    FROM validation_staff s
    LEFT JOIN units u ON s.unit_id = u.id
    WHERE s.id = :user_id
");
$stmt->execute(['user_id' => $userId]);
$userUnit = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userUnit && $userUnit['unit_name'] === 'Human Resources') {
    // Grant full access (like admin)
} else {
    // Restrict to unit only
}
```

### Files Modified

1. **ValidationStaffController.php** - `getAllStaff()` method
   - HR incharge sees all staff
   - Other incharges see only their unit's staff

2. **ValidationController.php** - `validate()` method
   - HR incharge can validate any staff
   - Other incharges can only validate their unit's staff

3. **ValidationController.php** - `getValidations()` method
   - HR incharge sees all validation records
   - Other incharges see only their unit's validations

## Testing

### Test HR Incharge (Full Access)

```bash
# 1. Login as HR incharge
curl -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"incharge1@validation.com","password":"incharge123"}'

# 2. Get all staff (should return ALL staff, not just HR unit)
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123"

# Expected: All 18 staff members across all units

# 3. Validate staff from any unit (should work)
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123" \
  -H "Content-Type: application/json" \
  -d '{"staffIds":[1,2,3,4,5],"month":"March","year":2026}'

# Expected: Success (can validate staff from any unit)

# 4. Get all validations (should return all)
curl -X GET "http://localhost:8000/api/v1/validations?month=March&year=2026" \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123"

# Expected: All validation records across all units
```

### Test Other Incharge (Unit-Restricted)

```bash
# 1. Login as Finance incharge
curl -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"incharge2@validation.com","password":"incharge123"}'

# 2. Get staff (should return only Finance unit staff)
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123"

# Expected: Only Finance unit staff (about 4 staff members)

# 3. Try to validate staff from another unit (should fail)
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123" \
  -H "Content-Type: application/json" \
  -d '{"staffIds":[1,2,3],"month":"March","year":2026}'

# Expected: 403 Forbidden - "You can only validate staff in your unit"

# 4. Get validations (should return only Finance unit validations)
curl -X GET "http://localhost:8000/api/v1/validations?month=March&year=2026" \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123"

# Expected: Only validations for Finance unit staff
```

## Frontend Testing

### Test as HR Incharge

1. Login at http://localhost:3000
2. Email: `incharge1@validation.com`
3. Password: `incharge123`
4. **Expected:**
   - See all 18 staff members (not just HR unit)
   - Can validate any staff member
   - See all validation records
   - Dashboard looks like admin dashboard

### Test as Finance Incharge

1. Login at http://localhost:3000
2. Email: `incharge2@validation.com`
3. Password: `incharge123`
4. **Expected:**
   - See only Finance unit staff (~4 members)
   - Can only validate Finance unit staff
   - See only Finance unit validations
   - Dashboard shows unit-restricted view

## Access Matrix

| Role | View All Staff | Validate Any Staff | View All Validations |
|------|---------------|-------------------|---------------------|
| **Admin** | ✓ | ✓ | ✓ |
| **Accountant** | ✓ | ✓ | ✓ |
| **HR Incharge** | ✓ | ✓ | ✓ |
| **Finance Incharge** | ✗ (Finance only) | ✗ (Finance only) | ✗ (Finance only) |
| **IT Incharge** | ✗ (IT only) | ✗ (IT only) | ✗ (IT only) |
| **Operations Incharge** | ✗ (Operations only) | ✗ (Operations only) | ✗ (Operations only) |
| **Staff** | ✗ (Self only) | ✗ | ✗ (Self only) |

## Why HR Incharge Gets Admin Access

HR (Human Resources) department typically needs to:
- Oversee all employees across departments
- Handle company-wide HR policies
- Manage employee records for all units
- Process validations for the entire organization
- Generate reports across all departments

Therefore, HR incharge is granted the same access level as admin and accountant.

## Database Query Logic

### Staff List Query

```sql
-- For HR Incharge
SELECT s.*, u.name as unitName
FROM validation_staff s
LEFT JOIN units u ON s.unit_id = u.id
WHERE s.deleted_at IS NULL
ORDER BY s.name

-- For Other Incharges
SELECT s.*, u.name as unitName
FROM validation_staff s
LEFT JOIN units u ON s.unit_id = u.id
WHERE s.deleted_at IS NULL 
AND s.unit_id = (SELECT unit_id FROM validation_staff WHERE id = :user_id)
ORDER BY s.name
```

### Validation Security Check

```php
// HR Incharge: Skip security check (full access)
if ($userUnit['unit_name'] === 'Human Resources') {
    // No restrictions
}

// Other Incharges: Verify staff belong to their unit
else {
    // Check if all staffIds belong to incharge's unit
    // Return 403 if any staff is outside their unit
}
```

## Summary

✓ **HR Incharge** (`incharge1@validation.com`) has full admin access
✓ **Other Incharges** remain restricted to their units
✓ **Security checks** properly distinguish between HR and non-HR incharges
✓ **All endpoints** (staff list, validation, validation history) respect this logic
✓ **Frontend** will automatically show appropriate access based on backend response

The system now properly grants HR incharge the same privileges as admin while maintaining unit restrictions for other incharges.
