<?php
require_once 'vendor/autoload.php';
use App\Core\Database;

$db = Database::getInstance()->getConnection();

function checkIndex($db, $table, $indexName) {
    $stmt = $db->query("SHOW INDEX FROM $table WHERE Key_name = '$indexName'");
    return $stmt->fetch() !== false;
}

function checkFK($db, $table, $fkName) {
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = '$table' 
          AND CONSTRAINT_NAME = '$fkName'
    ");
    return $stmt->fetch() !== false;
}

$checks = [
    ['type' => 'INDEX', 'table' => 'users', 'name' => 'idx_users_username', 'desc' => 'Unique Username Index'],
    ['type' => 'FK', 'table' => 'admission_details', 'name' => 'fk_admission_class_assigned', 'desc' => 'Admission Class FK'],
    ['type' => 'INDEX', 'table' => 'students', 'name' => 'idx_students_fullname', 'desc' => 'Students Fullname Index'],
    ['type' => 'INDEX', 'table' => 'academic_setup', 'name' => 'idx_academic_setup_search', 'desc' => 'Academic Setup Search Index'],
    ['type' => 'FK', 'table' => 'grading_scheme', 'name' => 'fk_grading_scheme_added_by', 'desc' => 'Grading Scheme Added By FK'],
];

echo "Verification Results:\n";
foreach ($checks as $check) {
    $passed = false;
    if ($check['type'] === 'INDEX') {
        $passed = checkIndex($db, $check['table'], $check['name']);
    } else {
        $passed = checkFK($db, $check['table'], $check['name']);
    }
    
    echo ($passed ? "[PASS]" : "[FAIL]") . " " . $check['desc'] . " (" . $check['table'] . " -> " . $check['name'] . ")\n";
}
