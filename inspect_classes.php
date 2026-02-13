<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("SHOW CREATE TABLE classes");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
file_put_contents('classes_schema.txt', print_r($row, true));
