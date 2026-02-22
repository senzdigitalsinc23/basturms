<?php
require 'vendor/autoload.php';

use App\Core\Database;
use Dotenv\Dotenv;

if (file_exists('.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$tables = ['staff_documents', 'student_documents', 'documents'];

try {
    $db = Database::getInstance()->getConnection();
    foreach ($tables as $table) {
        echo "--- Table: $table ---\n";
        $stmt = $db->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($cols);
        echo "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
