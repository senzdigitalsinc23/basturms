<?php
require_once 'vendor/autoload.php';

use App\Services\AssignmentActivityService;
use App\Services\ValidationService;

echo "Starting verification for Automatic Activity Generation...\n";

try {
    $validationService = new ValidationService();
    $service = new AssignmentActivityService($validationService);

    $activityName = 'Test Exercise ' . uniqid();
    $expectedCount = 3;

    echo "Creating Assignment Activity: $activityName with $expectedCount expected...\n";
    $result = $service->createActivity([
        'act_name' => $activityName,
        'expected_per_term' => $expectedCount,
        'weight' => 15,
        'academic_year' => '2024/2025',
        'term' => 'Term 2'
    ], 'admin');

    $activityId = $result['data']['activity_id'];
    echo "Activity ID: $activityId\n";

    echo "Checking 'activities' table for generated records...\n";
    $db = App\Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM activities WHERE act_id = ? ORDER BY activity_name ASC");
    $stmt->execute([$activityId]);
    $generated = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($generated) . " records.\n";
    foreach ($generated as $row) {
        echo "- " . $row['activity_name'] . " (Status: " . $row['status'] . ")\n";
    }

    if (count($generated) === $expectedCount) {
        echo "\nVerification SUCCESSFUL!\n";
    } else {
        echo "\nVerification FAILED! Expected $expectedCount but found " . count($generated) . ".\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
