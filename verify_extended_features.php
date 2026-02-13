<?php
require_once 'vendor/autoload.php';

use App\Services\AssignmentActivityService;
use App\Services\ValidationService;
use App\Models\AssignmentActivity;

echo "Starting verification for Edit and Delete features...\n";

try {
    $validationService = new ValidationService();
    $service = new AssignmentActivityService($validationService);

    echo "--- Step 1: Create Initial Activity ---\n";
    $createResult = $service->createActivity([
        'act_name' => 'Original Name',
        'expected_per_term' => 2,
        'weight' => 10,
        'academic_year' => '2024/2025',
        'term' => 'Term 1'
    ], 'admin');
    $activityId = $createResult['data']['activity_id'];
    echo "Created: $activityId\n";

    echo "--- Step 2: Update Activity ---\n";
    $updateResult = $service->updateActivity($activityId, [
        'act_name' => 'Updated Name',
        'expected_per_term' => 5,
        'weight' => 20
    ]);
    echo "Update Result: " . $updateResult['message'] . "\n";

    echo "--- Step 3: Verify Update in List ---\n";
    $listResult = $service->listActivities('2024/2025', 'Term 1');
    $found = false;
    foreach ($listResult['data'] as $act) {
        if ($act['activity_id'] === $activityId && $act['act_name'] === 'Updated Name') {
            $found = true;
            break;
        }
    }
    echo "Update Verified: " . ($found ? "YES" : "NO") . "\n";

    echo "--- Step 4: Soft Delete Activity ---\n";
    $softDeleteResult = $service->softDelete($activityId);
    echo "Soft Delete Result: " . $softDeleteResult['message'] . "\n";

    echo "--- Step 5: Verify Soft Delete (should not be in 'active' list) ---\n";
    $listResult = $service->listActivities('2024/2025', 'Term 1', 'active');
    $found = false;
    foreach ($listResult['data'] as $act) {
        if ($act['activity_id'] === $activityId) {
            $found = true;
            break;
        }
    }
    echo "Hidden from Active List: " . (!$found ? "YES" : "NO") . "\n";

    echo "--- Step 6: Verify Soft Delete (should be in 'inactive' list) ---\n";
    $listResult = $service->listActivities('2024/2025', 'Term 1', 'inactive');
    $found = false;
    foreach ($listResult['data'] as $act) {
        if ($act['activity_id'] === $activityId) {
            $found = true;
            break;
        }
    }
    echo "Found in Inactive List: " . ($found ? "YES" : "NO") . "\n";

    echo "--- Step 7: Permanent Delete ---\n";
    $permDeleteResult = $service->permanentDelete($activityId);
    echo "Permanent Delete Result: " . $permDeleteResult['message'] . "\n";

    echo "--- Step 8: Verify Permanent Delete (should not be anywhere) ---\n";
    $listResult = $service->listActivities('2024/2025', 'Term 1', null); // null status means all (if I implemented it that way, let's check)
    // Wait, listActivities currently requires a status or defaults to active. 
    // Let's check inactive explicitly again.
    $listResult = $service->listActivities('2024/2025', 'Term 1', 'inactive');
    $found = false;
    foreach ($listResult['data'] as $act) {
        if ($act['activity_id'] === $activityId) {
            $found = true;
            break;
        }
    }
    echo "Removed from DB: " . (!$found ? "YES" : "NO") . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
