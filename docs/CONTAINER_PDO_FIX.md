# Container PDO Dependency Fix

**Date:** February 22, 2026  
**Issue:** Unresolvable dependency: dsn  
**Status:** ✅ FIXED

---

## Problem

The dependency injection container couldn't resolve PDO instances because PDO requires specific constructor parameters (dsn, username, password, options) that the container didn't know how to provide.

### Error Message
```
"error": "Unresolvable dependency: dsn"
```

### Root Cause
When the container tried to auto-resolve classes that depend on PDO (like repositories), it attempted to instantiate PDO automatically but failed because:
1. PDO constructor requires `$dsn` (string) parameter
2. Container couldn't determine what value to pass for `$dsn`
3. No binding was registered for PDO in the container

---

## Solution

Added explicit PDO binding to the container with proper database configuration.

### Changes Made

**File:** `public/index.php`

#### 1. Added PDO Singleton Binding
```php
// Bind PDO (database connection)
$container->singleton(\PDO::class, function($container) {
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $dbname = $_ENV['DB_NAME'] ?? 'basturms_db';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    return new \PDO($dsn, $user, $pass, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false
    ]);
});
```

#### 2. Added Cache Singleton Binding
```php
// Bind Cache
$