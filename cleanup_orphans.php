<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

echo "Deleting orphaned records from student_report...\n";
$sql = "DELETE FROM student_report WHERE student_no NOT IN (SELECT student_no FROM students)";
$stmt = $db->prepare($sql);
$stmt->execute();

$count = $stmt->rowCount();
echo "Deleted $count orphaned rows.\n";
