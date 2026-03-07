<?php
/**
 * Test script to verify the staff endpoint with status='all' parameter
 * 
 * Usage:
 * 1. Make sure your API server is running
 * 2. Update the API_KEY and JWT_TOKEN below with valid credentials
 * 3. Run: php test_staff_all_endpoint.php
 */

// Configuration
$baseUrl = 'http://localhost:8000/api/v1';
$apiKey = 'YOUR_API_KEY_HERE';
$jwtToken = 'YOUR_JWT_TOKEN_HERE';

// Test cases
$testCases = [
    [
        'name' => 'Get active staff only (default)',
        'url' => "$baseUrl/staff?page=1&limit=5",
        'expected' => 'Should return only active staff'
    ],
    [
        'name' => 'Get all staff (including inactive)',
        'url' => "$baseUrl/staff?page=1&limit=5&status=all",
        'expected' => 'Should return staff with all statuses'
    ],
    [
        'name' => 'Get inactive staff only',
        'url' => "$baseUrl/staff?page=1&limit=5&status=inactive",
        'expected' => 'Should return only inactive staff'
    ],
    [
        'name' => 'Get suspended staff only',
        'url' => "$baseUrl/staff?page=1&limit=5&status=suspended",
        'expected' => 'Should return only suspended staff'
    ]
];

echo "=== Staff Endpoint Test Suite ===\n\n";

foreach ($testCases as $index => $test) {
    echo "Test " . ($index + 1) . ": {$test['name']}\n";
    echo "URL: {$test['url']}\n";
    echo "Expected: {$test['expected']}\n";
    
    $ch = curl_init($test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-Key: $apiKey",
        "Authorization: Bearer $jwtToken",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: $httpCode\n";
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✓ Success\n";
            echo "Total staff: " . ($data['pagination']['total'] ?? 0) . "\n";
            echo "Staff returned: " . count($data['data'] ?? []) . "\n";
            
            // Show status distribution
            if (!empty($data['data'])) {
                $statuses = array_count_values(array_column($data['data'], 'status'));
                echo "Status distribution: " . json_encode($statuses) . "\n";
            }
        } else {
            echo "✗ Failed: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "✗ No response received\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
}

echo "=== Test Suite Complete ===\n";
