<?php

// Direct fix script to deduplicate reports and add unique constraints
// Run with: php fix_reports_duplicates.php

$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n\n";

    // 1. Cleanup student_summary_report
    echo "Cleaning up student_summary_report duplicates...\n";
    // We keep the record with the lowest ID for each unique combination
    $deletedSummary = $pdo->exec("
        DELETE t1 FROM student_summary_report t1
        INNER JOIN student_summary_report t2 
        WHERE t1.id > t2.id 
        AND t1.student_no = t2.student_no 
        AND t1.subject_id = t2.subject_id 
        AND t1.class_id = t2.class_id 
        AND t1.academic_year = t2.academic_year 
        AND t1.term = t2.term 
        AND t1.assignment_activity_id = t2.assignment_activity_id
    ");
    echo "Cleaned up $deletedSummary duplicate summary records.\n";

    // 2. Add Unique Constraint to student_summary_report
    echo "Adding unique constraint to student_summary_report...\n";
    try {
        $pdo->exec("
            ALTER TABLE student_summary_report 
            ADD UNIQUE KEY unique_summary_entry (student_no, subject_id, class_id, academic_year, term, assignment_activity_id)
        ");
        echo "✓ Unique constraint 'unique_summary_entry' added successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "⚠️ Note: Unique constraint 'unique_summary_entry' already exists.\n";
        } else {
            throw $e;
        }
    }

    // 3. Cleanup student_report
    echo "Cleaning up student_report duplicates...\n";
    $deletedReport = $pdo->exec("
        DELETE t1 FROM student_report t1
        INNER JOIN student_report t2 
        WHERE t1.id > t2.id 
        AND t1.student_no = t2.student_no 
        AND t1.subject_id = t2.subject_id 
        AND t1.class_id = t2.class_id 
        AND t1.academic_year = t2.academic_year 
        AND t1.term = t2.term
    ");
    echo "Cleaned up $deletedReport duplicate report records.\n";

    // 4. Add Unique Constraint to student_report
    echo "Adding unique constraint to student_report...\n";
    try {
        $pdo->exec("
            ALTER TABLE student_report 
            ADD UNIQUE KEY unique_report_entry (student_no, subject_id, class_id, academic_year, term)
        ");
        echo "✓ Unique constraint 'unique_report_entry' added successfully.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "⚠️ Note: Unique constraint 'unique_report_entry' already exists.\n";
        } else {
            throw $e;
        }
    }

    echo "\n✅ Report deduplication and unique constraints applied successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
