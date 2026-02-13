<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Models\Student;
use App\Models\Activity;
use App\Models\AssignmentActivity;
use Jobs\SummarizeScoresJob;

$db = Database::getInstance()->getConnection();

try {
    // 1. Setup Data
    echo "Setting up test data...\n";
    $academicYear = '2025/2026';
    $term = 'Term 1';
    
    // Create Student
    $studentNo = 'STEST001';
    // Clean up first
    $db->prepare("DELETE FROM students WHERE student_no = ?")->execute([$studentNo]);
    
    $db->prepare("INSERT INTO students (student_no, first_name, last_name, gender, dob) VALUES (?, 'Test', 'Student', 'M', '2010-01-01')")->execute([$studentNo]);
    // admission_details might not be strictly needed for scores, but let's clear potential left-overs
    $db->prepare("DELETE FROM admission_details WHERE student_no = ?")->execute([$studentNo]);

    
    
    // Create Assignment Activity (Group) - e.g. Exercise
    $actId = 'TEST_EX';
    $assignActId = 0;
    
    $stmt = $db->prepare("SELECT id FROM assignment_activities WHERE activity_id = ?");
    $stmt->execute([$actId]);
    if ($row = $stmt->fetch()) {
        $assignActId = $row['id'];
    } else {
        $db->prepare("INSERT INTO assignment_activities (activity_id, act_name, expected_per_term, weight, academic_year, term, added_by, status) VALUES (?, 'Test Exercise', 2, 10, ?, ?, 'system', 'active')")->execute([$actId, $academicYear, $term]);
        $assignActId = $db->lastInsertId();
    }

    // Create Activities (Individual) - e.g. Exercise 1, Exercise 2
    $act1Id = 0;
    $act2Id = 0;
    
    // Check if activities exist first to avoid duplicates if re-running
    $stmt = $db->prepare("SELECT id FROM activities WHERE activity_name = ? AND act_id = ?");
    $stmt->execute(['Test Exercise 1', $actId]);
    if ($row = $stmt->fetch()) {
        $act1Id = $row['id'];
    } else {
        // Assuming sub_activity_id is the unique key causing issue, giving it a unique value
        // Also handling if column doesn't exist by catching or checking? 
        // Best guess: sub_activity_id was added and made unique.
        $db->prepare("INSERT INTO activities (act_id, sub_activity_id, activity_name, status, added_on) VALUES (?, ?, 'Test Exercise 1', 'active', NOW())")->execute([$actId, 'TEX1']);
        $act1Id = $db->lastInsertId();
    }
    
    $stmt->execute(['Test Exercise 2', $actId]);
    if ($row = $stmt->fetch()) {
        $act2Id = $row['id'];
    } else {
        $db->prepare("INSERT INTO activities (act_id, sub_activity_id, activity_name, status, added_on) VALUES (?, ?, 'Test Exercise 2', 'active', NOW())")->execute([$actId, 'TEX2']);
        $act2Id = $db->lastInsertId();
    }
    
    // Insert Scores
    // We need subject and class too. Let's pick existing or create dummy.
    // Assuming class_id 1 and subject_id 1 exist for simplicity in existing dev env, or checking first.
    // Better to check.
    $classId = 1;
    $subjectId = 1;
    
    // Check if class 1 exists
    $stmt = $db->prepare("SELECT id FROM classes LIMIT 1");
    $stmt->execute();
    if ($row = $stmt->fetch()) {
        $classId = $row['id'];
    }
    
    // Check if subject 1 exists
    $stmt = $db->prepare("SELECT id FROM subjects LIMIT 1");
    $stmt->execute();
    if ($row = $stmt->fetch()) {
        $subjectId = $row['id'];
    }
    
    echo "Using Class ID: $classId, Subject ID: $subjectId\n";

    $db->prepare("DELETE FROM scores WHERE student_no = ? AND subject_id = ? AND academic_year = ? AND term = ?")->execute([$studentNo, $subjectId, $academicYear, $term]);
    
    // Insert Score 1: 15
    $db->prepare("INSERT INTO scores (student_no, subject_id, activity_id, class_id, academic_year, term, score, entered_by, entered_on) VALUES (?, ?, ?, ?, ?, ?, 15, 'system', NOW())")->execute([$studentNo, $subjectId, $act1Id, $classId, $academicYear, $term]);
    
    // Insert Score 2: 25
    $db->prepare("INSERT INTO scores (student_no, subject_id, activity_id, class_id, academic_year, term, score, entered_by, entered_on) VALUES (?, ?, ?, ?, ?, ?, 25, 'system', NOW())")->execute([$studentNo, $subjectId, $act2Id, $classId, $academicYear, $term]);
    
    // 2. Run Job
    echo "Running SummarizeScoresJob...\n";
    $job = new SummarizeScoresJob();
    $job->handle($academicYear, $term);
    
    // 3. Verify Result
    $stmt = $db->prepare("SELECT total_score FROM student_summary_report WHERE student_no = ? AND subject_id = ? AND assignment_activity_id = ? AND academic_year = ? AND term = ?");
    $stmt->execute([$studentNo, $subjectId, $assignActId, $academicYear, $term]);
    
    if ($row = $stmt->fetch()) {
        echo "Total Score found: " . $row['total_score'] . "\n";
        if (floatval($row['total_score']) === 40.0) {
            echo "SUCCESS: Score matches expected (15 + 25 = 40).\n";
        } else {
            echo "FAILURE: Score mismatch. Expected 40.\n";
        }
    } else {
        echo "FAILURE: Summary record not found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    // Cleanup
    // $db->prepare("DELETE FROM scores WHERE student_no = ?")->execute(['STEST001']);
    // $db->prepare("DELETE FROM students WHERE student_no = ?")->execute(['STEST001']);
    // $db->prepare("DELETE FROM assignment_activities WHERE id = ?")->execute([$assignActId]);
    // $db->prepare("DELETE FROM activities WHERE id IN (?, ?)")->execute([$act1Id, $act2Id]);
}
