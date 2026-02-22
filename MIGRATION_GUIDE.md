# Migration Guide - Priority 1 Security Fixes

## Quick Start (5 Minutes)

### Step 1: Generate JWT Secret
```bash
# Generate a strong JWT secret
openssl rand -base64 32
```

### Step 2: Update .env File
```bash
# Copy example if you don't have .env
cp .env.example .env

# Edit .env and add your JWT secret
nano .env
```

Add this line with your generated secret:
```env
JWT_SECRET=your-generated-secret-from-step-1
```

### Step 3: Test the Application
```bash
# Start the development server
composer serve
# or
php -S localhost:8000 -t public
```

### Step 4: Verify Everything Works
Visit: http://localhost:8000

---

## Detailed Migration Steps

### 1. Backup Your Database
```bash
# Create a backup before making any changes
mysqldump -u root -p basturms > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Update Dependencies (if needed)
```bash
composer install
```

### 3. Configure Environment Variables

**Required Variables:**
```env
# CRITICAL - Application will not start without this
JWT_SECRET=your-strong-random-secret-min-32-chars

# Database
DB_HOST=localhost
DB_NAME=basturms
DB_USER=root
DB_PASS=your_password

# API Key
API_KEY=your-api-key
```

**Optional but Recommended:**
```env
# Email (for password resets)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### 4. Test Authentication Flow

**Test Login:**
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "email": "admin@example.com",
    "password": "your-password"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {...}
}
```

### 5. Test Student Creation

**Create Student:**
```bash
curl -X POST http://localhost:8000/api/v1/students/create \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -H "Authorization: Bearer your-jwt-token" \
  -d '{
    "student_info": {
      "first_name": "John",
      "last_name": "Doe",
      "dob": "2010-01-01",
      "gender": "Male"
    },
    "contact_address": {
      "email": "john.doe@example.com",
      "phone": "1234567890"
    },
    "admission_info": {
      "class_assigned": "P1A",
      "enrollment_date": "2024-01-01"
    }
  }'
```

**Check Logs for Generated Password:**
```bash
tail -f storage/logs/app.log | grep "Generated password"
```

### 6. Test Password Reset

**Request Password Reset:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/forgot-password \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "email": "user@example.com"
  }'
```

**Verify:**
- Check that email is sent (if configured)
- Check logs - should NOT contain plain text password
- Verify user can login with new password

### 7. Test SQL Injection Prevention

**Test Invalid ORDER BY:**
```bash
# This should be rejected gracefully
curl -X GET "http://localhost:8000/api/v1/students?orderBy=1;DROP%20TABLE%20students" \
  -H "X-API-Key: your-api-key" \
  -H "Authorization: Bearer your-jwt-token"
```

**Expected:** Application should use default safe column, not execute malicious SQL

---

## Breaking Changes

### 1. JWT_SECRET is Now Required

**Before:** Application would start with default secret  
**After:** Application throws exception if JWT_SECRET not set

**Migration:**
```bash
# Add to .env
JWT_SECRET=$(openssl rand -base64 32)
```

### 2. Student Passwords Changed

**Before:** Predictable pattern (FirstInitial + LastName + 123)  
**After:** Secure random 12-character passwords

**Impact:**
- New students get secure passwords
- Existing students keep their current passwords
- No database migration needed

**Action Required:**
- Implement password delivery mechanism (email/SMS)
- Update user documentation
- Consider forcing password reset for existing users

### 3. ORDER BY Validation

**Before:** Any column name accepted  
**After:** Only whitelisted columns allowed

**Impact:**
- Invalid column names default to safe column
- No breaking change for valid API calls

**Whitelisted Columns:**
- student_no
- first_name
- last_name
- other_name
- phone
- email
- class_name
- admission_status
- class_assigned

---

## Troubleshooting

### Error: "JWT_SECRET environment variable is not configured"

**Solution:**
```bash
# Generate secret
openssl rand -base64 32

# Add to .env
echo "JWT_SECRET=your-generated-secret" >> .env

# Restart server
```

### Error: "Invalid table name" or "Invalid column name"

**Cause:** SQL injection prevention detected invalid input

**Solution:**
- Check that table/column names are alphanumeric + underscore only
- Use whitelisted column names for ORDER BY
- Review API request parameters

### Students Not Receiving Passwords

**Cause:** Email service not configured

**Solution:**
```bash
# Configure email in .env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

**Temporary Workaround:**
```bash
# Check logs for generated passwords
tail -f storage/logs/app.log | grep "Generated password"
```

### Authentication Fails After Update

**Cause:** JWT secret changed

**Solution:**
- All existing tokens are invalidated when JWT_SECRET changes
- Users need to login again
- This is expected behavior for security

---

## Rollback Plan

If you need to rollback these changes:

### 1. Restore Previous Code
```bash
git checkout HEAD~1
```

### 2. Restore Database
```bash
mysql -u root -p basturms < backup_YYYYMMDD_HHMMSS.sql
```

### 3. Restart Server
```bash
composer serve
```

**Note:** Rollback is NOT recommended as it reintroduces security vulnerabilities.

---

## Post-Migration Checklist

- [ ] JWT_SECRET configured in .env
- [ ] Application starts without errors
- [ ] Login works correctly
- [ ] JWT tokens are generated
- [ ] Student creation works
- [ ] Secure passwords are generated
- [ ] Password reset works (no passwords in logs)
- [ ] Email service configured (optional)
- [ ] SQL injection tests pass
- [ ] All API endpoints tested
- [ ] Error logs reviewed
- [ ] No sensitive data in logs
- [ ] Documentation updated
- [ ] Team notified of changes

---

## Support

**Issues?** Check:
1. `storage/logs/app.log` for errors
2. `.env` file configuration
3. Database connection
4. PHP version (8.2+ required)

**Still stuck?** Contact the development team with:
- Error message
- Steps to reproduce
- Environment details (PHP version, OS)
- Relevant log entries

---

**Migration Guide Version:** 1.0  
**Last Updated:** February 22, 2026  
**Estimated Migration Time:** 5-10 minutes
