<?php
require 'vendor/autoload.php';

use App\Core\Database;
use PDO;

$db = Database::getInstance()->getConnection();
$stmt = $db->query('DESCRIBE student_report');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo $col['Field'] . ' - ' . $col['Type'] . PHP_EOL;
}
