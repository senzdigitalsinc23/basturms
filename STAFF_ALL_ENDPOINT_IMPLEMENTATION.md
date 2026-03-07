# Staff "All Status" Endpoint Implementation

## Overview
Enhanced the existing `/api/v1/staff` endpoint to support fetching all staff members regardless of their status by passing `status=all` as a query parameter.

## Changes Made

### 1. StaffRepository.php
Updated all staff retrieval methods to support `status='all'` parameter:

- `getAllStaff()` - Main method for fetching staff list
- `countStaff()` - Count total staff
- `getStaffByRole()` - Fetch staff by role
- `countStaffByRole()` - Count staff by role
- `getStaffByClass()` - Fetch staff by class
- `countStaffByClass()` - Count staff by class
- `getStaffBySubject()` - Fetch staff by subject
- `countStaffBySubject()` - Count staff by subject
- `searchStaff()` - Search staff by name/email
- `countSearchResults()` - Count search results

**Implementation Pattern:**
```php
// Before: Hardcoded status filter
WHERE s.status = ? AND s.is_archived = 0

// After: Conditional status filter
WHERE s.is_archived = 0
// Only add status filter if not 'all'
if ($status !== 'all') {
    $sql .= " AND s.status = ?";
    $params[] = $status;
}
```

### 2. StaffController.php
Updated OpenAPI documentation for the `/api/v1/staff` endpoint:

- Added enum values for status parameter: `["active", "inactive", "suspended", "terminated", "all"]`
- Clarified that `status=all` fetches staff with all statuses
- Default remains `active` for backward compatibility

## Usage

### Fetch Active Staff Only (Default)
```bash
GET /api/v1/staff?page=1&limit=10
GET /api/v1/staff?page=1&limit=10&status=active
```

### Fetch ALL Staff (Including Inactive)
```bash
GET /api/v1/staff?page=1&limit=10&status=all
```

### Fetch Specific Status
```bash
GET /api/v1/staff?page=1&limit=10&status=inactive
GET /api/v1/staff?page=1&limit=10&status=suspended
GET /api/v1/staff?page=1&limit=10&status=terminated
```

## Response Format
```json
{
  "success": true,
  "message": "Staff list retrieved successfully",
  "data": [
    {
      "staff_id": "LBAST26001",
      "first_name": "John",
      "last_name": "Doe",
      "status": "active",
      "roles": [...],
      "classes_assigned": [...],
      "subjects_assigned": [...]
    },
    {
      "staff_id": "LBAST26002",
      "first_name": "Jane",
      "last_name": "Smith",
      "status": "inactive",
      "roles": [...],
      "classes_assigned": [...],
      "subjects_assigned": [...]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 25,
    "total_pages": 3
  }
}
```

## Testing

A test script has been created: `test_staff_all_endpoint.php`

To test:
1. Update API_KEY and JWT_TOKEN in the test script
2. Ensure your API server is running
3. Run: `php test_staff_all_endpoint.php`

## Backward Compatibility

✓ Fully backward compatible
- Default behavior unchanged (returns active staff only)
- Existing API consumers will continue to work without modifications
- New `status=all` parameter is optional

## Security Considerations

- All existing authentication and authorization middleware remain in place
- No changes to access control
- Archived staff (`is_archived = 1`) are still excluded from all queries
- Only non-archived staff with various statuses can be retrieved

## Benefits

1. **Single Endpoint**: No need for a separate `/api/v1/staff/all` endpoint
2. **Consistency**: Same filtering pattern across all staff retrieval methods
3. **Flexibility**: Can filter by any status or fetch all statuses
4. **Clean Code**: Conditional SQL building keeps queries efficient
5. **Professional**: Follows REST API best practices

## Files Modified

- `App/Repositories/StaffRepository.php` - 10 methods updated
- `App/Controllers/Api/v1/StaffController.php` - OpenAPI documentation updated
- `test_staff_all_endpoint.php` - Test script created (new file)
- `STAFF_ALL_ENDPOINT_IMPLEMENTATION.md` - This documentation (new file)
