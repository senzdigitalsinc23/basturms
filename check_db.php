<?php
require_once 'vendor/autoload.php';
use App\Core\Database;

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Tables:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

if (in_array('migrations', $tables)) {
    $stmt = $db->query("SELECT migration FROM migrations");
    $applied = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nApplied Migrations:\n";
    foreach ($applied as $m) {
        echo "- $m\n";
    }
} else {
    echo "\n'migrations' table does NOT exist.\n";
}
