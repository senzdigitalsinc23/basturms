# Debug Authorization Header Issue

## Quick Test Steps

### Step 1: Test Direct API Call (Browser)

1. Make sure backend is running: `php bin/console serve`
2. Open in browser: http://localhost:8000/test-auth.html
3. Click "Login" button
4. Click "Check What Headers Backend Receives"
5. Click "Get Staff List"

This will show you exactly what headers the backend is receiving.

### Step 2: Check Auth Debug Log

After making a request, check the log file:
```bash
cat validation-api/storage/logs/auth_debug.log
```

This will show you what the AuthMiddleware is seeing.

### Step 3: Test with cURL

```bash
# 1. Login
curl -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@validation.com","password":"admin123"}'

# Copy the token from response, then:

# 2. Get staff (replace YOUR_TOKEN_HERE)
curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "X-API-Key: devKey123" \
  -v
```

The `-v` flag will show you all headers being sent.

## Common Issues

### Issue 1: Frontend calling Next.js API route instead of backend directly

**Check:** Are you calling `/api/staff` or `http://localhost:8000/api/v1/validation/staff`?

If calling `/api/staff`, the Next.js API route might not be forwarding headers correctly.

**Solution:** Update your frontend to call the backend directly:

```typescript
// Instead of:
fetch('/api/staff', { headers: { Authorization: `Bearer ${token}` } })

// Use:
fetch('http://localhost:8000/api/v1/validation/staff', { 
  headers: { 
    Authorization: `Bearer ${token}`,
    'X-API-Key': 'devKey123'
  } 
})
```

### Issue 2: Authorization header not being sent

**Check browser console:**
- Open DevTools → Network tab
- Make the request
- Click on the request
- Check "Request Headers" section
- Verify "Authorization: Bearer ..." is present

### Issue 3: CORS blocking headers

If you see CORS errors, the Authorization header might be blocked.

**Check backend .env:**
```env
CORS_ALLOWED_ORIGINS="http://localhost:3000"
```

### Issue 4: Token not stored correctly

**Check localStorage:**
```javascript
// In browser console:
console.log(localStorage.getItem('token'));
```

If null or undefined, the token wasn't stored after login.

## Debugging Your Frontend

### Check where you're calling the API

Find where you're fetching staff in your frontend code:

```typescript
// Look for something like:
const response = await fetch('/api/staff', ...);
// or
const response = await apiClient.getStaff(token);
```

### Verify token is being passed

Add console.log to see what's being sent:

```typescript
console.log('Token:', token);
console.log('Headers:', {
  'Authorization': `Bearer ${token}`,
  'X-API-Key': 'devKey123'
});

const response = await fetch(url, { headers: ... });
```

## Expected vs Actual

### Expected Request Headers:
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
X-API-Key: devKey123
Content-Type: application/json
```

### If you see this error:
```json
{"success": false, "message": "Missing or invalid authorization header"}
```

It means the Authorization header is either:
1. Not being sent at all
2. Not in the correct format (must be "Bearer {token}")
3. Being stripped by a proxy/middleware

## Next Steps

1. Run the browser test (test-auth.html)
2. Check the auth_debug.log file
3. Share the results so we can see what's happening

## Files to Check

1. `agh-validation-ui/lib/api.ts` - API client
2. `agh-validation-ui/app/api/staff/route.ts` - Next.js API route
3. Your component that calls getStaff()
4. Browser DevTools Network tab
5. `validation-api/storage/logs/auth_debug.log`
