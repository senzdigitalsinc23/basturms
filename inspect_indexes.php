<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();

echo "=== ACTIVITIES TABLE INDEXES ===\n";
try {
    $stmt = $db->query("SHOW INDEX FROM activities");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($indexes as $idx) {
        echo "Key_name: {$idx['Key_name']}, Column_name: {$idx['Column_name']}, Non_unique: {$idx['Non_unique']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
