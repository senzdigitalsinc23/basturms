<?php
require 'vendor/autoload.php';
$db = App\Core\Database::getInstance()->getConnection();

function printTableSchema($db, $table) {
    echo "--- $table ---\n";
    try {
        $stmt = $db->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo str_pad($row['Field'], 25) . " " . $row['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

printTableSchema($db, 'student_summary_report');
printTableSchema($db, 'assignment_activities');
