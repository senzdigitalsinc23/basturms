<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();

function showData($db, $table, $limit = 5) {
    echo "\n--- $table (limit $limit) ---\n";
    try {
        $rows = $db->query("SELECT * FROM $table LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
        print_r($rows);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

showData($db, 'classes', 10);
showData($db, 'class_levels', 10);
showData($db, 'promotion_criteria', 10);

echo "\nChecking for references to class_levels in other tables...\n";
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    try {
        $cols = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            if (stripos($col['Field'], 'level') !== false) {
                echo "Table: $table, Column: {$col['Field']}\n";
            }
        }
    } catch (Exception $e) {}
}
