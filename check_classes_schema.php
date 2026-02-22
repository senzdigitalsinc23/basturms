<?php
$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "--- classes ---\n";
    $stmt = $pdo->query('DESCRIBE classes');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

    echo "\n--- Sample classes data ---\n";
    $stmt = $pdo->query('SELECT * FROM classes LIMIT 5');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
