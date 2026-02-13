<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AcademicSetupService;
use App\Repositories\AcademicSetupRepository;
use App\Services\ValidationService;

// Test the Academic Setup
$repo = new AcademicSetupRepository();
$validationService = new ValidationService();
$service = new AcademicSetupService($repo, $validationService);

// Test Data
$academicYear = '2023-2024';
$term = 'First Term';
$startDate = '2023-09-01';
$endDate = '2023-12-15';
$status = 'Upcoming';
$addedBy = 'test_user';
$numberOfTerms = 3;

// Test Set Number of Terms
$result = $service->setNumberOfTerms($academicYear, $numberOfTerms);
if ($result['success']) {
    echo "Set Number of Terms: SUCCESS\n";
} else {
    echo "Set Number of Terms: FAIL - {$result['message']}\n";
}

// Test Create Academic Year
$result = $service->createAcademicYear($academicYear, $term, $startDate, $endDate, $status, $addedBy);
if ($result['success']) {
    echo "Create Academic Year: SUCCESS\n";
    $id = $result['data']['id'];
} else {
    echo "Create Academic Year: FAIL - {$result['message']}\n";
    exit;
}

// Test Update Academic Year
$newStartDate = '2023-09-02';
$newEndDate = '2023-12-16';
$newStatus = 'Active';
$result = $service->updateAcademicYear($id, $newStartDate, $newEndDate, $newStatus, $addedBy);
if ($result['success']) {
    echo "Update Academic Year: SUCCESS\n";
} else {
    echo "Update Academic Year: FAIL - {$result['message']}\n";
}

// Test Update Status
$newStatus = 'Completed';
$result = $service->updateStatus($id, $newStatus, $addedBy);
if ($result['success']) {
    echo "Update Status: SUCCESS\n";
} else {
    echo "Update Status: FAIL - {$result['message']}\n";
}

// Test Delete Academic Year
$result = $service->delete($id);
if ($result['success']) {
    echo "Delete Academic Year: SUCCESS\n";
} else {
    echo "Delete Academic Year: FAIL - {$result['message']}\n";
}
