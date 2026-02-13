<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();

try {
    $row = $db->query("SHOW CREATE VIEW vw_student_profile")->fetch(PDO::FETCH_ASSOC);
    echo "VIEW DEFINITION:\n";
    echo $row['Create View'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
