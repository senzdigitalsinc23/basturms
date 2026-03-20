<?php

// Standalone script to update admin passwords
$host = '127.0.0.1';
$dbname = 'agh_validations';
$username = 'root';
$password = 'tem22ple12345?';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Generate proper password hashes
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $inchargePassword = password_hash('incharge123', PASSWORD_BCRYPT);
    
    // Update admin password
    $stmt = $db->prepare("UPDATE validation_staff SET password = :password WHERE email = :email");
    $stmt->execute([
        'password' => $adminPassword,
        'email' => 'admin@ghs.gov.gh'
    ]);
    echo "✓ Admin password updated\n";
    
    // Update HR incharge password
    $stmt->execute([
        'password' => $inchargePassword,
        'email' => 'incharge1@validation.com'
    ]);
    echo "✓ HR Incharge password updated\n";
    
    // Verify the users
    $stmt = $db->prepare("SELECT id, name, email, role FROM validation_staff WHERE email IN ('admin@ghs.gov.gh', 'incharge1@validation.com')");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== Login Credentials ===\n";
    foreach ($users as $user) {
        echo "\n{$user['name']}:\n";
        echo "  Email: {$user['email']}\n";
        if ($user['email'] === 'admin@ghs.gov.gh') {
            echo "  Password: admin123\n";
        } else {
            echo "  Password: incharge123\n";
        }
        echo "  Role: {$user['role']}\n";
    }
    echo "\n========================\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
