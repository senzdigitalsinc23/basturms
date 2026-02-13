<?php
require 'vendor/autoload.php';

use App\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Ensure admin role exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO roles (role_id, name) VALUES (1, 'admin')");
    $stmt->execute();
    
    // Create test user
    $userId = uniqid('user_');
    $username = 'testadmin';
    $email = 'test@example.com';
    $plainPassword = 'password123';
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (user_id, username, email, password, role_id, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, 1, 'active', NOW(), NOW())
        ON DUPLICATE KEY UPDATE updated_at = NOW()
    ");
    $stmt->execute([$userId, $username, $email, $hashedPassword]);
    
    echo "✅ Test user created/updated!\n";
    echo "Email: {$email}\n";
    echo "Password: {$plainPassword}\n";
    echo "Try logging in with these credentials to your API at /api/v1/login\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    if (isset($pdo)) {
        echo "Tables check:\n";
        $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        echo "Users table exists: " . ($tables ? 'Yes' : 'No') . "\n";
        $tables = $pdo->query("SHOW TABLES LIKE 'roles'")->fetch();
        echo "Roles table exists: " . ($tables ? 'Yes' : 'No') . "\n";
    }
}
?>
