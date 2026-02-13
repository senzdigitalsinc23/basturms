<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\SubjectService;
use App\Repositories\SubjectRepository;
use App\Core\Database;

// Mock Session and user roles for testing
// In a real application, you would have a proper testing framework
class MockSession {
    private static array $data = [];

    public static function set(string $key, $value): void {
        self::$data[$key] = $value;
    }

    public static function get(string $key, $default = null) {
        return self::$data[$key] ?? $default;
    }
}

// Replace Session with MockSession for testing
if (!class_exists('App\Core\Session')) {
    class_alias(MockSession::class, 'App\Core\Session');
}

// Setup database and services
$db = Database::getInstance()->getConnection();
$subjectRepo = new SubjectRepository();
$subjectService = new SubjectService();

echo "\n--- Bulk Subject Deletion Testing ---\n";

$testSubjects = [];

function createTestSubject(SubjectService $service, string $name, string $code, string $level, string $category, string $user): array
{
    global $testSubjects, $subjectRepo;
    try {
        $result = $service->createSubject($name, $code, $level, $category, "Test description for {$name}", $user);
        if ($result['success']) {
            echo "  ✓ Created: {$name} ({$code})\n";
            $testSubjects[] = $result['data'];
            return $result['data'];
        }
    } catch (Exception $e) {
        // If creation failed, it might be because the subject already exists (e.g., from a previous failed run)
        // Try to retrieve the existing subject instead
        echo "  ℹ️  Failed to create: {$name} ({$code}) - " . $e->getMessage() . ". Attempting to retrieve existing...\n";
        $existing = $subjectRepo->getByCode($code, true); // Get regardless of status
        if ($existing) {
            echo "  ✓ Retrieved existing: {$existing['subject_name']} ({$existing['subject_code']})\n";
            $testSubjects[] = $existing;
            return $existing;
        } else {
            echo "  ✗ Could not create or find existing subject: {$name} ({$code})\n";
        }
    }
    return []; // Return empty array if unable to create or retrieve
}

function printSubjects(SubjectRepository $repo, string $title = "Current Subjects")
{
    echo "\n{$title}:\n";
    $allSubjects = $repo->getAll(null); // Get all subjects regardless of status
    foreach ($allSubjects as $s) {
        echo "  - ID: {$s['id']}, Code: {$s['subject_code']}, Name: {$s['subject_name']}, Status: {$s['status']}\n";
    }
}

// Clean up previous test data before running
echo "\nCleaning up any lingering test data...";
$db->exec("DELETE FROM subjects WHERE subject_code LIKE 'BULK_TEST_%'");
echo "\n";

// Create subjects for testing
echo "Creating test subjects...\n";
$s1 = createTestSubject($subjectService, 'Bulk Test Subject 1', 'BT_1', 'Primary', 'Core', 'test_user');
$s2 = createTestSubject($subjectService, 'Bulk Test Subject 2', 'BT_2', 'Primary', 'Core', 'test_user');
$s3 = createTestSubject($subjectService, 'Bulk Test Subject 3', 'BT_3', 'Primary', 'Core', 'test_user');
$s4 = createTestSubject($subjectService, 'Bulk Test Subject 4', 'BT_4', 'JHS', 'Elective', 'test_user');

printSubjects($subjectRepo, "Subjects after creation");

// Test 1: Bulk delete by IDs
echo "\nTest 1: Bulk delete by IDs (Subject 1, Subject 2)...";
$idsToDelete = [(int)$s1['id'], (int)$s2['id']];
try {
    $result = $subjectService->deleteSubject($idsToDelete);
    if ($result['success']) {
        echo "\n✓ Success: All subjects deleted: " . $result['message'] . "\n";
    } else {
        echo "\n⚠ Partial Success/Failure: " . $result['message'] . "\n";
    }
    foreach ($result['results'] as $res) {
        echo "  - Subject: {$res['subject']}, Success: " . ($res['success'] ? 'Yes' : 'No') . ", Message: {$res['message']}\n";
    }

    // Verify in DB
    $s1_status = $subjectRepo->getById((int)$s1['id'], true)['status'];
    $s2_status = $subjectRepo->getById((int)$s2['id'], true)['status'];
    if ($s1_status === 'dormant' && $s2_status === 'dormant') {
        echo "✓ Verification: Subjects 1 and 2 are dormant.\n";
    } else {
        echo "✗ Verification: Subjects 1 and 2 are NOT dormant. (S1: {$s1_status}, S2: {$s2_status})\n";
    }
} catch (Exception $e) {
    echo "\n✗ Error during bulk deletion by IDs: " . $e->getMessage() . "\n";
}
printSubjects($subjectRepo, "Subjects after Test 1");

// Test 2: Bulk delete by codes (Subject 3, non-existent, already dormant Subject 1)
echo "\nTest 2: Bulk delete by codes (Subject 3, non-existent, already dormant Subject 1)...";
$codesToDelete = [$s3['subject_code'], 'NON_EXISTENT_CODE', $s1['subject_code']];
try {
    $result = $subjectService->deleteSubject($codesToDelete);
    if (!$result['success']) { // Expecting partial failure
        echo "\n✓ Success: Expected partial failure/success occurred: " . $result['message'] . "\n";
    } else {
        echo "\n✗ Failed: Expected partial failure but all succeeded: " . $result['message'] . "\n";
    }
    foreach ($result['results'] as $res) {
        echo "  - Subject: {$res['subject']}, Success: " . ($res['success'] ? 'Yes' : 'No') . ", Message: {$res['message']}\n";
    }

    // Verify in DB
    $s3_status = $subjectRepo->getById((int)$s3['id'], true)['status'];
    $s1_status = $subjectRepo->getById((int)$s1['id'], true)['status']; // Should remain dormant
    if ($s3_status === 'dormant' && $s1_status === 'dormant') {
        echo "✓ Verification: Subject 3 is dormant and Subject 1 is still dormant.\n";
    } else {
        echo "✗ Verification: Subjects statuses are incorrect. (S3: {$s3_status}, S1: {$s1_status})\n";
    }
} catch (Exception $e) {
    echo "\n✗ Error during bulk deletion by codes: " . $e->getMessage() . "\n";
}
printSubjects($subjectRepo, "Subjects after Test 2");

// Test 3: Attempt to delete an empty array
echo "\nTest 3: Attempt to delete an empty array...";
try {
    $result = $subjectService->deleteSubject([]);
    echo "\n✗ Failed: Expected exception for empty array, but succeeded.\n";
} catch (Exception $e) {
    echo "\n✓ Success: Caught expected exception: " . $e->getMessage() . "\n";
}

// Final check: list all subjects including dormant to see overall state
MockSession::set('user', ['role' => 'admin']); // Act as admin to see all
$finalList = $subjectService->listSubjects(true);
echo "\nFinal List of Subjects (as Admin): " . count($finalList['data']) . " entries.\n";

// Clean up test data from the database
echo "\nFinal Cleanup of all test subjects...\n";
foreach ($testSubjects as $subject) {
    if (isset($subject['subject_code'])) {
        $db->prepare("DELETE FROM subjects WHERE subject_code = ?")->execute([$subject['subject_code']]);
    }
}
echo "✓ All test subjects removed.\n";

echo "\n--- Bulk Subject Deletion Testing Completed ---\n";
