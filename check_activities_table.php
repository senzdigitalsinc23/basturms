<?php
require 'vendor/autoload.php';
$db = App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("SHOW TABLES LIKE 'activities'");
$exists = $stmt->fetch();
if ($exists) {
    echo "Table 'activities' exists.\n";
    $stmt = $db->query("DESCRIBE activities");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo "Table 'activities' does NOT exist.\n";
}
