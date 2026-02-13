<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Repositories\StudentScoreRepository;

echo "Verifying StudentScoreService::getSummaryReports...\n";

try {
    // Use repository directly to test query logic first
    $repo = new StudentScoreRepository();
    $result = $repo->getSummaryReports(null, '2025/2026', 'Term 1');

    if (empty($result)) {
        echo "No summary records found for 2025/2026 - Term 1. Testing without filters...\n";
        $result = $repo->getSummaryReports();
    }
} catch (\Throwable $e) {
    echo "BASE64_ERROR_START\n";
    echo base64_encode("Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "\nBASE64_ERROR_END\n";
}

$count = count($result);
echo "Found $count records.\n";

if ($count > 0) {
    echo "Sample Record:\n";
    print_r($result[0]);
    echo "\nVerification Successful.\n";
} else {
    echo "Warning: No records found. Ensure summaries have been generated.\n";
}
