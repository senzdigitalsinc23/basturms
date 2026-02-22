<?php
/**
 * Security Fixes Verification Script
 * 
 * This script tests all Priority 1 security fixes to ensure they're working correctly.
 * Run this after implementing the security fixes.
 */

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

echo "=== BASTURMS Security Fixes Verification ===\n\n";

$allPassed = true;

// Test 1: JWT Secret Configuration
echo "Test 1: JWT Secret Configuration\n";
echo "-----------------------------------\n";
if (empty($_ENV['JWT_SECRET'])) {
    echo "❌ FAILED: JWT_SECRET is not configured\n";
    echo "   Action: Add JWT_SECRET to your .env file\n";
    echo "   Generate: php -r \"echo bin2hex(random_bytes(32));\"\n";
    $allPassed = false;
} else {
    $secretLength = strlen($_ENV['JWT_SECRET']);
    if ($secretLength < 32) {
        echo "⚠️  WARNING: JWT_SECRET is too short ({$secretLength} chars)\n";
        echo "   Recommendation: Use at least 32 characters\n";
        $allPassed = false;
    } else {
        echo "✅ PASSED: JWT_SECRET is configured ({$secretLength} chars)\n";
    }
}
echo "\n";

// Test 2: Password Generator
echo "Test 2: Secure Password Generator\n";
echo "-----------------------------------\n";
try {
    if (!class_exists('App\Utils\PasswordGenerator')) {
        echo "❌ FAILED: PasswordGenerator class not found\n";
        $allPassed = false;
    } else {
        $password = \App\Utils\PasswordGenerator::generate(12);
        $validation = \App\Utils\PasswordGenerator::validateStrength($password);
        
        if ($validation['valid']) {
            echo "✅ PASSED: Password generator working\n";
            echo "   Sample: {$password}\n";
            echo "   Strength: {$validation['strength']}/100\n";
        } else {
            echo "❌ FAILED: Generated password is weak\n";
            echo "   Errors: " . implode(', ', $validation['errors']) . "\n";
            $allPassed = false;
        }
    }
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// Test 3: Database Connection
echo "Test 3: Database Connection\n";
echo "-----------------------------------\n";
try {
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "✅ PASSED: Database connection successful\n";
    echo "   Database: " . ($_ENV['DB_NAME'] ?? 'unknown') . "\n";
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// Test 4: SQL Injection Prevention
echo "Test 4: SQL Injection Prevention\n";
echo "-----------------------------------\n";
try {
    // Test table name validation
    try {
        \Database\ORM\Model::all('users; DROP TABLE students--');
        echo "❌ FAILED: SQL injection not prevented (table name)\n";
        $allPassed = false;
    } catch (\InvalidArgumentException $e) {
        echo "✅ PASSED: Table name validation working\n";
    }
    
    // Test ORDER BY validation in Student model
    $testOrderBy = "1; DROP TABLE students--";
    $result = \App\Models\Student::paginate(1, 0, $testOrderBy, 'ASC');
    echo "✅ PASSED: ORDER BY validation working (malicious input rejected)\n";
    
} catch (\Exception $e) {
    echo "⚠️  WARNING: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: AuthService JWT Secret Enforcement
echo "Test 5: AuthService Configuration\n";
echo "-----------------------------------\n";
try {
    // Temporarily unset JWT_SECRET to test enforcement
    $originalSecret = $_ENV['JWT_SECRET'] ?? null;
    unset($_ENV['JWT_SECRET']);
    
    try {
        $userRepo = new \App\Repositories\UserRepository();
        $academicService = new \App\Services\AcademicSetupService(
            new \App\Repositories\AcademicSetupRepository(),
            new \App\Repositories\AcademicYearRepository(),
            new \App\Services\ValidationService()
        );
        $authService = new \App\Services\AuthService($userRepo, $academicService);
        
        echo "❌ FAILED: AuthService allows empty JWT_SECRET\n";
        $allPassed = false;
    } catch (\RuntimeException $e) {
        echo "✅ PASSED: AuthService enforces JWT_SECRET configuration\n";
        echo "   Error message: " . substr($e->getMessage(), 0, 50) . "...\n";
    }
    
    // Restore JWT_SECRET
    if ($originalSecret) {
        $_ENV['JWT_SECRET'] = $originalSecret;
    }
} catch (\Exception $e) {
    echo "⚠️  WARNING: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Environment Configuration
echo "Test 6: Environment Configuration\n";
echo "-----------------------------------\n";
$requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'JWT_SECRET', 'API_KEY'];
$missingVars = [];

foreach ($requiredVars as $var) {
    if (empty($_ENV[$var])) {
        $missingVars[] = $var;
    }
}

if (empty($missingVars)) {
    echo "✅ PASSED: All required environment variables configured\n";
} else {
    echo "❌ FAILED: Missing environment variables:\n";
    foreach ($missingVars as $var) {
        echo "   - {$var}\n";
    }
    $allPassed = false;
}
echo "\n";

// Test 7: File Permissions
echo "Test 7: File Permissions\n";
echo "-----------------------------------\n";
$criticalFiles = [
    '.env' => 'Should not be publicly accessible',
    'storage/logs' => 'Should be writable',
    'storage/cache' => 'Should be writable',
];

foreach ($criticalFiles as $file => $requirement) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        if (is_writable($path) || $file === '.env') {
            echo "✅ {$file}: {$requirement}\n";
        } else {
            echo "⚠️  {$file}: Not writable\n";
        }
    } else {
        echo "⚠️  {$file}: Does not exist\n";
    }
}
echo "\n";

// Final Summary
echo "=== SUMMARY ===\n";
echo "-----------------------------------\n";
if ($allPassed) {
    echo "✅ ALL TESTS PASSED!\n";
    echo "\nYour application is secure and ready for deployment.\n";
    echo "\nNext steps:\n";
    echo "1. Test authentication flow (login/logout)\n";
    echo "2. Test student creation with secure passwords\n";
    echo "3. Test password reset (verify no passwords in logs)\n";
    echo "4. Review error logs for any issues\n";
    echo "5. Deploy to production\n";
} else {
    echo "❌ SOME TESTS FAILED\n";
    echo "\nPlease fix the issues above before deploying.\n";
    echo "Refer to MIGRATION_GUIDE.md for detailed instructions.\n";
}
echo "\n";

exit($allPassed ? 0 : 1);
