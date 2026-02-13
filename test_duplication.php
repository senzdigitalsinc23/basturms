<?php

require 'vendor/autoload.php';

use App\Core\Database;

// Test duplication prevention
$db = Database::getInstance()->getConnection();

$student_no = 'TEST_STU_001';
$subject_id = 1;
$activity_id = 1;
$class_id = 1;
$academic_year = '2024/2025';
$term = 'Term 1';

echo "Testing score duplication prevention...\n";

try {
    // 1. Clear existing test data
    $db->exec("DELETE FROM scores WHERE student_no = '$student_no'");
    echo "✓ Cleaned up test data.\n";

    // 2. Insert first score
    $sql = "
        INSERT INTO scores (student_no, subject_id, activity_id, class_id, academic_year, term, score, entered_by, entered_on)
        VALUES (:student_no, :subject_id, :activity_id, :class_id, :academic_year, :term, :score, :entered_by, NOW())
        ON DUPLICATE KEY UPDATE 
            score = :u_score, 
            entered_by = :u_entered_by, 
            entered_on = NOW()
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':student_no' => $student_no,
        ':subject_id' => $subject_id,
        ':activity_id' => $activity_id,
        ':class_id' => $class_id,
        ':academic_year' => $academic_year,
        ':term' => $term,
        ':score' => 70.5,
        ':entered_by' => 'tester',
        ':u_score' => 70.5,
        ':u_entered_by' => 'tester'
    ]);
    echo "✓ Inserted first score (70.5).\n";

    // 3. Insert second score for same combination
    $stmt->execute([
        ':student_no' => $student_no,
        ':subject_id' => $subject_id,
        ':activity_id' => $activity_id,
        ':class_id' => $class_id,
        ':academic_year' => $academic_year,
        ':term' => $term,
        ':score' => 85.0,
        ':entered_by' => 'tester',
        ':u_score' => 85.0,
        ':u_entered_by' => 'tester'
    ]);
    echo "✓ Attempted to insert second score (85.0) for the same combination.\n";

    // 4. Check record count and value
    $stmt = $db->query("SELECT COUNT(*) as count, score FROM scores WHERE student_no = '$student_no'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\nResults:\n";
    echo "  Total records: " . $result['count'] . "\n";
    echo "  Current score: " . $result['score'] . "\n";

    if ($result['count'] == 1 && $result['score'] == 85.0) {
        echo "\n✅ SUCCESS: Only one record exists and it was updated to 85.0!\n";
    } else {
        echo "\n❌ FAILURE: Duplication detection failed.\n";
    }

    // 5. Cleanup
    $db->exec("DELETE FROM scores WHERE student_no = '$student_no'");
    echo "\n✓ Test data cleaned up.\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
