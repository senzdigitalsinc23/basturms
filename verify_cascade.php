<?php
require_once 'vendor/autoload.php';

use App\Services\AssignmentActivityService;
use App\Services\ClassActivityAssignmentService;
use App\Services\ValidationService;
use App\Core\Database;

echo "Starting verification for Cascade Status and Delete...\n";

try {
    $validationService = new ValidationService();
    $activityService = new AssignmentActivityService($validationService);
    $assignmentService = new ClassActivityAssignmentService($validationService);

    $activityName = 'Cascade Test ' . uniqid();
    echo "1. Creating Activity: $activityName...\n";
    $result = $activityService->createActivity([
        'act_name' => $activityName,
        'expected_per_term' => 1,
        'weight' => 5,
        'academic_year' => '2024/2025',
        'term' => 'Term 5'
    ], 'admin');
    $activityId = $result['data']['activity_id'];

    $db = Database::getInstance()->getConnection();
    // Use an existing class
    $classId = $db->query("SELECT class_id FROM classes LIMIT 1")->fetchColumn();
    if (!$classId) throw new Exception("No class found.");

    echo "2. Assigning to class: $classId...\n";
    // Mock user session for Session::get
    App\Core\Session::set('user', ['academic_year' => '2024/2025', 'term' => 'Term 1']);
    
    $assignmentService->assignActivity([
        'class_id' => $classId,
        'act_id' => $activityId
    ], 'admin');

    echo "3. Deactivating parent activity...\n";
    $activityService->softDelete($activityId);

    // Check assignment status
    $stmt = $db->prepare("SELECT status FROM class_activity_assignment WHERE act_id = ?");
    $stmt->execute([$activityId]);
    $assignStatus = $stmt->fetchColumn();
    echo "Assignment Status after deactivation: $assignStatus\n";

    echo "4. Reactivating parent activity...\n";
    $activityService->activate($activityId);
    $stmt->execute([$activityId]);
    $assignStatus = $stmt->fetchColumn();
    echo "Assignment Status after reactivation: $assignStatus\n";

    echo "5. Permanently deleting parent activity...\n";
    $activityService->permanentDelete($activityId);

    // Check if assignment exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM class_activity_assignment WHERE act_id = ?");
    $stmt->execute([$activityId]);
    $count = $stmt->fetchColumn();
    echo "Assignment record count after permanent delete: $count\n";

    // Check if activities (Exercise 1 etc) exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM activities WHERE act_id = ?");
    $stmt->execute([$activityId]);
    $actCount = $stmt->fetchColumn();
    echo "Individual activity records count after permanent delete: $actCount\n";

    if ($assignStatus === 'active' && $count == 0 && $actCount == 0) {
        echo "\nVerification SUCCESSFUL!\n";
    } else {
        echo "\nVerification FAILED!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
