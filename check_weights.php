<?php
$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query('SELECT act_name, weight FROM assignment_activities');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['act_name'] . ' - ' . $row['weight'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
