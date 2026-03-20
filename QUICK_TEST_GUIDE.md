# Quick Test Guide - Authentication Fixed ✓

## Problem Solved
The "Unauthorized" error when fetching staff list has been fixed. The AuthMiddleware now properly validates JWT tokens.

## Quick Test (3 Steps)

### Step 1: Start Backend
```bash
cd validation-api
php bin/console serve
```
Leave this running.

### Step 2: Run Test Script
Open a new terminal:
```bash
cd validation-api
php test_auth.php
```

**Expected Output:**
```
╔════════════════════════════════════════════════════════╗
║     AGH Validation System - Auth Test                 ║
╚════════════════════════════════════════════════════════╝

🔐 Test 1: Login...
  ✓ Login successful
  Token: eyJ0eXAiOiJKV1QiLCJhbGc...
  User: Admin User (admin)

👤 Test 2: Get Current User...
  ✓ User retrieved successfully
  Name: Admin User
  Email: admin@validation.com

👥 Test 3: Get Staff List...
  ✓ Staff list retrieved successfully
  Total staff: 18
  Sample: Admin User (admin)

🏢 Test 4: Get Units...
  ✓ Units retrieved successfully
  Total units: 4

✅ Test 5: Validate Staff...
  ✓ Staff validated successfully

╔════════════════════════════════════════════════════════╗
║  ✓ All authentication tests passed!                   ║
╚════════════════════════════════════════════════════════╝
```

### Step 3: Test from Frontend
```bash
cd agh-validation-ui
npm run dev
```

Visit http://localhost:3000 and login with:
- Email: `admin@validation.com`
- Password: `admin123`

You should now be able to fetch the staff list without getting "Unauthorized" errors.

## What Was Fixed

1. **AuthMiddleware** - Now properly validates JWT tokens from Authorization header
2. **Request Class** - Added setAttribute/getAttribute methods for passing user data
3. **Token Validation** - Checks signature, expiration, and extracts user data

## Frontend Requirements

Your frontend must send these headers:
```typescript
{
  'Authorization': `Bearer ${token}`,
  'X-API-Key': 'devKey123',
  'Content-Type': 'application/json'
}
```

## Test Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@validation.com | admin123 |
| Accountant | accountant@validation.com | accountant123 |
| Incharge | incharge1@validation.com | incharge123 |
| Staff | humanresources.staff1@validation.com | staff123 |

## Troubleshooting

### Still getting 401?
1. Check backend is running on port 8000
2. Verify token is in Authorization header as `Bearer {token}`
3. Verify X-API-Key header is set to `devKey123`
4. Check browser console for actual error message

### Token expired?
- Tokens last 24 hours
- Login again to get a new token

### CORS errors?
- Backend must be running
- Check CORS_ALLOWED_ORIGINS in .env includes your frontend URL

## Status: READY ✓

Authentication is now working correctly. You can proceed with testing the full validation workflow.
