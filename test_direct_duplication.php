<?php

// Direct test script for duplication prevention using hardcoded credentials

$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n\n";

    $student_no = 'TEST_STU_001';
    $subject_id = 1;
    $activity_id = 1;
    $class_id = 1;
    $academic_year = '2024/2025';
    $term = 'Term 1';

    echo "Testing score duplication prevention...\n";

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
    $params = [
        ':student_no' => $student_no,
        ':subject_id' => $subject_id,
        ':activity_id' => $activity_id,
        ':class_id' => $class_id,
        ':academic_year' => $academic_year,
        ':term' => $term,
        ':score' => 70.5,
        ':entered_by' => 'tester'
    ];
    $update_params = [
        ':u_score' => 70.5,
        ':u_entered_by' => 'tester'
    ];
    
    $stmt->execute(array_merge($params, $update_params));
    echo "✓ Inserted first score (70.5).\n";

    // 3. Insert second score for same combination
    $params[':score'] = 85.0;
    $update_params[':u_score'] = 85.0;
    
    $stmt->execute(array_merge($params, $update_params));
    echo "✓ Attempted to insert second score (85.0) for the same combination.\n";

    // 4. Check record count and value
    $q = $db->query("SELECT COUNT(*) as count FROM scores WHERE student_no = '$student_no'");
    $count = $q->fetch(PDO::FETCH_ASSOC)['count'];
    
    $qVal = $db->query("SELECT score FROM scores WHERE student_no = '$student_no'");
    $score = $qVal->fetch(PDO::FETCH_ASSOC)['score'];

    echo "\nResults:\n";
    echo "  Total records: $count\n";
    echo "  Current score: $score\n";

    if ($count == 1 && $score == 85.0) {
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
