<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AssignmentActivityService;
use App\Services\ValidationService;
use App\Core\Database;

$db = Database::getInstance()->getConnection();
$service = new AssignmentActivityService(new ValidationService());

echo "=== Testing Sub-Activity Generation ===\n\n";

try {
    // 1. Create a new Assignment Activity
    echo "Creating new assignment activity...\n";
    
    // Using a mock user ID
    $userId = 'test_usr_' . uniqid();
    
    $data = [
        'act_name' => 'Test Exercise',
        'expected_per_term' => 3,
        'weight' => 20,
        'academic_year' => '2025/2026',
        'term' => 'Term 2' // Use a future term/year to avoid conflicts with active data if any
    ];
    
    // We expect the service to auto-generate activity_id
    $result = $service->createActivity($data, $userId);
    
    if (!$result['success']) {
        throw new Exception("Failed to create activity: " . print_r($result, true));
    }
    
    $parentActivityId = $result['data']['activity_id'];
    echo "✓ Parent Activity Created: {$parentActivityId}\n\n";
    
    // 2. Verify Sub-Activities
    echo "Verifying sub-activities in 'activities' table...\n";
    
    $sql = "SELECT * FROM activities WHERE act_id = :act_id ORDER BY id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':act_id' => $parentActivityId]);
    $subActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = count($subActivities);
    echo "Found {$count} sub-activities (Expected: 3)\n";
    
    if ($count !== 3) {
        throw new Exception("Incorrect number of sub-activities generated.");
    }
    
    foreach ($subActivities as $index => $activity) {
        $expectedSuffix = $index + 1;
        $expectedSubId = $parentActivityId . $expectedSuffix;
        
        echo "  [{$index}] Sub-Activity ID: {$activity['sub_activity_id']} (Expected: {$expectedSubId})\n";
        
        if ($activity['sub_activity_id'] !== $expectedSubId) {
            throw new Exception("Mismatch in sub_activity_id for index {$index}");
        }
    }
    
    echo "\n✓ Sub-activity IDs verified successfully\n\n";
    
    // 3. Cleanup
    echo "Cleaning up test data...\n";
    $db->prepare("DELETE FROM activities WHERE act_id = ?")->execute([$parentActivityId]);
    $db->prepare("DELETE FROM assignment_activities WHERE activity_id = ?")->execute([$parentActivityId]);
    echo "✓ Cleanup complete\n\n";
    
    echo "=== All Tests PASSED ✓ ===\n";

} catch (Exception $e) {
    echo "\n❌ ERROR TYPE: " . get_class($e) . "\n";
    echo "❌ ERROR MSG: " . $e->getMessage() . "\n";
    exit(1);
}
