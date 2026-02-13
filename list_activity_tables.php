<?php
require 'vendor/autoload.php';
$db = App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("SHOW TABLES LIKE '%activit%'");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
