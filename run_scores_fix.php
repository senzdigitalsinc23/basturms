<?php

// Direct fix script to add unique constraint to scores table
// Run with: php run_scores_fix.php

$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n\n";
    
    // 1. Remove any existing duplicates just in case
    echo "Cleaning up any existing duplicate scores...\n";
    $deleted = $pdo->exec("
        DELETE s1 FROM scores s1
        INNER JOIN scores s2 
        WHERE s1.id < s2.id 
        AND s1.student_no = s2.student_no 
        AND s1.subject_id <=> s2.subject_id 
        AND s1.activity_id = s2.activity_id 
        AND s1.academic_year = s2.academic_year 
        AND s1.term = s2.term
    ");
    echo "Removed $deleted duplicate records.\n\n";

    // 2. Add the unique constraint
    echo "Adding unique constraint to scores table...\n";
    $pdo->exec("
        ALTER TABLE scores 
        ADD UNIQUE KEY unique_score_entry (student_no, subject_id, activity_id, academic_year, term)
    ");
    echo "✓ Unique constraint 'unique_score_entry' added successfully.\n\n";
    
    // 3. Verify the constraint
    echo "Verifying constraint presence...\n";
    $stmt = $pdo->query("SHOW KEYS FROM scores WHERE Key_name = 'unique_score_entry'");
    if ($stmt->fetch()) {
        echo "✅ Verification SUCCESS: Unique constraint is active.\n";
    } else {
        echo "❌ Verification FAILED: Unique constraint NOT found.\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "⚠️ Note: Unique constraint already exists.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    exit(1);
}
