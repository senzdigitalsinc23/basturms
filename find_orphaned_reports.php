<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

$sql = "SELECT DISTINCT student_no FROM student_report WHERE student_no NOT IN (SELECT student_no FROM students)";
$orphans = $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);

if (empty($orphans)) {
    echo "No orphaned student records found in student_report.\n";
} else {
    echo "Found orphaned student records in student_report:\n";
    foreach ($orphans as $orphan) {
        echo "- $orphan\n";
    }
}
