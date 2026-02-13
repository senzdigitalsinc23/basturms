<?php

// Multi-activity verification script for SBA 50% calculation
// Run with: php verify_multi_sba.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set environment variables for database connection
putenv('DB_HOST=127.0.0.1');
putenv('DB_NAME=basturms_db');
putenv('DB_USER=root');
putenv('DB_PASS=tem22ple12345?');

require_once 'Core/Config.php';
require_once 'Core/Logger.php';
require_once 'Core/Database.php';
require_once 'Core/Queue.php';
require_once 'jobs/SummarizeScoresJob.php';
require_once 'jobs/GenerateStudentReportJob.php';

use App\Core\Database;
use Jobs\SummarizeScoresJob;
use Jobs\GenerateStudentReportJob;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Connected to database successfully.\n\n";

    $testStudent = 'VERIFY_MULTI_002';
    $academicYear = '2025/2026';
    $term = 'Term 1';
    $subjectId = 1;
    $classId = 1;

    // 1. Setup Test Data
    echo "Setting up test data...\n";
    $pdo->exec("DELETE FROM scores WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_summary_report WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_report WHERE student_no = '$testStudent'");

    // Fetch activities for 'Excercise' (Expected should be 5, Weight 20)
    $stmt = $pdo->query("SELECT a.id, aa.weight, aa.expected_per_term FROM activities a JOIN assignment_activities aa ON a.act_id = aa.activity_id WHERE aa.act_name = 'Excercise' AND aa.academic_year = '$academicYear' AND aa.term = '$term' LIMIT 3");
    $exActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($exActivities)) {
        throw new Exception("Missing 'Excercise' activity type for current term.");
    }

    // CREATE a temporary 'Quiz' activity
    echo "Creating a mock 'Quiz' activity for testing...\n";
    $pdo->exec("DELETE FROM activities WHERE act_id = 'QUIZ_MOCK'");
    $pdo->exec("DELETE FROM assignment_activities WHERE activity_id = 'QUIZ_MOCK'");
    
    $pdo->exec("INSERT INTO assignment_activities (activity_id, act_name, expected_per_term, weight, academic_year, term, status, added_by, added_on) 
                VALUES ('QUIZ_MOCK', 'Quiz', 1, 10, '$academicYear', '$term', 'active', 'system', NOW())");
    $pdo->exec("INSERT INTO activities (act_id, activity_name, status, added_on, sub_activity_id) 
                VALUES ('QUIZ_MOCK', 'Test Quiz', 'active', NOW(), '')");
    
    $quizActId = $pdo->lastInsertId();
    echo "Created Quiz activity with ID: $quizActId\n";

    $insertScore = $pdo->prepare("INSERT INTO scores (student_no, subject_id, activity_id, class_id, academic_year, term, score, entered_by, entered_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    // Insert 3 exercises (80, 90, 70) 
    // Weight=20, Expected=5. Sum=240. Weighted summary = (240 / 500) * 20 = 9.6
    foreach ($exActivities as $idx => $act) {
        $insertScore->execute([$testStudent, $subjectId, $act['id'], $classId, $academicYear, $term, [80, 90, 70][$idx], 'test']);
    }
    
    // Insert 1 Quiz (80)
    // Weight=10, Expected=1. Sum=80. Weighted summary = (80 / 100) * 10 = 8.0
    $insertScore->execute([$testStudent, $subjectId, $quizActId, $classId, $academicYear, $term, 80, 'test']);

    // 2. Run Jobs
    echo "\nRunning Jobs...\n";
    (new SummarizeScoresJob())->handle($academicYear, $term);
    (new GenerateStudentReportJob())->handle($academicYear, $term);

    // 3. Verify
    $stmt = $pdo->prepare("SELECT sba_raw_score, `sba_50%`, `total_score_100%` FROM student_report WHERE student_no = ? AND subject_id = ?");
    $stmt->execute([$testStudent, $subjectId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\nFinal Report for multi-activity student $testStudent:\n";
    print_r($report);

    // Calculation Check:
    // sba_50% = ((9.6 + 8.0) / (20 + 10)) * 50 = (17.6 / 30) * 50 = 29.333
    $expected = (17.6 / 30) * 50;
    echo "Expected Scaled SBA 50%: $expected\n";

    if (abs($report['sba_50%'] - $expected) < 0.01) {
        echo "✅ SUCCESS: Multi-activity scaling is correct.\n";
    } else {
        echo "❌ FAILURE: Multi-activity scaling mismatch.\n";
    }

    // Cleanup mock data
    $pdo->exec("DELETE FROM activities WHERE act_id = 'QUIZ_MOCK'");
    $pdo->exec("DELETE FROM assignment_activities WHERE activity_id = 'QUIZ_MOCK'");

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
