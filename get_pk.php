<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("SHOW KEYS FROM assignment_activities WHERE Key_name = 'PRIMARY'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Primary Key: " . ($row['Column_name'] ?? 'Not Found') . "\n";

// Also check columns just in case
$stmt = $db->query("DESCRIBE assignment_activities");
$cols = $stmt->fetchAll(PDO::FETCH_COlUMN);
echo "Columns: " . implode(', ', $cols) . "\n";
