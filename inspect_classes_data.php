<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, class_id FROM classes LIMIT 50");
file_put_contents('classes_data.txt', print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true));
