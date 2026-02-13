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

echo "\n--- Subject Feature Testing ---\n";

// --- Test 1: Create a new subject ---
echo "\nTest 1: Create a new subject (English - Primary)...";
try {
    $result = $subjectService->createSubject('English', 'ENG_PR_T', 'Primary', 'Core', 'Test English for Primary', 'test_user');
    if ($result['success']) {
        echo "\n✓ Success: " . $result['message'];
        $newSubjectId = (int)$result['data']['id'];
        $newSubjectCode = $result['data']['subject_code'];

        // Direct database verification after creation
        $stmt = $db->prepare("SELECT status FROM subjects WHERE id = ?");
        $stmt->execute([$newSubjectId]);
        $dbStatusAfterCreation = $stmt->fetchColumn();
        echo "\nDebug: Subject ID {$newSubjectId} status after creation in DB: {$dbStatusAfterCreation}";

    } else {
        echo "\n✗ Failed: " . $result['message'];
    }
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage();
}

// --- Test 2: List active subjects (default behavior) ---
echo "\n\nTest 2: List active subjects (non-admin user)...";
MockSession::set('user', ['role' => 'student']);
try {
    $result = $subjectService->listSubjects(false);
    $foundNewSubjectInActiveList = false;
    foreach ($result['data'] as $subject) {
        error_log("  Debug (Test 2 Loop): Checking subject ID {$subject['id']} (type: " . gettype($subject['id']) . ") against newSubjectId {$newSubjectId} (type: " . gettype($newSubjectId) . ")");
        if ((int)$subject['id'] === (int)$newSubjectId && $subject['status'] === 'active') {
            $foundNewSubjectInActiveList = true;
            break;
        }
    }
    if ($foundNewSubjectInActiveList) {
        echo "\n✓ Success: New subject (ID: {$newSubjectId}) found and is active in active list. Total active: " . count($result['data']);
    } else {
        echo "\n✗ Failed: New subject (ID: {$newSubjectId}) not found or not active in active list. Total active: " . count($result['data']);
    }
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage();
}

// --- Test 3: Soft delete (set to dormant) a subject ---
echo "\n\nTest 3: Soft delete a subject (set to dormant)...";
try {
    $result = $subjectService->deleteSubject($newSubjectId);
    if ($result['success']) {
        echo "\n✓ Success: " . $result['message'];
    } else {
        echo "\n✗ Failed: " . $result['message'];
    }

    // Verify status in DB after soft delete
    $subjectInDb = $subjectRepo->getById($newSubjectId, true); // Include dormant for direct check
    if ($subjectInDb && $subjectInDb['status'] === 'dormant') {
        echo "\n✓ Verification: Subject ID {$newSubjectId} is indeed dormant in DB.";
    } else {
        echo "\n✗ Verification: Subject ID {$newSubjectId} is NOT dormant in DB (status: " . ($subjectInDb['status'] ?? 'N/A') . ").";
    }
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage();
}

// --- Test 4: List active subjects (should not show dormant) ---
echo "\n\nTest 4: List active subjects (should not show dormant subject)...";
MockSession::set('user', ['role' => 'student']);
try {
    $result = $subjectService->listSubjects(false);
    $foundDormantSubject = false;
    foreach ($result['data'] as $subject) {
        error_log("  Debug (Test 4 Loop): Checking subject ID {$subject['id']} (type: " . gettype($subject['id']) . ") against newSubjectId {$newSubjectId} (type: " . gettype($newSubjectId) . ")");
        if ((int)$subject['id'] === (int)$newSubjectId) {
            $foundDormantSubject = true;
            break;
        }
    }
    if (!$foundDormantSubject) {
        echo "\n✓ Success: Dormant subject (ID: {$newSubjectId}) is correctly hidden from active list. Total active: " . count($result['data']);
    } else {
        echo "\n✗ Failed: Dormant subject (ID: {$newSubjectId}) is still visible in active list. Total active: " . count($result['data']);
    }
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage();
}

// Direct database query for all subjects after soft delete
echo "\n\nDebug: Current state of all subjects in DB after soft delete:";
$allSubjectsInDb = $db->query("SELECT id, subject_name, status FROM subjects ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($allSubjectsInDb as $s) {
    error_log("  Debug (DB State Loop): ID: {$s['id']}, Name: {$s['subject_name']}, Status: {$s['status']}");
}

// --- Test 5: List all subjects as admin (should show dormant) ---
echo "\n\nTest 5: List all subjects as admin (should show dormant subject)...";
MockSession::set('user', ['role' => 'admin']); // Set admin role
try {
    $result = $subjectService->listSubjects(true); // Request dormant subjects
    $foundDormantSubjectInAllList = false;
    foreach ($result['data'] as $subject) {
        error_log("  Debug (Test 5 Loop): Checking subject ID {$subject['id']} (type: " . gettype($subject['id']) . ") against newSubjectId {$newSubjectId} (type: " . gettype($newSubjectId) . ")");
        if ((int)$subject['id'] === (int)$newSubjectId && $subject['status'] === 'dormant') {
            $foundDormantSubjectInAllList = true;
            break;
        }
    }
    if ($foundDormantSubjectInAllList) {
        echo "\n✓ Success: Dormant subject (ID: {$newSubjectId}) is correctly visible to admin. Total all: " . count($result['data']);
    } else {
        echo "\n✗ Failed: Dormant subject (ID: {$newSubjectId}) is NOT visible to admin. Total all: " . count($result['data']);
    }
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage();
}

// --- Test 6: List all subjects as super-admin (should show dormant) ---
echo "\n\nTest 6: List all subjects as super-admin (should show dormant subject)...";
MockSession::set('user', ['role' => 'super-admin']); // Set super-admin role
try {
    $result = $subjectService->listSubjects(true); // Request dormant subjects
    $foundDormantSubjectInAllListSA = false;
    foreach ($result['data'] as $subject) {
        error_log("  Debug (Test 6 Loop): Checking subject ID {$subject['id']} (type: " . gettype($subject['id']) . ") against newSubjectId {$newSubjectId} (type: " . gettype($newSubjectId) . ")");
        if ((int)$subject['id'] === (int)$newSubjectId && $subject['status'] === 'dormant') {
            $foundDormantSubjectInAllListSA = true;
            break;
        }
    }
    if ($foundDormantSubjectInAllListSA) {
        echo "\n✓ Success: Dormant subject (ID: {$newSubjectId}) is correctly visible to super-admin. Total all: " . count($result['data']);
    } else {
        echo "\n✗ Failed: Dormant subject (ID: {$newSubjectId}) is NOT visible to super-admin. Total all: " . count($result['data']);
    }
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage();
}

// --- Test 7: Try to delete a non-existent subject ---
echo "\n\nTest 7: Try to delete a non-existent subject...";
try {
    $result = $subjectService->deleteSubject(99999);
    echo "\n✗ Should have failed but succeeded.";
} catch (Exception $e) {
    echo "\n✓ Correctly failed: " . $e->getMessage();
}

// --- Test 8: Create a subject with duplicate code ---
echo "\n\nTest 8: Create a subject with duplicate code (should fail for active)...";
try {
    $result = $subjectService->createSubject('Duplicate English', $newSubjectCode, 'Primary', 'Core', 'Duplicate check', 'test_user');
    echo "\n✗ Should have failed but succeeded.";
} catch (Exception $e) {
    echo "\n✓ Correctly failed: " . $e->getMessage();
}

// Clean up: delete the test subject from the database
echo "\n\nCleaning up test data...";
try {
    $db->prepare("DELETE FROM subjects WHERE subject_code = ?")->execute([$newSubjectCode]);
    echo "\n✓ Test subject {$newSubjectCode} deleted from database.";
} catch (Exception $e) {
    echo "\n✗ Error during cleanup: " . $e->getMessage();
}

echo "\n\n--- Subject Feature Testing Completed ---\n";
