<?php

// Test script for report unique constraints
// Run with: php test_report_constraints.php

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

use App\Core\Database;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Connected to database successfully.\n\n";

    $testStudent = 'TEST_STU_001';
    $academicYear = '2025/2026';
    $term = 'Term 1';

    // 1. CLEAR existing test data
    $pdo->exec("DELETE FROM student_summary_report WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_report WHERE student_no = '$testStudent'");
    echo "Cleared existing test data.\n";

    // 2. TEST student_summary_report unique constraint
    echo "\nTesting student_summary_report unique constraint...\n";
    $summaryData = [
        ':student_no' => $testStudent,
        ':subject_id' => 1,
        ':class_id' => 1,
        ':academic_year' => $academicYear,
        ':term' => $term,
        ':assignment_activity_id' => 1,
        ':total_score' => 10.0,
        ':percentage_score' => 2.0,
        ':u_total_score' => 10.0,
        ':u_percentage_score' => 2.0
    ];

    $sql = "
        INSERT INTO student_summary_report 
            (student_no, subject_id, class_id, academic_year, term, assignment_activity_id, total_score, percentage_score)
        VALUES 
            (:student_no, :subject_id, :class_id, :academic_year, :term, :assignment_activity_id, :total_score, :percentage_score)
        ON DUPLICATE KEY UPDATE 
            total_score = :u_total_score,
            percentage_score = :u_percentage_score,
            updated_at = NOW()
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($summaryData);
    echo "First summary insert successful.\n";

    // Try inserting again with same unique keys but different score
    $summaryData[':u_total_score'] = 15.0; // Correct parameter for the duplicate attempt
    $stmt->execute($summaryData);
    echo "Second summary insert (duplicate) with ON DUPLICATE KEY UPDATE successful.\n";

    // Verify count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_summary_report WHERE student_no = :student_no");
    $stmt->execute([':student_no' => $testStudent]);
    $count = $stmt->fetchColumn();

    if ($count == 1) {
        echo "✅ SUCCESS: student_summary_report unique constraint handled correctly (Only 1 record found).\n";
    } else {
        echo "❌ FAILURE: Found $count records in student_summary_report. Expected 1.\n";
    }

    // 3. TEST student_report unique constraint
    echo "\nTesting student_report unique constraint...\n";
    $reportData = [
        ':student_no' => $testStudent,
        ':subject_id' => 1,
        ':class_id' => 1,
        ':academic_year' => $academicYear,
        ':term' => $term,
        ':sba_raw_score' => 10,
        ':sba_50' => 5,
        ':exam_raw_score' => 20,
        ':exam_50' => 10,
        ':total_100' => 15,
        ':grade' => 'A',
        ':remarks' => 'Good',
        ':entered_by' => 'test',
        ':u_total_100' => 15
    ];

    $reportSql = "
        INSERT INTO student_report 
            (student_no, subject_id, class_id, academic_year, term, sba_raw_score, `sba_50%`, exam_raw_score, `exam_50%`, `total_score_100%`, grade, remarks, entered_by, entered_on)
        VALUES 
            (:student_no, :subject_id, :class_id, :academic_year, :term, :sba_raw_score, :sba_50, :exam_raw_score, :exam_50, :total_100, :grade, :remarks, :entered_by, NOW())
        ON DUPLICATE KEY UPDATE 
            `total_score_100%` = :u_total_100,
            entered_on = NOW()
    ";
    
    $stmt = $pdo->prepare($reportSql);
    $stmt->execute($reportData);
    echo "First report insert successful.\n";

    // Try again
    $stmt->execute($reportData);
    echo "Second report insert (duplicate) with ON DUPLICATE KEY UPDATE successful.\n";

    // Verify count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM student_report WHERE student_no = :student_no");
    $countStmt->execute([':student_no' => $testStudent]);
    $count = $countStmt->fetchColumn();

    if ($count == 1) {
        echo "✅ SUCCESS: student_report unique constraint handled correctly (Only 1 record found).\n";
    } else {
        echo "❌ FAILURE: Found $count records in student_report. Expected 1.\n";
    }

    // CLEANUP
    $pdo->exec("DELETE FROM student_summary_report WHERE student_no = '$testStudent'");
    $pdo->exec("DELETE FROM student_report WHERE student_no = '$testStudent'");

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
