<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use Jobs\ComputeRankingsJob;

$db = Database::getInstance()->getConnection();

// Find latest academic year and term from student_report
$latest = $db->query("
    SELECT academic_year, term 
    FROM student_report 
    ORDER BY id DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$latest) {
    echo "No student report data found to rank.\n";
    exit(1);
}

$academicYear = $latest['academic_year'];
$term = $latest['term'];

echo "Manually triggering rankings for {$academicYear} - {$term}...\n";

// Load env vars for safety if needed, though we already confirmed inline env works
// For this manual script, it should just work if run with the same env prefix

$job = new ComputeRankingsJob();
$job->handle($academicYear, $term);

echo "Done.\n";
