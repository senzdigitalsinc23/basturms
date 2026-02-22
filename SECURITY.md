# Security Guidelines

## Priority 1 Security Fixes - COMPLETED ✅

This document outlines the critical security improvements implemented in the Basturms School Management System.

### 1. Secure Password Generation ✅

**Issue**: Predictable password pattern (`FirstInitial + LastName + 123`)  
**Risk**: Easy brute force attacks  
**Fix**: Implemented cryptographically secure random password generator

**Implementation**:
- Created `App/Utils/PasswordGenerator.php` with secure random generation
- Generates 12-character passwords with mixed case, numbers, and symbols
- Updated `App/Services/StudentService.php` to use secure passwords
- Passwords meet modern security standards (OWASP compliant)

**Usage**:
```php
$password = \App\Utils\PasswordGenerator::generate(12); // Secure random password
$memorable = \App\Utils\PasswordGenerator::generateMemorable(4); // Memorable password
```

### 2. JWT Secret Enforcement ✅

**Issue**: Fallback to weak default secrets (`'your-secret-key-change-this'`, `'change_me'`)  
**Risk**: Token forgery, complete authentication bypass  
**Fix**: Enforced JWT_SECRET configuration requirement

**Implementation**:
- Updated `App/Middleware/JWTMiddleware.php` to throw exception if JWT_SECRET not set
- Updated `App/Services/AuthService.php` to throw exception if JWT_SECRET not set
- No fallback defaults - application will not start without proper configuration

**Configuration Required**:
```env
# .env file - REQUIRED
JWT_SECRET=your-strong-random-secret-key-min-32-chars
```

**Generate Strong Secret**:
```bash
# Linux/Mac
openssl rand -base64 32

# PHP
php -r "echo bin2hex(random_bytes(32));"
```

### 3. Removed Plain Text Password Logging ✅

**Issue**: Passwords logged to error_log during password reset  
**Risk**: Password exposure in log files  
**Fix**: Removed all plain text password logging

**Implementation**:
- Updated `App/Services/AuthService.php` forgotPassword() method
- Passwords never logged, even in development mode
- Email failures logged without exposing credentials
- Production mode throws exception on email failure

### 4. SQL Injection Prevention ✅

**Issue**: Unsanitized user input in ORDER BY clauses and table names  
**Risk**: Complete database compromise  
**Fix**: Implemented whitelisting and validation

**Implementation**:

#### Student Model (`App/Models/Student.php`):
- Whitelisted allowed ORDER BY columns
- Validated ORDER direction (ASC/DESC only)
- Removed parameterized binding of ORDER BY (not supported by PDO)

```php
$allowedOrderColumns = [
    'student_no', 'first_name', 'last_name', 'other_name',
    'phone', 'email', 'class_name', 'admission_status', 'class_assigned'
];
```

#### ORM Model Base Class (`Database/ORM/Model.php`):
- Validated table names (alphanumeric and underscore only)
- Validated column names in WHERE clauses
- Validated ORDER BY column names
- Added comprehensive input validation

**Validation Pattern**:
```php
if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
    throw new \InvalidArgumentException('Invalid table name');
}
```

### 5. Input Validation Best Practices ✅

**Implemented Across All Endpoints**:
- Whitelist validation for enum-like values
- Regex validation for identifiers
- Type casting for numeric inputs
- Length validation for strings
- Format validation for dates and emails

**Example**:
```php
// Validate status enum
$allowedStatuses = ['active', 'inactive', 'graduated', 'transferred'];
if (!in_array($status, $allowedStatuses, true)) {
    throw new ValidationException(['status' => ['Invalid status value']]);
}

// Validate and sanitize ORDER BY
if (!in_array($orderBy, $allowedColumns, true)) {
    $orderBy = 'id'; // Safe default
}
```

## Security Checklist for Developers

### Before Deploying to Production:

- [ ] Set strong JWT_SECRET in .env (minimum 32 characters)
- [ ] Configure email service for password resets
- [ ] Review all error logs for sensitive data
- [ ] Enable HTTPS (enforce in production)
- [ ] Set APP_ENV=production in .env
- [ ] Disable debug mode (APP_DEBUG=false)
- [ ] Configure rate limiting thresholds
- [ ] Review and update API keys
- [ ] Enable database query logging (temporarily) to verify no SQL injection
- [ ] Run security audit: `composer security-audit`
- [ ] Test authentication flows
- [ ] Test password reset flow
- [ ] Verify JWT token expiration
- [ ] Test account lockout mechanism

### Code Review Checklist:

- [ ] No hardcoded credentials
- [ ] No plain text passwords in logs
- [ ] All user input validated
- [ ] SQL queries use parameterized statements
- [ ] ORDER BY columns whitelisted
- [ ] Table/column names validated
- [ ] Error messages don't expose system details
- [ ] Sensitive data encrypted at rest
- [ ] HTTPS enforced for all endpoints
- [ ] CORS configured correctly
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] Session security configured

## Remaining Security Enhancements (Priority 2)

### To Be Implemented:

1. **Dependency Injection** - Remove direct instantiation of dependencies
2. **Database Indexes** - Add indexes on foreign keys and frequently queried columns
3. **N+1 Query Optimization** - Use JOINs instead of loops
4. **Comprehensive Unit Tests** - Achieve 70%+ code coverage
5. **Security Test Suite** - OWASP testing guidelines
6. **API Rate Limiting** - Per-endpoint rate limits
7. **Input Sanitization** - XSS prevention middleware
8. **Audit Logging** - Comprehensive security event logging

## Reporting Security Issues

If you discover a security vulnerability, please email: security@basturms.com

**Do NOT** create public GitHub issues for security vulnerabilities.

## Security Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [JWT Best Practices](https://tools.ietf.org/html/rfc8725)
- [SQL Injection Prevention](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)

---

**Last Updated**: February 2026  
**Security Audit**: Completed  
**Next Review**: March 2026
