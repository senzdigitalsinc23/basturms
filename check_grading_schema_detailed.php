<?php
require 'vendor/autoload.php';
$db = App\Core\Database::getInstance()->getConnection();
echo "Schema for grading_scheme:\n";
$stmt = $db->query('DESCRIBE grading_scheme');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("%-15s %-15s %-10s %-10s %-10s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default']);
}
