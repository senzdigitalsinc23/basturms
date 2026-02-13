<?php
$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "--- Score Distribution (Top 20) ---\n";
    $stmt = $pdo->query('SELECT score, COUNT(*) as count FROM scores GROUP BY score ORDER BY count DESC LIMIT 20');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Score: " . $row['score'] . " - Count: " . $row['count'] . "\n";
    }

    echo "\n--- Scores > 20 ---\n";
    $stmt = $pdo->query('SELECT COUNT(*) FROM scores WHERE score > 20');
    echo "Count of scores > 20: " . $stmt->fetchColumn() . "\n";

    echo "\n--- Sample Scores for a Student (SBA) ---\n";
    $stmt = $pdo->query('SELECT s.student_no, s.score, aa.act_name 
                         FROM scores s 
                         JOIN activities a ON s.activity_id = a.id 
                         JOIN assignment_activities aa ON a.act_id = aa.activity_id 
                         WHERE aa.act_name != "End of Term Exam"
                         LIMIT 10');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
