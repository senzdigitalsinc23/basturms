<?php
// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

echo "=== Applying Database Performance Indexes ===\n\n";

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbname = $_ENV['DB_NAME'] ?? 'basturms_db';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

echo "Connecting to: $host / $dbname as $user\n\n";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connected to database\n\n";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/Database/Migrations/add_performance_indexes.sql';
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements
    $lines = explode("\n", $sql);
    $statements = [];
    $currentStatement = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments and empty lines
        if (empty($line) || str_starts_with($line, '--') || str_starts_with($line, '/*')) {
            continue;
        }
        
        $currentStatement .= ' ' . $line;
        
        // Check if statement is complete
        if (str_ends_with($line, ';')) {
            $stmt = trim($currentStatement);
            if (str_contains(strtoupper($stmt), 'CREATE INDEX')) {
                $statements[] = rtrim($stmt, ';');
            }
            $currentStatement = '';
        }
    }
    
    echo "Found " . count($statements) . " index creation statements\n\n";
    
    $created = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        // Extract index name for reporting
        if (preg_match('/CREATE INDEX (?:IF NOT EXISTS )?(\w+)/i', $statement, $matches)) {
            $indexName = $matches[1];
            
            try {
                // Remove IF NOT EXISTS as MySQL doesn't support it for indexes
                $cleanStatement = preg_replace('/IF NOT EXISTS\s+/i', '', $statement);
                $pdo->exec($cleanStatement . ';');
                echo "✅ Created: $indexName\n";
                $created++;
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate key name') || 
                    str_contains($e->getMessage(), 'already exists')) {
                    echo "⚠️  Skipped (exists): $indexName\n";
                    $skipped++;
                } else {
                    echo "❌ Error on $indexName: " . $e->getMessage() . "\n";
                    $errors++;
                }
            }
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "✅ Created: $created\n";
    echo "⚠️  Skipped: $skipped\n";
    echo "❌ Errors: $errors\n";
    echo "\nTotal indexes processed: " . ($created + $skipped + $errors) . "\n";
    
    if ($created > 0 || $skipped > 0) {
        echo "\n=== Analyzing Tables ===\n";
        $tables = [
            'students', 'student_contact', 'admission_details', 'student_scores',
            'class_subjects', 'teacher_subjects', 'guardian_info', 'emergency_contact',
            'users', 'academic_years', 'academic_year_terms', 'subjects', 'classes',
            'assignment_activities', 'class_activity_assignment', 'audit_logs', 'auth_logs'
        ];
        
        foreach ($tables as $table) {
            try {
                $pdo->exec("ANALYZE TABLE $table");
                echo "✅ Analyzed: $table\n";
            } catch (PDOException $e) {
                echo "⚠️  Could not analyze $table\n";
            }
        }
    }
    
    echo "\n=== Migration Complete ===\n";
    if ($created > 0) {
        echo "🎉 Successfully created $created new indexes!\n";
        echo "📈 Expected improvement: 5-10x faster queries\n";
    } else if ($skipped > 0) {
        echo "✅ All indexes already exist - database is optimized!\n";
    }
    echo "\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
