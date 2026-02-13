<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->query('SHOW TABLES');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
