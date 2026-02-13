<?php
require 'vendor/autoload.php';
$db = App\Core\Database::getInstance()->getConnection();
$stmt = $db->query('DESCRIBE grading_scheme');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
