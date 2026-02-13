<?php
require 'vendor/autoload.php';
$db = App\Core\Database::getInstance()->getConnection();
$stmt = $db->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
file_put_contents('all_tables.txt', implode("\n", $tables));
echo "Listed " . count($tables) . " tables.\n";
