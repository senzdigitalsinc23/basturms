# Quick Reference: Fetch All Staff Including Inactive

## The Solution

The existing `/api/v1/staff` endpoint now supports fetching all staff regardless of status.

## How to Use

### Get ALL Staff (Active + Inactive + Suspended + Terminated)
```bash
GET /api/v1/staff?status=all
```

### With Pagination
```bash
GET /api/v1/staff?status=all&page=1&limit=20
```

### Example cURL Request
```bash
curl -X GET "http://localhost:8000/api/v1/staff?status=all&page=1&limit=10" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

### Example JavaScript/Fetch
```javascript
const response = await fetch('http://localhost:8000/api/v1/staff?status=all&page=1&limit=10', {
  method: 'GET',
  headers: {
    'X-API-Key': 'YOUR_API_KEY',
    'Authorization': 'Bearer YOUR_JWT_TOKEN',
    'Content-Type': 'application/json'
  }
});

const data = await response.json();
console.log('Total staff:', data.pagination.total);
console.log('Staff list:', data.data);
```

## Other Status Options

| Parameter | Description |
|-----------|-------------|
| `status=active` | Only active staff (default) |
| `status=inactive` | Only inactive staff |
| `status=suspended` | Only suspended staff |
| `status=terminated` | Only terminated staff |
| `status=all` | All staff regardless of status |

## Response Structure

```json
{
  "success": true,
  "message": "Staff list retrieved successfully",
  "data": [
    {
      "staff_id": "LBAST26001",
      "first_name": "John",
      "last_name": "Doe",
      "other_name": "Middle",
      "email": "john.doe@example.com",
      "phone": "+233123456789",
      "status": "active",
      "date_of_joining": "2026-01-15",
      "roles": [...],
      "classes_assigned": [...],
      "subjects_assigned": [...]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "total_pages": 5
  }
}
```

## Important Notes

1. **Archived staff are NEVER included** - Staff with `is_archived = 1` are excluded from all queries
2. **Default behavior unchanged** - Without the `status` parameter, only active staff are returned
3. **Fully backward compatible** - Existing API consumers continue to work without changes
4. **Authentication required** - All requests must include valid API key and JWT token

## Testing

Use the provided test script:
```bash
php test_staff_all_endpoint.php
```

(Remember to update API_KEY and JWT_TOKEN in the script first)
