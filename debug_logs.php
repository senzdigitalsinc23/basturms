<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM audit_logs WHERE action_type = 'student_scores' ORDER BY id DESC LIMIT 5");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($logs as $log) {
    echo "ID: " . $log['id'] . "\n";
    echo "Action: " . $log['action_type'] . "\n";
    echo "Details: " . $log['details'] . "\n";
    echo "Time: " . $log['created_at'] . "\n";
    echo "----------------------------------------\n";
}
