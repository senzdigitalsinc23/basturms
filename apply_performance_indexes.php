<?php
/**
 * Apply Performance Indexes Migration
 * 
 * This script applies database indexes to improve query performance.
 * Run this after backing up your database.
 */

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

echo "=== Database Performance Indexes Migration ===\n\n";

try {
    // Get database connection
    $db = \App\Core\Database::getInstance()->getConnection();
    
    echo "Connected to database: " . ($_ENV['DB_NAME'] ?? 'unknown') . "\n\n";
    
    // Read the migration SQL file
    $sqlFile = __DIR__ . '/Database/Migrations/add_performance_indexes.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            // Filter out comments and empty statements
            return !empty($stmt) && 
                   !str_starts_with($stmt, '--') && 
                   !str_starts_with($stmt, '/*');
        }
    );
    
    echo "Found " . count($statements) . " SQL statements to execute\n\n";
    
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    
    // Execute each statement
    foreach ($statements as $index => $statement) {
        // Extract index name for better logging
        if (preg_match('/CREATE INDEX.*?(\w+)\s+ON/i', $statement, $matches)) {
            $indexName = $matches[1];
            echo "Creating index: {$indexName}... ";
            
            try {
                $db->exec($statement);
                echo "✅ Created\n";
                $successCount++;
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate key name')) {
                    echo "⚠️  Already exists\n";
                    $skipCount++;
                } else {
                    echo "❌ Error: " . $e->getMessage() . "\n";
                    $errorCount++;
                }
            }
        }
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "✅ Created: {$successCount}\n";
    echo "⚠️  Skipped (already exist): {$skipCount}\n";
    echo "❌ Errors: {$errorCount}\n";
    
    if ($errorCount > 0) {
        echo "\n⚠️  Some indexes failed to create. Check the errors above.\n";
        exit(1);
    }
    
    // Analyze tables to update statistics
    echo "\n=== Analyzing Tables ===\n";
    $tables = [
        'students', 'student_contact', 'admission_details', 'student_scores',
        'class_subjects', 'teacher_subjects', 'guardian_info', 'emergency_contact',
        'users', 'academic_years', 'academic_year_terms', 'subjects', 'classes',
        'assignment_activities', 'class_activity_assignment', 'audit_logs', 'auth_logs'
    ];
    
    foreach ($tables as $table) {
        echo "Analyzing {$table}... ";
        try {
            $db->exec("ANALYZE TABLE {$table}");
            echo "✅\n";
        } catch (PDOException $e) {
            echo "⚠️  " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Performance Testing ===\n";
    echo "Testing query performance...\n\n";
    
    // Test query 1: Student list with joins
    $start = microtime(true);
    $stmt = $db->query("
        SELECT s.student_no, s.first_name, s.last_name, 
               c.phone, c.email, 
               a.admission_status, 
               cl.class_name
        FROM students s
        LEFT JOIN student_contact c ON s.student_no = c.student_no
        LEFT JOIN admission_details a ON s.student_no = a.student_no
        LEFT JOIN classes cl ON a.class_assigned = cl.class_id
        WHERE a.admission_status = 'active'
        LIMIT 10
    ");
    $end = microtime(true);
    $time1 = round(($end - $start) * 1000, 2);
    
    echo "Query 1 (Student list with joins): {$time1}ms\n";
    
    // Test query 2: Student scores
    $start = microtime(true);
    $stmt = $db->query("
        SELECT student_no, subject_id, score
        FROM student_scores
        WHERE academic_year = '2024/2025' AND term = 'Term 1'
        LIMIT 10
    ");
    $end = microtime(true);
    $time2 = round(($end - $start) * 1000, 2);
    
    echo "Query 2 (Student scores by academic year/term): {$time2}ms\n";
    
    // Test query 3: Class subjects
    $start = microtime(true);
    $stmt = $db->query("
        SELECT cs.*, s.subject_name
        FROM class_subjects cs
        LEFT JOIN subjects s ON cs.subject_id = s.subject_id
        WHERE cs.class_id = 1
        LIMIT 10
    ");
    $end = microtime(true);
    $time3 = round(($end - $start) * 1000, 2);
    
    echo "Query 3 (Class subjects): {$time3}ms\n";
    
    echo "\n✅ Migration completed successfully!\n\n";
    
    echo "=== Next Steps ===\n";
    echo "1. Monitor query performance in production\n";
    echo "2. Run EXPLAIN on slow queries to verify index usage\n";
    echo "3. Consider adding more indexes based on query patterns\n";
    echo "4. Regularly run OPTIMIZE TABLE to maintain performance\n\n";
    
    echo "=== Performance Tips ===\n";
    echo "- Indexes speed up SELECT queries but slow down INSERT/UPDATE\n";
    echo "- Monitor index usage with: SHOW INDEX FROM table_name;\n";
    echo "- Check query execution plan with: EXPLAIN SELECT ...\n";
    echo "- Remove unused indexes if they impact write performance\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
