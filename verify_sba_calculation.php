<?php

// Comprehensive verification script for SBA 50% calculation
// Run with: php verify_sba_calculation.php

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

    $testStudent = 'VERIFY_SBA_001';
    $academicYear = '2025/2026';
    $term = 'Term 1';
    $subjectId = 1;
    $classId = 1;

    // 1. Setup Test Data
    echo "Setting up test data...\n";
    $pdo->exec("DELETE FROM scores WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_summary_report WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_report WHERE student_no = '$testStudent'");

    // Fetch multiple activities for 'Excercise' type
    $stmt = $pdo->query("
        SELECT a.id, aa.weight, aa.expected_per_term 
        FROM activities a 
        JOIN assignment_activities aa ON a.act_id = aa.activity_id 
        WHERE aa.act_name = 'Excercise' 
        LIMIT 3
    ");
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($activities)) {
        throw new Exception("Could not find any activities for 'Excercise' type.");
    }

    $weight = (float)$activities[0]['weight'];
    $expected = (int)$activities[0]['expected_per_term'];
    
    echo "Using activities for 'Excercise' (Weight: $weight, Expected: $expected)\n";

    $scoresValue = [80, 90, 70];
    $totalRaw = 0;
    
    $insertScore = $pdo->prepare("INSERT INTO scores (student_no, subject_id, activity_id, class_id, academic_year, term, score, entered_by, entered_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    foreach ($activities as $idx => $act) {
        if ($idx >= count($scoresValue)) break;
        $score = $scoresValue[$idx];
        $insertScore->execute([$testStudent, $subjectId, $act['id'], $classId, $academicYear, $term, $score, 'test']);
        $totalRaw += $score;
        echo "Inserted score $score for activity ID: {$act['id']}\n";
    }

    // 2. Run SummarizeScoresJob
    echo "\nRunning SummarizeScoresJob...\n";
    $summarizer = new SummarizeScoresJob();
    $summarizer->handle($academicYear, $term);

    // Verify summary
    $stmt = $pdo->prepare("SELECT percentage_score FROM student_summary_report WHERE student_no = ? AND subject_id = ?");
    $stmt->execute([$testStudent, $subjectId]);
    $summaryScore = $stmt->fetchColumn();
    echo "Summary Percentage Score: $summaryScore\n";
    
    $expectedSummary = ($totalRaw / ($expected * 100)) * $weight;
    echo "Expected Summary Percentage Score: $expectedSummary\n";

    // 3. Run GenerateStudentReportJob
    echo "\nRunning GenerateStudentReportJob...\n";
    $reporter = new GenerateStudentReportJob();
    $reporter->handle($academicYear, $term);

    // 4. Verify Final Report
    $stmt = $pdo->prepare("SELECT sba_raw_score, `sba_50%`, exam_raw_score, `exam_50%`, `total_score_100%` FROM student_report WHERE student_no = ? AND subject_id = ?");
    $stmt->execute([$testStudent, $subjectId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "\nFinal Report for student $testStudent:\n";
    print_r($report);

    // Scaling Calculation:
    // If "Excercise" is the only SBA activity, it should be scaled to 50.
    // result = (summaryScore / weight) * 50
    $expectedSba50 = ($summaryScore / $weight) * 50; 
    echo "Expected Scaled SBA 50%: $expectedSba50\n";

    if (abs($report['sba_50%'] - $expectedSba50) < 0.01) {
        echo "✅ SUCCESS: SBA 50% calculation is correct and scaled properly.\n";
    } else {
        echo "❌ FAILURE: SBA 50% calculation mismatch.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
