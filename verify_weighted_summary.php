<?php

// Verification script for weighted summary scores
// Run with: php verify_weighted_summary.php

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

    $testStudent = 'VERIFY_WEIGHT_004';
    $academicYear = '2025/2026';
    $term = 'Term 1';
    $subjectId = 1;
    $classId = 1;

    // 1. Setup Test Data
    echo "Setting up test data for student $testStudent...\n";
    $pdo->exec("DELETE FROM scores WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_summary_report WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_report WHERE student_no = '$testStudent'");

    // Fetch activities for 'Excercise' (Weight should be 50)
    $stmt = $pdo->prepare("SELECT a.id, aa.weight FROM activities a JOIN assignment_activities aa ON a.act_id = aa.activity_id WHERE aa.act_name = 'Excercise' AND aa.academic_year = ? AND aa.term = ? LIMIT 5");
    $stmt->execute([$academicYear, $term]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $weight = (float)$activities[0]['weight'];
    echo "Using 'Excercise' (Weight: $weight%)\n";

    $scoresValue = [18, 15, 19, 17, 19];
    $totalRaw = array_sum($scoresValue); // 88
    
    $insertScore = $pdo->prepare("INSERT INTO scores (student_no, subject_id, activity_id, class_id, academic_year, term, score, entered_by, entered_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    foreach ($activities as $idx => $act) {
        $insertScore->execute([$testStudent, $subjectId, $act['id'], $classId, $academicYear, $term, $scoresValue[$idx], 'test']);
    }

    // 2. Run SummarizeScoresJob
    echo "\nRunning SummarizeScoresJob...\n";
    (new SummarizeScoresJob())->handle($academicYear, $term);

    // Verify summary table
    $stmt = $pdo->prepare("SELECT total_score, percentage_score FROM student_summary_report WHERE student_no = ? AND subject_id = ?");
    $stmt->execute([$testStudent, $subjectId]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\nSummary Record:\n";
    print_r($summary);
    
    $expectedWeighted = $totalRaw * ($weight / 100);
    echo "Expected Weighted Score: $expectedWeighted\n";

    if (abs($summary['percentage_score'] - $expectedWeighted) < 0.01) {
        echo "✅ SUCCESS: Summary table stores weighted score ($expectedWeighted%).\n";
    } else {
        echo "❌ FAILURE: Summary table does not store weighted score.\n";
    }

    // 3. Run GenerateStudentReportJob
    echo "\nRunning GenerateStudentReportJob...\n";
    (new GenerateStudentReportJob())->handle($academicYear, $term);

    // 4. Verify Final Report
    $stmt = $pdo->prepare("SELECT sba_raw_score, `sba_50%` FROM student_report WHERE student_no = ? AND subject_id = ?");
    $stmt->execute([$testStudent, $subjectId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\nFinal Report:\n";
    print_r($report);

    if (abs($report['sba_50%'] - $expectedWeighted) < 0.01) {
        echo "✅ SUCCESS: Final report correctly aggregates pre-weighted scores.\n";
    } else {
        echo "❌ FAILURE: Final report aggregation mismatch.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
