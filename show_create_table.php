<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();
$tableName = $argv[1] ?? 'activities';
$stmt = $db->query("SHOW CREATE TABLE $tableName");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
