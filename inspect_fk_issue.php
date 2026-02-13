<?php
require_once __DIR__ . '/vendor/autoload.php';
try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $output = "";

    $id = 'ACT01169CB6';

    $stmt = $db->query("SELECT * FROM activities LIMIT 10");
    $output .= "Activities Table content samples:\n";
    $output .= print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true);

    $stmt = $db->query("SHOW CREATE TABLE assignment_activities");
    $output .= "\n--- assignment_activities ---\n";
    $output .= print_r($stmt->fetch(PDO::FETCH_ASSOC), true);

    $stmt = $db->prepare("SELECT * FROM assignment_activities WHERE activity_id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        $output .= "\nFound ID $id in assignment_activities (activity_id)!\n";
    } else {
        $output .= "\nID $id NOT FOUND in assignment_activities either.\n";
    }

    file_put_contents('inspect_data_mismatch.txt', $output);

} catch (Exception $e) {
    file_put_contents('inspect_data_mismatch.txt', $e->getMessage());
}
