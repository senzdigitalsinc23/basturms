<?php
$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "--- Distinct level_id values in classes ---\n";
    $stmt = $pdo->query('SELECT DISTINCT level_id FROM classes');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "'" . $row['level_id'] . "'\n";
    }

    echo "\n--- All classes with level_id ---\n";
    $stmt = $pdo->query('SELECT class_name, level_id FROM classes ORDER BY class_name');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['class_name'] . " -> " . ($row['level_id'] ?: '[empty]') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
