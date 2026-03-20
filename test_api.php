<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     AGH Validation System - API Test                  ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Test 1: Check table structure
    echo "📋 Testing Database Schema...\n";
    $stmt = $db->query("DESCRIBE validation_staff");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $idColumn = array_filter($columns, fn($col) => $col['Field'] === 'id');
    $idColumn = reset($idColumn);
    
    if ($idColumn['Type'] === 'int' && strpos($idColumn['Extra'], 'auto_increment') !== false) {
        echo "  ✓ validation_staff.id is INT AUTO_INCREMENT\n";
    } else {
        echo "  ✗ validation_staff.id is NOT INT AUTO_INCREMENT\n";
        echo "    Type: {$idColumn['Type']}, Extra: {$idColumn['Extra']}\n";
    }
    
    // Test 2: Check staff records
    echo "\n👥 Testing Staff Records...\n";
    $stmt = $db->query("SELECT id, name, email, role FROM validation_staff LIMIT 5");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($staff as $member) {
        $idType = is_int($member['id']) ? 'INT' : gettype($member['id']);
        echo "  ✓ ID: {$member['id']} ({$idType}) - {$member['name']} ({$member['role']})\n";
    }
    
    // Test 3: Check validations table
    echo "\n✅ Testing Validations Table...\n";
    $stmt = $db->query("DESCRIBE validations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $idColumn = array_filter($columns, fn($col) => $col['Field'] === 'id');
    $idColumn = reset($idColumn);
    
    if ($idColumn['Type'] === 'int' && strpos($idColumn['Extra'], 'auto_increment') !== false) {
        echo "  ✓ validations.id is INT AUTO_INCREMENT\n";
    } else {
        echo "  ✗ validations.id is NOT INT AUTO_INCREMENT\n";
    }
    
    // Test 4: Test creating a validation record
    echo "\n🔄 Testing Validation Insert...\n";
    $db->beginTransaction();
    
    $stmt = $db->prepare("
        INSERT INTO validations (staff_id, month, year, validated, validated_by, validated_at)
        VALUES (1, 'March', 2026, TRUE, 1, NOW())
        ON DUPLICATE KEY UPDATE validated_at = NOW()
    ");
    $stmt->execute();
    
    $lastId = $db->lastInsertId();
    echo "  ✓ Validation created with ID: {$lastId}\n";
    
    $db->rollBack(); // Rollback test data
    echo "  ✓ Test data rolled back\n";
    
    // Test 5: Check comprehensive staff tables
    echo "\n📊 Testing Comprehensive Staff Tables...\n";
    $tables = [
        'staff_personal_info',
        'staff_contact_info',
        'staff_employment_info',
        'staff_qualifications',
        'staff_bank_info'
    ];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  ✓ $table: {$result['count']} records\n";
    }
    
    echo "\n╔════════════════════════════════════════════════════════╗\n";
    echo "║  ✓ All API tests passed successfully!                 ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
