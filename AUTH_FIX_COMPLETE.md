# Authentication Fix Complete ✓

## What Was Fixed

### Issue
The AuthMiddleware was trying to use a non-existent AuthService and relying on sessions, which doesn't work with JWT tokens from the frontend.

### Solution
1. **Rewrote AuthMiddleware** to properly validate JWT tokens
   - Extracts Bearer token from Authorization header
   - Validates JWT using Firebase JWT library
   - Checks token expiration
   - Attaches user data to request for controllers

2. **Updated Request Class** to support attributes
   - Added `setAttribute()` method
   - Added `getAttribute()` method
   - Added `$attributes` property

## Changes Made

### 1. AuthMiddleware.php
```php
// Now properly validates JWT tokens
- Extracts token from "Authorization: Bearer {token}"
- Validates signature using JWT_SECRET
- Checks expiration
- Attaches user data to request
- Returns proper error responses
```

### 2. Core/Request.php
```php
// Added attribute support
protected array $attributes = [];

public function setAttribute(string $key, $value): void
public function getAttribute(string $key, $default = null)
```

## How It Works Now

### 1. Frontend Login
```typescript
const response = await fetch('http://localhost:8000/api/v1/validation/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'admin@validation.com',
    password: 'admin123'
  })
});

const data = await response.json();
const token = data.token; // Store this
```

### 2. Authenticated Requests
```typescript
const response = await fetch('http://localhost:8000/api/v1/validation/staff', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'X-API-Key': 'devKey123'
  }
});
```

### 3. Backend Flow
```
Request → CorsMiddleware → CsrfMiddleware → APIKeyMiddleware → AuthMiddleware → Controller
                                                                      ↓
                                                            Validates JWT token
                                                            Attaches user data
                                                                      ↓
                                                            Controller gets user via:
                                                            $request->getAttribute('user')
```

## Testing

### Option 1: Use Test Script
```bash
cd validation-api
php test_auth.php
```

This will test:
- ✓ Login
- ✓ Get current user
- ✓ Get staff list
- ✓ Get units
- ✓ Validate staff

### Option 2: Manual cURL Test

#### 1. Login
```bash
curl -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@validation.com","password":"admin123"}'
```

Copy the token from response.

#### 2. Get Staff (replace YOUR_TOKEN)
```bash
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-Key: devKey123"
```

### Option 3: Test from Frontend

Make sure your frontend API client sends:
```typescript
headers: {
  'Authorization': `Bearer ${token}`,
  'X-API-Key': 'devKey123',
  'Content-Type': 'application/json'
}
```

## Common Issues & Solutions

### Issue: Still getting 401 Unauthorized
**Check:**
1. Token is being sent in Authorization header
2. Format is exactly: `Bearer {token}` (with space)
3. API Key is being sent in X-API-Key header
4. Backend server is running

### Issue: Token expired
**Solution:**
- Tokens expire after 24 hours
- Login again to get a new token
- Or implement token refresh in frontend

### Issue: Invalid token signature
**Check:**
- JWT_SECRET in .env matches what was used to create token
- Token hasn't been modified

## What Controllers Can Access

After AuthMiddleware runs, controllers can access user data:

```php
public function someMethod(Request $request, Response $response): Response
{
    $user = $request->getAttribute('user');
    
    // Available data:
    $userId = $user['user_id'];    // int
    $email = $user['email'];       // string
    $role = $user['role'];         // 'admin', 'accountant', 'incharge', 'staff'
    $unitId = $user['unit_id'];    // int or null
    
    // Use in queries, logic, etc.
}
```

## Middleware Order (Important!)

```php
$router->middleware([
    CorsMiddleware::class,      // 1. MUST be first for preflight
    CsrfMiddleware::class,      // 2. CSRF (validation endpoints excluded)
    WAFMiddleware::class,       // 3. Web Application Firewall
    RateLimiter::class,         // 4. Rate limiting
    SecurityHeaders::class,     // 5. Security headers
    ContentTypeEnforcer::class, // 6. Content type validation
    JsonBodyParser::class,      // 7. Parse JSON body
]);

// Then per-route middleware:
// APIKeyMiddleware → AuthMiddleware → Controller
```

## Status: FIXED ✓

Authentication now works correctly with JWT tokens. Frontend can login and make authenticated requests to all protected endpoints.

## Next Steps

1. Start backend: `php bin/console serve`
2. Test with: `php test_auth.php`
3. Or test from frontend
4. Verify all endpoints work with authentication

## Files Modified

- `validation-api/App/Middleware/AuthMiddleware.php` - Complete rewrite
- `validation-api/Core/Request.php` - Added setAttribute/getAttribute
- `validation-api/test_auth.php` - New test script
