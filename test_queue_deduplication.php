<?php

// Test script for queue deduplication
// Run with: php test_queue_deduplication.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set environment variables for database connection
putenv('DB_HOST=127.0.0.1');
putenv('DB_NAME=basturms_db');
putenv('DB_USER=root');
putenv('DB_PASS=tem22ple12345?');

require_once 'Core/Config.php';
require_once 'Core/Logger.php';
require_once 'Core/Database.php';
require_once 'Core/Queue.php';

use App\Core\Database;
use App\Core\Queue;

try {
    // Get DB from existing Core infrastructure
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Connected to database successfully using Core infrastructure.\n\n";

    $jobClass = 'Jobs\\SummarizeScoresJob';
    $data = [
        'academic_year' => '2025/2026',
        'term' => 'Term 1'
    ];

    // 1. Clear existing pending jobs for this test
    $pdo->exec("DELETE FROM queue_jobs WHERE job_class = '$jobClass' AND status = 'pending'");
    echo "Cleared existing pending jobs for test.\n";

    // 2. Dispatch the same job twice
    echo "Dispatching job first time...\n";
    Queue::dispatch($jobClass, $data);
    
    echo "Dispatching job second time (should be skipped)...\n";
    Queue::dispatch($jobClass, $data);

    // 3. Verify count in database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM queue_jobs WHERE job_class = :job_class AND status = 'pending'");
    $stmt->execute([':job_class' => $jobClass]);
    $count = $stmt->fetchColumn();

    if ($count == 1) {
        echo "✅ SUCCESS: Only 1 pending job found in queue_jobs as expected.\n";
    } else {
        echo "❌ FAILURE: Found $count pending jobs. Expected 1.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
