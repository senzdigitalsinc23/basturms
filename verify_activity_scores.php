<?php
require_once __DIR__ . '/vendor/autoload.php';

$db = \App\Core\Database::getInstance()->getConnection();
$service = new \App\Services\StudentScoreService();
$repo = new \App\Repositories\StudentScoreRepository();

echo "=== Testing Activity Score Entry (Service Layer) ===\n\n";

try {
    // 1. Find a student, subject, activity, and class
    $student = $db->query("SELECT student_no FROM students LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $subject = $db->query("SELECT id FROM subjects LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $activity = $db->query("SELECT id, act_name FROM assignment_activities LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $class = $db->query("SELECT id FROM classes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $academicYear = $db->query("SELECT academic_year FROM academic_years WHERE status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$student || !$subject || !$activity || !$class || !$academicYear) {
        throw new Exception("Missing required data (student, subject, activity, class, or active academic year)");
    }
    
    $studentNo = $student['student_no'];
    $subjectId = (int)$subject['id'];
    $activityId = (int)$activity['id'];
    $classId = (int)$class['id'];
    $year = $academicYear['academic_year'];
    
    echo "Test Data:\n";
    echo "  Student: {$studentNo}\n";
    echo "  Subject ID: {$subjectId}\n";
    echo "  Activity ID: {$activityId} ({$activity['act_name']})\n";
    echo "  Class ID: {$classId}\n";
    echo "  Academic Year: {$year}\n\n";
    
    // 2. Add an activity score via Service
    echo "Adding activity score via Service...\n";
    $result = $service->addActivityScore(
        $studentNo,
        $subjectId,
        $activityId,
        $classId,
        $year,
        'Term 1',
        92.5,
        'system_check'
    );
    
    if (!$result['success']) {
        throw new Exception("Failed to add activity score: " . json_encode($result));
    }
    
    echo "✓ Activity score added successfully via Service\n\n";
    
    // 3. Retrieve the score via Repository (to verify storage)
    echo "Retrieving activity scores...\n";
    $scores = $repo->getActivityScores($studentNo, $subjectId, $year, 'Term 1');
    
    $found = false;
    foreach ($scores as $s) {
        if ($s['activity_id'] == $activityId && $s['score'] == 92.5) {
            $found = true;
            echo "✓ Found expected score: {$s['score']} for activity '{$s['activity_name']}'\n";
            break;
        }
    }
    
    if (!$found) {
        throw new Exception("Score not found in database verification");
    }
    
    // 4. Cleanup
    echo "\nCleaning up test data...\n";
    $db->prepare("DELETE FROM scores WHERE student_no = ? AND subject_id = ? AND activity_id = ? AND entered_by = 'system_check'")
       ->execute([$studentNo, $subjectId, $activityId]);
    echo "✓ Cleanup complete\n\n";
    
    echo "=== All Tests PASSED ✓ ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR TYPE: " . get_class($e) . "\n";
    echo "❌ ERROR MSG: " . $e->getMessage() . "\n";
    exit(1);
}
