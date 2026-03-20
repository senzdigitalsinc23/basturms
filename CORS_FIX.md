# CORS Issue Fixed

## Problem
The frontend was unable to import staff due to two issues:
1. CORS headers were not being sent in API responses
2. Frontend was calling the wrong URL for staff import

## Changes Made

### Backend (validation-api)
**File: `Core/Response.php`**
- Modified the `json()` method to automatically add CORS headers based on the `CORS_ALLOWED_ORIGINS` environment variable
- Headers are only added if the request origin matches the allowed origins
- This ensures CORS headers are sent even when controllers call `$response->json()` directly

### Frontend (agh-validation-ui)
**File: `lib/api.ts`**
- Fixed `importStaff()` method to use `${this.baseUrl}/staff/import` instead of `/api/staff/import`
- This ensures requests go to the PHP backend (http://localhost:8000) instead of Next.js server (http://localhost:3000)

## Testing
You can test the CORS configuration with:
```powershell
$headers = @{ 
    "Origin" = "http://localhost:3000"
    "Access-Control-Request-Method" = "POST"
    "Access-Control-Request-Headers" = "Content-Type,Authorization,X-API-Key"
}
Invoke-WebRequest -Uri "http://localhost:8000/api/v1/staff/import" -Method OPTIONS -Headers $headers -UseBasicParsing
```

Expected response headers:
- `Access-Control-Allow-Origin: http://localhost:3000`
- `Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN, X-API-KEY, X-Api-Key`
- `Access-Control-Allow-Credentials: true`

## Next Steps
1. Restart the Next.js development server to pick up the changes
2. Login to the admin dashboard
3. Click "Import Staff" button
4. Upload a CSV file with the correct format
5. Verify staff are imported successfully
