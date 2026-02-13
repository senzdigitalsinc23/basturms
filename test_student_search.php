<?php

require __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Repositories\StudentRepository;
use App\Services\StudentService;
use App\Services\ValidationService;

// Load config
\App\Core\Config::load(__DIR__ . '/config');

try {
    // Test the repository directly
    $repository = new StudentRepository();
    
    echo "Testing StudentRepository...\n";
    
    // Test with search
    $students = $repository->getStudents(10, 0, 'John', 'active');
    echo "Found " . count($students) . " students with search 'John'\n";
    
    // Test without search
    $students = $repository->getStudents(10, 0, null, 'active');
    echo "Found " . count($students) . " students without search\n";
    
    // Test count
    $total = $repository->countStudents('John', 'active');
    echo "Total count with search 'John': $total\n";
    
    $total = $repository->countStudents(null, 'active');
    echo "Total count without search: $total\n";
    
    echo "Repository test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}