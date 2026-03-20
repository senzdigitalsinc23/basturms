<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Core\Database;

echo "Testing Validation Settings...\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Test 1: Check if table exists
    echo "1. Checking if validation_settings table exists...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'validation_settings'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "   ✓ Table exists\n\n";
        
        // Test 2: Check table structure
        echo "2. Checking table structure...\n";
        $stmt = $db->query("DESCRIBE validation_settings");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "   - {$column['Field']} ({$column['Type']})\n";
        }
        echo "\n";
        
        // Test 3: Try to insert a test record
        echo "3. Testing insert...\n";
        $stmt = $db->prepare("
            INSERT INTO validation_settings (month, year, start_date, end_date, created_by)
            VALUES (:month, :year, :start_date, :end_date, :created_by)
            ON DUPLICATE KEY UPDATE
                start_date = VALUES(start_date),
                end_date = VALUES(end_date),
                updated_at = NOW()
        ");
        
        $stmt->execute([
            'month' => 'March',
            'year' => 2026,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'created_by' => 1
        ]);
        
        echo "   ✓ Insert successful\n\n";
        
        // Test 4: Read the record
        echo "4. Testing select...\n";
        $stmt = $db->prepare("
            SELECT * FROM validation_settings WHERE month = :month AND year = :year
        ");
        $stmt->execute(['month' => 'March', 'year' => 2026]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            echo "   ✓ Record found:\n";
            echo "     Month: {$record['month']}\n";
            echo "     Year: {$record['year']}\n";
            echo "     Start Date: {$record['start_date']}\n";
            echo "     End Date: {$record['end_date']}\n";
            echo "     Created By: {$record['created_by']}\n";
        }
        
        echo "\n✅ All tests passed!\n";
        
    } else {
        echo "   ❌ Table does not exist. Please run migrations.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
