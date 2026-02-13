<?php
// Disable error reporting for session warnings in CLI
ini_set('session.use_cookies', 0);
ini_set('session.use_remote_headers', 0);
@session_start();

require_once 'vendor/autoload.php';

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AcademicSetupService;
use App\Services\SubjectService;
use App\Services\ClassService;
use App\Services\ClassSubjectService;
use App\Services\TeacherSubjectService;
use App\Services\StudentScoreService;
use App\Services\AssignmentActivityService;
use App\Services\LoggingService;
use App\Services\ValidationService;
use App\Repositories\AcademicSetupRepository;
use App\Repositories\AcademicYearRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\ClassRepository;
use App\Repositories\ClassSubjectRepository;
use App\Repositories\TeacherSubjectRepository;
use App\Repositories\StudentScoreRepository;
use App\Controllers\Api\v1\AcademicController;

try {
    // Mock session
    $_SESSION['user'] = ['id' => '1', 'user_id' => '1', 'role' => 'admin'];

    echo "Starting verification...\n";

    // Initialize dependencies
    $validationService = new ValidationService();
    $loggingService = new LoggingService();
    
    $setupRepo = new AcademicSetupRepository();
    $yearRepo = new AcademicYearRepository();
    $subjectRepo = new SubjectRepository();
    $classRepo = new ClassRepository();
    $classSubjectRepo = new ClassSubjectRepository();
    $teacherSubjectRepo = new TeacherSubjectRepository();
    $studentScoreRepo = new StudentScoreRepository();

    $academicSetupService = new AcademicSetupService($setupRepo, $yearRepo, $validationService);
    $subjectService = new SubjectService($subjectRepo, $validationService);
    $classService = new ClassService($classRepo, $validationService);
    $classSubjectService = new ClassSubjectService($classSubjectRepo, $validationService);
    $teacherSubjectService = new TeacherSubjectService($teacherSubjectRepo, $validationService);
    $studentScoreService = new StudentScoreService($studentScoreRepo, $validationService);
    $assignmentActivityService = new AssignmentActivityService($validationService);

    $controller = new AcademicController(
        $academicSetupService,
        $subjectService,
        $classService,
        $classSubjectService,
        $teacherSubjectService,
        $studentScoreService,
        $assignmentActivityService,
        $loggingService
    );

    $response = new Response();

    echo "--- Test 1: Create Assignment Activity ---\n";
    $activityId = 'TEST_ACT_' . time();
    $postData = [
        'activity_id' => $activityId,
        'act_name' => 'Verification Test Activity',
        'expected_per_term' => 5,
        'weight' => 20,
        'academic_year' => '2024/2025',
        'term' => 'Term 1'
    ];
    // Request constructor: public function __construct(array $query = [], array $post = [], array $cookies = [], array $files = [], array $server = [])
    $request = new Request([], $postData);
    
    $resultResponse = $controller->createAssignmentActivity($request, $response);
    echo "Status Code: " . $resultResponse->getStatusCode() . "\n";
    echo "Content: " . $resultResponse->getContent() . "\n\n";

    echo "--- Test 2: List Assignment Activities ---\n";
    // List uses getPost('academic_year') or getQuery('academic_year')
    $requestList = new Request(['academic_year' => '2024/2025', 'term' => 'Term 1'], []);
    $responseList = new Response();
    $resultResponseList = $controller->listAssignmentActivities($requestList, $responseList);
    echo "Status Code: " . $resultResponseList->getStatusCode() . "\n";
    echo "Content: " . $resultResponseList->getContent() . "\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
