<?php
require_once 'vendor/autoload.php';

use App\Services\ClassActivityAssignmentService;
use App\Services\AssignmentActivityService;
use App\Services\ValidationService;
use App\Core\Database;

echo "Starting verification for Class Activity Assignment...\n";

try {
    $validationService = new ValidationService();
    $assignmentActivityService = new AssignmentActivityService($validationService);
    $classAssignmentService = new ClassActivityAssignmentService($validationService);

    // 1. Create a dummy activity
    $activityResult = $assignmentActivityService->createActivity([
        'act_name' => 'Assignment Test ' . uniqid(),
        'expected_per_term' => 1,
        'weight' => 5,
        'academic_year' => '2024/2025',
        'term' => 'Term 4'
    ], 'admin');
    $activityId = $activityResult['data']['activity_id'];
    echo "Created Activity: $activityId\n";

    // 2. Assign to a class (assuming class_id 1 exists or using a dummy)
    // Let's find an existing class first to be sure
    $db = Database::getInstance()->getConnection();
    $classId = $db->query("SELECT class_id FROM classes LIMIT 1")->fetchColumn();
    
    if (!$classId) {
        throw new Exception("No classes found to test assignment.");
    }
    echo "Testing with Class ID: $classId\n";

    echo "Assigning Activity...\n";
    $assignResult = $classAssignmentService->assignActivity([
        'class_id' => $classId,
        'act_id' => $activityId
    ], 'admin');
    echo "Assign Result: " . $assignResult['message'] . "\n";

    // 3. List assignments
    echo "Listing assignments for class...\n";
    $listResult = $classAssignmentService->listClassActivities($classId);
    $found = false;
    foreach ($listResult['data'] as $act) {
        if ($act['activity_id'] === $activityId) {
            $found = true;
            break;
        }
    }
    echo "Assignment Found in List: " . ($found ? "YES" : "NO") . "\n";

    // 4. Unassign
    echo "Unassigning Activity...\n";
    $unassignResult = $classAssignmentService->unassignActivity($classId, $activityId);
    echo "Unassign Result: " . $unassignResult['message'] . "\n";

    // 5. Verify unassigned
    $listResult = $classAssignmentService->listClassActivities($classId);
    $found = false;
    foreach ($listResult['data'] as $act) {
        if ($act['activity_id'] === $activityId) {
            $found = true;
            break;
        }
    }
    echo "Assignment Cleared from List: " . (!$found ? "YES" : "NO") . "\n";

    if ($found === false) {
        echo "\nVerification SUCCESSFUL!\n";
    } else {
        echo "\nVerification FAILED!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
