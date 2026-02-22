<?php
$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    
    echo "=== GUARDIAN_INFO TABLE ===\n";
    $stmt = $db->query('DESCRIBE guardian_info');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== EMERGENCY_CONTACT TABLE ===\n";
    $stmt = $db->query('DESCRIBE emergency_contact');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== STUDENTS TABLE ===\n";
    $stmt = $db->query('DESCRIBE students');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== SAMPLE GUARDIAN DATA ===\n";
    $stmt = $db->query('SELECT * FROM guardian_info LIMIT 2');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "\n=== SAMPLE EMERGENCY CONTACT DATA ===\n";
    $stmt = $db->query('SELECT * FROM emergency_contact LIMIT 2');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
