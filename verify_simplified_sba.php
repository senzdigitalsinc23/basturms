<?php

// Verification script for simplified SBA 50% calculation
// Run with: php verify_simplified_sba.php

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

    $testStudent = 'VERIFY_SIMP_003';
    $academicYear = '2025/2026';
    $term = 'Term 1';
    $subjectId = 1;
    $classId = 1;

    // 1. Setup Test Data
    echo "Setting up test data for student $testStudent...\n";
    $pdo->exec("DELETE FROM scores WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_summary_report WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_report WHERE student_no = '$testStudent'");

    // Fetch activities for 'Excercise' (Weight should be 50 for this test)
    $stmt = $pdo->prepare("SELECT a.id, aa.weight FROM activities a JOIN assignment_activities aa ON a.act_id = aa.activity_id WHERE aa.act_name = 'Excercise' AND aa.academic_year = ? AND aa.term = ? LIMIT 5");
    $stmt->execute([$academicYear, $term]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($activities) < 5) {
        throw new Exception("Need at least 5 'Excercise' activities to match the example.");
    }

    $weight = (float)$activities[0]['weight'];
    echo "Using 'Excercise' (Weight: $weight%)\n";

    $scoresValue = [18, 15, 19, 17, 19];
    $totalRaw = array_sum($scoresValue);
    
    $insertScore = $pdo->prepare("INSERT INTO scores (student_no, subject_id, activity_id, class_id, academic_year, term, score, entered_by, entered_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    foreach ($activities as $idx => $act) {
        $score = $scoresValue[$idx];
        $insertScore->execute([$testStudent, $subjectId, $act['id'], $classId, $academicYear, $term, $score, 'test']);
        echo "Inserted score $score for activity ID: {$act['id']}\n";
    }

    // 2. Run Jobs
    echo "\nRunning Jobs...\n";
    (new SummarizeScoresJob())->handle($academicYear, $term);
    (new GenerateStudentReportJob())->handle($academicYear, $term);

    // 3. Verify
    $stmt = $pdo->prepare("SELECT sba_raw_score, `sba_50%`, `total_score_100%` FROM student_report WHERE student_no = ? AND subject_id = ?");
    $stmt->execute([$testStudent, $subjectId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\nFinal Report for student $testStudent:\n";
    print_r($report);

    // User's expectation: (50/100) * 88 = 44
    $expected = ($weight / 100) * $totalRaw;
    echo "Expected Scaled SBA Portion: $expected\n";

    if (abs($report['sba_50%'] - $expected) < 0.01) {
        echo "✅ SUCCESS: Simplified calculation matches user example (Portion is ". $report['sba_50%'] . " for sum of $totalRaw).\n";
    } else {
        echo "❌ FAILURE: Calculation mismatch.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
