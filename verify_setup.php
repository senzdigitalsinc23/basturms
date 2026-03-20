<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use Dotenv\Dotenv;

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo "\033[36m╔════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║     AGH Validation System - Setup Verification        ║\033[0m\n";
echo "\033[36m╚════════════════════════════════════════════════════════╝\033[0m\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check units
    echo "\033[33m📦 Units:\033[0m\n";
    $stmt = $db->query("SELECT name, description FROM units ORDER BY name");
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($units as $unit) {
        echo "  ✓ {$unit['name']} - {$unit['description']}\n";
    }
    echo "\n";
    
    // Check staff by role
    echo "\033[33m👥 Staff by Role:\033[0m\n";
    $stmt = $db->query("
        SELECT role, COUNT(*) as count 
        FROM validation_staff 
        GROUP BY role 
        ORDER BY FIELD(role, 'admin', 'accountant', 'incharge', 'staff')
    ");
    $roleCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($roleCounts as $roleCount) {
        echo "  ✓ " . ucfirst($roleCount['role']) . ": {$roleCount['count']}\n";
    }
    echo "\n";
    
    // Show login credentials
    echo "\033[33m🔐 Login Credentials:\033[0m\n";
    $stmt = $db->query("
        SELECT s.name, s.email, s.role, u.name as unit_name
        FROM validation_staff s
        LEFT JOIN units u ON s.unit_id = u.id
        WHERE s.role IN ('admin', 'accountant', 'incharge')
        ORDER BY FIELD(s.role, 'admin', 'accountant', 'incharge'), s.email
        LIMIT 5
    ");
    $credentials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n";
    echo "  ┌─────────────┬────────────────────────────────────┬──────────────┐\n";
    echo "  │ Role        │ Email                              │ Password     │\n";
    echo "  ├─────────────┼────────────────────────────────────┼──────────────┤\n";
    
    foreach ($credentials as $cred) {
        $role = str_pad(ucfirst($cred['role']), 11);
        $email = str_pad($cred['email'], 34);
        $password = str_pad($cred['role'] . '123', 12);
        echo "  │ {$role} │ {$email} │ {$password} │\n";
    }
    
    echo "  └─────────────┴────────────────────────────────────┴──────────────┘\n\n";
    
    // Show sample staff
    echo "\033[33m👤 Sample Staff Members:\033[0m\n";
    $stmt = $db->query("
        SELECT s.name, s.email, u.name as unit_name
        FROM validation_staff s
        LEFT JOIN units u ON s.unit_id = u.id
        WHERE s.role = 'staff'
        LIMIT 3
    ");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($staff as $member) {
        echo "  ✓ {$member['name']} ({$member['unit_name']}) - {$member['email']}\n";
    }
    echo "    Password: staff123\n\n";
    
    // API Information
    echo "\033[33m🌐 API Configuration:\033[0m\n";
    echo "  Base URL: " . ($_ENV['APP_URL'] ?? 'http://localhost:8000') . "/api/v1\n";
    echo "  API Key: " . ($_ENV['API_KEY'] ?? 'Not set') . "\n";
    echo "  CORS Origins: " . ($_ENV['CORS_ALLOWED_ORIGINS'] ?? 'Not set') . "\n\n";
    
    echo "\033[32m╔════════════════════════════════════════════════════════╗\033[0m\n";
    echo "\033[32m║  ✓ Setup verification completed successfully!         ║\033[0m\n";
    echo "\033[32m╚════════════════════════════════════════════════════════╝\033[0m\n\n";
    
    echo "\033[33mNext Steps:\033[0m\n";
    echo "  1. Start the API server: php bin/console serve\n";
    echo "  2. Update frontend .env.local with API_KEY: " . ($_ENV['API_KEY'] ?? 'devKey123') . "\n";
    echo "  3. Start the frontend: cd ../agh-validation-ui && npm run dev\n";
    echo "  4. Visit: http://localhost:3000/login\n\n";
    
} catch (\Exception $e) {
    echo "\033[31m✗ Error:\033[0m " . $e->getMessage() . "\n";
    exit(1);
}
