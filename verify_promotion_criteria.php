<?php
require_once 'vendor/autoload.php';

use App\Services\PromotionCriteriaService;
use App\Services\ValidationService;
use App\Core\Database;

echo "Starting verification for Promotion Criteria...\n";

try {
    $validationService = new ValidationService();
    $service = new PromotionCriteriaService($validationService);

    // 1. Create a criteria entry
    echo "1. Creating Promotion Criteria Entry...\n";
    
    // We need a valid class_id/level_id. Let's assume 'SHS1' or similar exists, or query one.
    // Or try to insert a random one and rely on schema (it references classes table).
    // Let's first check available classes or assume 'C1' or similar from earlier logs or migrations?
    // Migration `20250115000037_create_promotion_criteria_table.php` has FOREIGN KEY (level_id) REFERENCES classes(class_id)
    // So we must have a class. 
    // Let's query one class.
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT class_id FROM classes LIMIT 1");
    $classId = $stmt->fetchColumn();
    
    if (!$classId) {
        // Create a dummy class if none exist
        echo "No classes found. Creating dummy class 'TEST_CLASS'...\n";
        $db->exec("INSERT INTO classes (class_id, class_name) VALUES ('TEST_CLASS', 'Test Class')");
        $classId = 'TEST_CLASS';
    }
    
    echo "Using Class ID: $classId\n";

    $createResult = $service->createCriteria([
        'level_id' => $classId,
        'min_score' => 50,
        'min_pass_mark' => 50,
        'min_electives' => 3
    ], 'admin');
    
    $id = $createResult['data']['id'];
    echo "Created Criteria ID: $id\n";

    // 2. List criteria
    echo "2. Listing criteria...\n";
    $listResult = $service->listCriteria();
    $found = false;
    foreach ($listResult['data'] as $entry) {
        if ($entry['id'] == $id) {
            $found = true;
            break;
        }
    }
    echo "Entry Found in List: " . ($found ? "YES" : "NO") . "\n";

    // 3. Update criteria
    echo "3. Updating Criteria...\n";
    $service->updateCriteria($id, [
        'min_score' => 60,
        'min_pass_mark' => 60
    ], 'admin');
    
    // Verify update
    $stmt = $db->prepare("SELECT min_score FROM promotion_criteria WHERE id = ?");
    $stmt->execute([$id]);
    $score = $stmt->fetchColumn();
    echo "Updated Min Score: $score\n";

    // 4. Delete criteria
    echo "4. Deleting Criteria...\n";
    $service->deleteCriteria($id);

    // 5. Verify deleted
    $stmt = $db->prepare("SELECT COUNT(*) FROM promotion_criteria WHERE id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();
    echo "Entry count after delete: $count\n";

    if ($found && $score == 60 && $count == 0) {
        echo "\nVerification SUCCESSFUL!\n";
    } else {
        echo "\nVerification FAILED!\n";
    }
    
    // Cleanup if we created dummy class (optional, maybe leave it)
    if ($classId === 'TEST_CLASS') {
         $db->exec("DELETE FROM classes WHERE class_id = 'TEST_CLASS'");
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    // Check if it's related to foreign key constraint failing? 
}
