<?php
require_once 'vendor/autoload.php';

use App\Services\GradingSchemeService;
use App\Services\ValidationService;
use App\Core\Database;

echo "Starting verification for Grading Scheme...\n";

try {
    $validationService = new ValidationService();
    $gradingService = new GradingSchemeService($validationService);

    // 1. Create a grading entry
    echo "1. Creating Grading Entry...\n";
    $createResult = $gradingService->createGrading([
        'grade' => 'A+',
        'grade_from' => 90,
        'grade_to' => 100,
        'remarks' => 'Excellent+'
    ], 'admin');
    $id = $createResult['data']['id'];
    echo "Created Entry ID: $id\n";

    // 2. List grading scheme
    echo "2. Listing grading scheme...\n";
    $listResult = $gradingService->listGrading();
    $found = false;
    foreach ($listResult['data'] as $entry) {
        if ($entry['id'] == $id) {
            $found = true;
            break;
        }
    }
    echo "Entry Found in List: " . ($found ? "YES" : "NO") . "\n";

    // 3. Update grading entry
    echo "3. Updating Grading Entry...\n";
    $gradingService->updateGrading($id, [
        'grade' => 'A++',
        'grade_from' => 95,
        'grade_to' => 100,
        'remarks' => 'Outstanding'
    ]);
    
    // Verify update
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT grade FROM grading_scheme WHERE id = ?");
    $stmt->execute([$id]);
    $grade = $stmt->fetchColumn();
    echo "Updated Grade: $grade\n";

    // 4. Delete grading entry
    echo "4. Deleting Grading Entry...\n";
    $gradingService->deleteGrading($id);

    // 5. Verify deleted
    $stmt = $db->prepare("SELECT COUNT(*) FROM grading_scheme WHERE id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();
    echo "Entry count after delete: $count\n";

    if ($found && $grade === 'A++' && $count == 0) {
        echo "\nVerification SUCCESSFUL!\n";
    } else {
        echo "\nVerification FAILED!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
