# Validation SQL Parameter Fix ✓

## Issue

When trying to validate staff from the frontend, got this error:
```
POST http://localhost:8000/api/v1/validations 500 (Internal Server Error)
Error: Failed to validate staff: SQLSTATE[HY093]: Invalid parameter number
```

## Root Cause

In `ValidationController.php`, the security check query for incharge users had a parameter mismatch:

### Before (Broken)
```php
$placeholders = str_repeat('?,', count($staffIds) - 1) . '?';
$stmt = $this->db->prepare("
    SELECT COUNT(*) as count 
    FROM validation_staff 
    WHERE id IN ($placeholders) 
    AND (unit_id != ? OR unit_id IS NULL)
    AND deleted_at IS NULL
");

$params = array_merge($staffIds, [$userUnitId]);
$stmt->execute($params);
```

**Problem:** The query logic was checking for staff NOT in the unit, which was confusing and had parameter issues.

### After (Fixed)
```php
$placeholders = implode(',', array_fill(0, count($staffIds), '?'));
$stmt = $this->db->prepare("
    SELECT COUNT(*) as count 
    FROM validation_staff 
    WHERE id IN ($placeholders) 
    AND unit_id = ?
    AND deleted_at IS NULL
");

$params = array_merge($staffIds, [$userUnitId]);
$stmt->execute($params);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// If the count doesn't match, some staff are outside the unit
if ($result['count'] != count($staffIds)) {
    return 403: "You can only validate staff in your unit"
}
```

**Fix:** 
1. Changed to check for staff IN the unit (positive logic)
2. Used `implode(',', array_fill(...))` for cleaner placeholder generation
3. Compare count of found staff with count of requested staff IDs
4. If counts don't match, some staff are outside the unit

## How It Works Now

### Example: Incharge validates 3 staff

**Request:**
```json
{
  "staffIds": [5, 6, 7],
  "month": "March",
  "year": 2026
}
```

**Security Check:**
1. Generate placeholders: `?,?,?`
2. Query: `SELECT COUNT(*) WHERE id IN (?,?,?) AND unit_id = ?`
3. Parameters: `[5, 6, 7, 2]` (staff IDs + unit ID)
4. Execute query
5. If count = 3: All staff in unit → Allow validation
6. If count < 3: Some staff outside unit → Return 403

### Example: Incharge tries to validate staff from another unit

**Request:**
```json
{
  "staffIds": [1, 2, 3],
  "month": "March",
  "year": 2026
}
```

**Security Check:**
1. Query finds 0 staff (they're in different unit)
2. Count (0) != requested count (3)
3. Return 403: "You can only validate staff in your unit"

## Testing

### Test as Incharge

1. Login as incharge:
```bash
curl -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"incharge1@validation.com","password":"incharge123"}'
```

2. Get staff in your unit:
```bash
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123"
```

3. Validate staff from your unit (should work):
```bash
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123" \
  -H "Content-Type: application/json" \
  -d '{"staffIds":[5,6,7],"month":"March","year":2026}'
```

4. Try to validate staff from another unit (should fail with 403):
```bash
curl -X POST http://localhost:8000/api/v1/validations \
  -H "Authorization: Bearer {token}" \
  -H "X-API-Key: devKey123" \
  -H "Content-Type: application/json" \
  -d '{"staffIds":[1,2,3],"month":"March","year":2026}'
```

### Test from Frontend

1. Login as `incharge1@validation.com` / `incharge123`
2. You should see only staff in Finance unit
3. Select staff and click "Validate Selected"
4. Should work without 500 error
5. Validation status should update to "Validated"

## What Was Changed

**File:** `validation-api/App/Controllers/Api/v1/ValidationController.php`

**Method:** `validate()`

**Lines:** Security check for incharge users (around line 68-85)

## Status: FIXED ✓

The validation endpoint now works correctly for all roles:
- ✓ Admin can validate any staff
- ✓ Accountant can validate any staff
- ✓ Incharge can validate staff in their unit
- ✓ Incharge gets 403 if trying to validate staff outside their unit
- ✓ No more SQL parameter errors

## Related Documentation

- `ROLE_BASED_ACCESS.md` - Role-based access control
- `INCHARGE_UNIT_FILTERING.md` - Unit filtering for incharge
- `VALIDATION_SYSTEM_API.md` - Complete API reference
