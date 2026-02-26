# Session-Based File Access for Signatures ✓

## Problem
Getting 401 Unauthorized when trying to access signature files even when logged in, because:
- The `/api/v1/uploads/file/{id}` endpoint requires Bearer token in Authorization header
- Browser `<img>` tags and direct access don't send Authorization headers
- Session cookies alone aren't enough for the API endpoint

## Solution
Created a new endpoint `/api/v1/uploads/secure/{id}` that uses session-based authentication, which works in browsers with cookies.

## Three File Access Endpoints

### 1. Public Endpoint (No Auth)
**URL:** `/api/v1/uploads/public/{id}`
**Auth:** None required
**Allowed:** Profile pictures only
**Use Case:** Display profile pictures in `<img>` tags

```html
<img src="http://localhost:8000/api/v1/uploads/public/5" alt="Profile">
```

### 2. Secure Endpoint (Session Auth)
**URL:** `/api/v1/uploads/secure/{id}`
**Auth:** Session cookie (automatic in browser)
**Allowed:** All file types for logged-in users
**Use Case:** Display signatures and documents in browser for logged-in users

```html
<!-- Works if user is logged in (session cookie sent automatically) -->
<img src="http://localhost:8000/api/v1/uploads/secure/10" alt="Signature">
```

### 3. API Endpoint (Bearer Token)
**URL:** `/api/v1/uploads/file/{id}`
**Auth:** Bearer token in Authorization header
**Allowed:** All file types
**Use Case:** API access, mobile apps, external integrations

```javascript
fetch('http://localhost:8000/api/v1/uploads/file/10', {
  headers: {
    'Authorization': 'Bearer ' + token
  }
})
```

## URL Generation by Document Type

### UploadService now returns different URLs:

```php
// Profile pictures → Public endpoint
if ($docType === 'profile_picture') {
    $fullUrl = $appUrl . '/api/v1/uploads/public/' . $uploadId;
}
// Signatures → Secure session-based endpoint
elseif (in_array($docType, ['signature', 'staff_signature'])) {
    $fullUrl = $appUrl . '/api/v1/uploads/secure/' . $uploadId;
}
// Documents → API endpoint with Bearer token
else {
    $fullUrl = $appUrl . '/api/v1/uploads/file/' . $uploadId;
}
```

## Upload Responses

### Upload Profile Picture
```json
{
  "success": true,
  "upload_id": 5,
  "url": "http://localhost:8000/api/v1/uploads/public/5",
  "doc_name": "photo.jpg"
}
```

### Upload Signature
```json
{
  "success": true,
  "upload_id": 10,
  "url": "http://localhost:8000/api/v1/uploads/secure/10",
  "doc_name": "signature.png"
}
```

### Upload Document
```json
{
  "success": true,
  "upload_id": 15,
  "url": "http://localhost:8000/api/v1/uploads/file/15",
  "doc_name": "report.pdf"
}
```

## Security Features

### Secure Endpoint Protection
- Checks for active session
- Returns 401 if not logged in
- Works with browser cookies automatically
- No Bearer token needed

### Implementation
```php
public function getSecureFile(Request $request, Response $response, array $params): Response
{
    // Check if user is authenticated via session
    $user = \App\Core\Session::get('user');
    if (!$user) {
        return 401 Unauthorized;
    }
    
    // Serve the file
    // ...
}
```

## Frontend Usage

### Display Signature (Logged-in User)
```html
<!-- Simple - works if user is logged in -->
<img src="http://localhost:8000/api/v1/uploads/secure/10" alt="Signature">
```

```javascript
// React/Vue/Angular
<img src={staff.signature_url} alt="Signature" />
```

### Display Profile Picture (Anyone)
```html
<!-- Works for everyone, no login needed -->
<img src="http://localhost:8000/api/v1/uploads/public/5" alt="Profile">
```

## Access Control Matrix

| Document Type | Public | Secure (Session) | API (Bearer) |
|--------------|--------|------------------|--------------|
| profile_picture | ✅ Yes | ✅ Yes | ✅ Yes |
| signature | ❌ No | ✅ Yes | ✅ Yes |
| staff_signature | ❌ No | ✅ Yes | ✅ Yes |
| student_document | ❌ No | ✅ Yes | ✅ Yes |
| staff_document | ❌ No | ✅ Yes | ✅ Yes |

## Benefits

1. **Browser Compatible**: Signatures work in `<img>` tags for logged-in users
2. **Secure**: Requires active session to access
3. **No Token Needed**: Session cookies handled automatically by browser
4. **Flexible**: Three endpoints for different use cases
5. **Audit Trail**: Session-based access can be logged

## Testing

### Test 1: Upload Signature
```bash
curl -X POST "http://localhost:8000/api/v1/uploads" \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@signature.png" \
  -F "doc_type=signature"

# Returns URL: http://localhost:8000/api/v1/uploads/secure/10
```

### Test 2: Access Signature (Logged In)
```bash
# In browser, if logged in, this will work:
http://localhost:8000/api/v1/uploads/secure/10

# Or with curl using session cookie:
curl "http://localhost:8000/api/v1/uploads/secure/10" \
  --cookie "PHPSESSID=your_session_id"
```

### Test 3: Access Signature (Not Logged In)
```bash
# Without session - should fail
curl "http://localhost:8000/api/v1/uploads/secure/10"

# Response: 401 Unauthorized
```

### Test 4: Try Public Access (Should Fail)
```bash
curl "http://localhost:8000/api/v1/uploads/public/10"

# Response: 403 Forbidden (signatures blocked)
```

## Route Configuration

```php
// Public - no auth
$router->getApi('v1', '/uploads/public/{id}', [UploadController::class, 'getPublicFile'], [RateLimiter::class]);

// Secure - session auth (no middleware, checks session internally)
$router->get('/api/v1/uploads/secure/{id}', [UploadController::class, 'getSecureFile']);

// API - Bearer token auth
$router->getApi('v1', '/uploads/file/{id}', [UploadController::class, 'getFile'], [APIKeyMiddleware::class, AuthMiddleware::class]);
```

## Files Modified
- `App/Controllers/Api/v1/UploadController.php` - Added getSecureFile() method
- `App/Services/UploadService.php` - Updated URL generation logic
- `routes/api.php` - Added secure endpoint route

## Use Cases

### Staff Profile with Signature
```javascript
// Staff profile includes signature URL
{
  "staff_id": "STF001",
  "full_name": "Jane Smith",
  "signature_url": "http://localhost:8000/api/v1/uploads/secure/10"
}

// Display in browser (user must be logged in)
<img src={staff.signature_url} alt="Signature" />
```

### Document Generation
```javascript
// When generating a document, fetch signature
const response = await fetch(staff.signature_url);
const blob = await response.blob();
const base64 = await blobToBase64(blob);

// Embed in PDF or document
```

## Security Notes

- Signatures are never publicly accessible
- Session-based endpoint works in browser for logged-in users
- API endpoint still available for programmatic access with Bearer token
- All access methods are secure and require authentication
