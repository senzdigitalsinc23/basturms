<?php
require 'vendor/autoload.php';
$db = App\Core\Database::getInstance()->getConnection();
$stmt = $db->query('DESCRIBE classes');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
echo "\nDESCRIBE assignment_activities:\n";
$stmt = $db->query('DESCRIBE assignment_activities');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
echo "\nDESCRIBE class_activity_assignment:\n";
$stmt = $db->query('DESCRIBE class_activity_assignment');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
