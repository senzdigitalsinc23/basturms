# Troubleshooting "Missing or invalid authorization header"

## Quick Fix - Test These in Order

### 1. Test Direct Backend Call (Fastest Way to Diagnose)

Open this in your browser: **http://localhost:8000/test-auth.html**

This bypasses your frontend completely and tests the backend directly.

- Click "Login" → Should succeed
- Click "Check Headers" → Shows what backend receives
- Click "Get Staff" → Should work if auth is correct

**If this works:** The problem is in your frontend code.
**If this fails:** The problem is in the backend.

### 2. Check How Your Frontend is Calling the API

Look at your component code. Are you calling:

**Option A: Next.js API Route (Proxy)**
```typescript
fetch('/api/staff', { ... })  // Goes to Next.js first
```

**Option B: Direct Backend Call**
```typescript
fetch('http://localhost:8000/api/v1/validation/staff', { ... })  // Direct
```

**Most likely issue:** You're using Option A, and the Next.js API route isn't forwarding the Authorization header correctly.

### 3. Fix for Next.js API Route Issue

If you're using `/api/staff`, the issue is in `agh-validation-ui/app/api/staff/route.ts`.

The apiClient.getStaff() call needs to pass headers correctly. Let me check if fetch() in Node.js is handling headers properly.

**Quick Fix:** Call backend directly from frontend:

```typescript
// In your component:
const token = localStorage.getItem('token');

const response = await fetch('http://localhost:8000/api/v1/validation/staff', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'X-API-Key': 'devKey123',
    'Content-Type': 'application/json'
  }
});

const data = await response.json();
```

## Detailed Diagnosis

### Check 1: Is the token stored?

Open browser console:
```javascript
console.log(localStorage.getItem('token'));
```

If `null`, you need to store it after login:
```javascript
localStorage.setItem('token', data.token);
```

### Check 2: Is the token being sent?

Open DevTools → Network tab → Click the failed request → Headers tab

Look for:
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**If missing:** Your frontend isn't sending it.
**If present:** Backend isn't receiving it (check logs).

### Check 3: Backend logs

```bash
cat validation-api/storage/logs/auth_debug.log
```

This shows exactly what AuthMiddleware receives.

## Common Scenarios

### Scenario 1: Using Next.js API Routes

**Problem:** Next.js API route receives the header but doesn't forward it to backend.

**Solution:** Update `agh-validation-ui/lib/api.ts`:

```typescript
async getStaff(token: string): Promise<ApiResponse> {
  const response = await fetch(`${this.baseUrl}/validation/staff`, {
    method: 'GET',
    headers: this.getHeaders(token),
  });

  // Add error checking
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to fetch staff');
  }

  return response.json();
}
```

### Scenario 2: Token not stored after login

**Problem:** Login succeeds but token isn't saved.

**Solution:** In your login component:

```typescript
const handleLogin = async (email: string, password: string) => {
  const response = await apiClient.login(email, password);
  
  if (response.success && response.token) {
    // IMPORTANT: Store the token
    localStorage.setItem('token', response.token);
    localStorage.setItem('user', JSON.stringify(response.user));
    
    // Then redirect or update state
    router.push('/dashboard');
  }
};
```

### Scenario 3: Calling wrong endpoint

**Problem:** Calling `/api/staff` instead of backend URL.

**Solution:** Use the full backend URL:

```typescript
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

fetch(`${API_URL}/validation/staff`, { ... });
```

## Testing Commands

### Test 1: Login and get token
```bash
curl -X POST http://localhost:8000/api/v1/validation/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@validation.com","password":"admin123"}' \
  | jq -r '.token'
```

### Test 2: Use token to get staff
```bash
TOKEN="paste_token_here"

curl -X GET http://localhost:8000/api/v1/validation/staff \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-API-Key: devKey123" \
  -v
```

The `-v` flag shows all headers being sent and received.

## What to Share for Help

If still not working, share:

1. **Browser Network Tab Screenshot**
   - Show the request headers
   - Show the response

2. **Auth Debug Log**
   ```bash
   cat validation-api/storage/logs/auth_debug.log
   ```

3. **Your Frontend Code**
   - How you're calling the API
   - How you're storing/retrieving the token

4. **Test Results**
   - Does http://localhost:8000/test-auth.html work?
   - Does cURL work?

## Quick Checklist

- [ ] Backend is running on port 8000
- [ ] Frontend .env.local has correct API_URL and API_KEY
- [ ] Token is stored in localStorage after login
- [ ] Token is being sent in Authorization header
- [ ] Header format is exactly: `Bearer {token}` (with space)
- [ ] X-API-Key header is also being sent
- [ ] CORS is configured correctly in backend .env
- [ ] Test page (test-auth.html) works

## Most Likely Solution

Based on your setup, the most likely issue is that you're calling the Next.js API route (`/api/staff`) which then calls the backend, but the Authorization header isn't being forwarded properly.

**Quick fix:** Update your frontend to call the backend directly:

```typescript
// Change from:
const response = await fetch('/api/staff', { ... });

// To:
const response = await fetch('http://localhost:8000/api/v1/validation/staff', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'X-API-Key': 'devKey123'
  }
});
```

This bypasses the Next.js API route completely and calls the PHP backend directly.
