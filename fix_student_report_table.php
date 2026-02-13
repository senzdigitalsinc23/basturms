<?php

// Direct migration script to fix student_report table
// This bypasses the migration framework to apply the fix immediately

$host = '127.0.0.1';
$dbname = 'basturms_db';
$user = 'root';
$pass = 'tem22ple12345?';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n\n";
    
    // Check if student_report table exists
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM information_schema.tables 
        WHERE table_schema = '$dbname' 
        AND table_name = 'student_report'
    ");
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $tableExists = $result['count'] > 0;
    
    if (!$tableExists) {
        echo "Creating student_report table...\n";
        
        $pdo->exec("
            CREATE TABLE student_report (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(20) NOT NULL,
                subject_id INT NOT NULL,
                class_id INT NOT NULL,
                academic_year VARCHAR(9) NOT NULL,
                term VARCHAR(20) NOT NULL,
                sba_raw_score DECIMAL(6,2) NOT NULL DEFAULT 0,
                `sba_50%` DECIMAL(5,2) NOT NULL DEFAULT 0,
                exam_raw_score DECIMAL(6,2) NOT NULL DEFAULT 0,
                `exam_50%` DECIMAL(5,2) NOT NULL DEFAULT 0,
                `total_score_100%` DECIMAL(5,2) NOT NULL DEFAULT 0,
                grade VARCHAR(2) NOT NULL DEFAULT '9',
                remarks VARCHAR(50) NOT NULL DEFAULT 'N/A',
                entered_by VARCHAR(20) NOT NULL,
                entered_on DATETIME NOT NULL,
                
                -- Foreign keys
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
                
                -- Indexes for performance
                INDEX idx_student_subject (student_no, subject_id),
                INDEX idx_academic_term (academic_year, term),
                INDEX idx_class (class_id),
                
                -- Unique constraint to prevent duplicate report entries
                UNIQUE KEY unique_report_entry (student_no, subject_id, class_id, academic_year, term)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        
        echo "✓ Table created successfully with correct data types.\n";
    } else {
        echo "Table exists. Checking current column types...\n";
        
        // Get current column information
        $stmt = $pdo->query("DESCRIBE student_report");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nCurrent schema:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']}: {$col['Type']}\n";
        }
        
        echo "\nAltering columns to fix numeric overflow issue...\n";
        
        $pdo->exec("
            ALTER TABLE student_report 
            MODIFY COLUMN sba_raw_score DECIMAL(6,2) NOT NULL DEFAULT 0,
            MODIFY COLUMN `sba_50%` DECIMAL(5,2) NOT NULL DEFAULT 0,
            MODIFY COLUMN exam_raw_score DECIMAL(6,2) NOT NULL DEFAULT 0,
            MODIFY COLUMN `exam_50%` DECIMAL(5,2) NOT NULL DEFAULT 0,
            MODIFY COLUMN `total_score_100%` DECIMAL(5,2) NOT NULL DEFAULT 0
        ");
        
        echo "✓ Columns altered successfully.\n";
    }
    
    echo "\nVerifying new schema:\n";
    $stmt = $pdo->query("DESCRIBE student_report");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['sba_raw_score', 'sba_50%', 'exam_raw_score', 'exam_50%', 'total_score_100%'])) {
            echo "  ✓ {$col['Field']}: {$col['Type']}\n";
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nThe student_report table now uses DECIMAL types that can handle larger score values.\n";
    echo "You can now retry the GenerateStudentReportJob.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
