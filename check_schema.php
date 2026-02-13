<?php
require_once __DIR__ . '/vendor/autoload.php';
$db = \App\Core\Database::getInstance()->getConnection();

function describe($db, $table) {
    echo "\n--- $table ---\n";
    try {
        $cols = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        print_r($cols);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

describe($db, 'classes');
describe($db, 'class_levels');
describe($db, 'promotion_criteria');
describe($db, 'class_subjects');
