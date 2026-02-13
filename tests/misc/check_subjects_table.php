<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

try {
    // Check if subjects table exists and its structure
    $stmt = $db->query("SHOW TABLES LIKE 'subjects'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "✓ Subjects table exists\n";

        // Check table structure
        $stmt = $db->query("DESCRIBE subjects");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Table structure:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
        }

        // Check if any data exists
        $stmt = $db->query("SELECT COUNT(*) as count FROM subjects");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "\nRecords in table: {$count['count']}\n";

        if ($count['count'] > 0) {
            // Show a few sample records
            $stmt = $db->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name LIMIT 15");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "\nSample records:\n";
            foreach ($records as $record) {
                echo "  - {$record['subject_name']} ({$record['subject_id']})\n";
            }
        }
    } else {
        echo "✗ Subjects table does not exist\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
