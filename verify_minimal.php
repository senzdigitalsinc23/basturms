<?php
require_once 'vendor/autoload.php';

use App\Services\AssignmentActivityService;
use App\Services\ValidationService;
use App\Models\AssignmentActivity;

echo "Starting minimal verification...\n";

try {
    $validationService = new ValidationService();
    $service = new AssignmentActivityService($validationService);

    echo "--- Test 1: Create Assignment Activity via Service ---\n";
    $activityId = 'MIN_ACT_' . time();
    $data = [
        'activity_id' => $activityId,
        'act_name' => 'Minimal Test Activity',
        'expected_per_term' => 3,
        'weight' => 15,
        'academic_year' => '2024/2025',
        'term' => 'Term 2'
    ];
    
    $result = $service->createActivity($data, 'system');
    echo "Result: " . json_encode($result) . "\n\n";

    echo "--- Test 2: List Assignment Activities via Service ---\n";
    $list = $service->listActivities('2024/2025', 'Term 2');
    echo "Count: " . count($list['data']) . "\n";
    echo "First item name: " . ($list['data'][0]['act_name'] ?? 'N/A') . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    // echo $e->getTraceAsString() . "\n";
}
