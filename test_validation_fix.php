<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     Testing Validation Parameter Fix                  ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Test the query that was failing
    echo "📋 Testing IN clause with placeholders...\n";
    
    $staffIds = [1, 2, 3];
    $userUnitId = 1;
    
    // This is the fixed query
    $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
    echo "  Placeholders: $placeholders\n";
    echo "  Staff IDs: " . implode(', ', $staffIds) . "\n";
    echo "  Unit ID: $userUnitId\n\n";
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM validation_staff 
        WHERE id IN ($placeholders) 
        AND unit_id = ?
        AND deleted_at IS NULL
    ");
    
    $params = array_merge($staffIds, [$userUnitId]);
    echo "  Parameters: " . implode(', ', $params) . "\n";
    echo "  Parameter count: " . count($params) . "\n\n";
    
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "  ✓ Query executed successfully\n";
    echo "  Staff found in unit: {$result['count']}\n";
    echo "  Expected: " . count($staffIds) . "\n\n";
    
    if ($result['count'] == count($staffIds)) {
        echo "  ✓ All staff belong to the unit\n";
    } else {
        echo "  ✗ Some staff are outside the unit\n";
    }
    
    // Test with staff from different units
    echo "\n📋 Testing with mixed unit staff...\n";
    
    $stmt = $db->query("
        SELECT id, name, unit_id 
        FROM validation_staff 
        WHERE deleted_at IS NULL 
        LIMIT 5
    ");
    $allStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Available staff:\n";
    foreach ($allStaff as $s) {
        echo "    - ID: {$s['id']}, Name: {$s['name']}, Unit: {$s['unit_id']}\n";
    }
    
    echo "\n╔════════════════════════════════════════════════════════╗\n";
    echo "║  ✓ Validation parameter fix verified!                 ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
