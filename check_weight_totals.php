<?php
$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "--- All Activity Weights ---\n";
    $stmt = $pdo->query('SELECT act_name, weight FROM assignment_activities');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Activity: " . $row['act_name'] . " - Weight: " . $row['weight'] . "\n";
    }

    echo "\n--- Grouped Weights ---\n";
    $stmt = $pdo->query('SELECT 
                            CASE WHEN act_name LIKE "%Exam%" THEN "EXAM" ELSE "SBA" END as type,
                            SUM(weight) as total_weight
                         FROM assignment_activities 
                         GROUP BY type');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
