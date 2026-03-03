<?php

use App\Controllers\Api\DocumentationController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\v1\AdminController;
use App\Controllers\Api\v1\AuthController;
use App\Controllers\Api\v1\StudentController;
use App\Controllers\Api\v1\StaffController;
use App\Controllers\Api\v1\PromotionController;
use App\Controllers\Api\v1\AcademicController;
use App\Controllers\Api\v1\RankingController;
use App\Controllers\Api\v1\CsrfController;
use App\Controllers\Api\v1\PromotionCriteriaController;
use App\Controllers\Api\v1\UploadController;
use App\Controllers\TestController;
use App\Middleware\APIKeyMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\BruteForceLockoutMiddleware;
use App\Middleware\SecurityHeaders;
use App\Middleware\CorsMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\ContentTypeEnforcer;
use App\Middleware\JsonBodyParser;
use App\Middleware\RateLimiter;
use App\Middleware\WAFMiddleware;

// Global API middleware (order matters)
$router->middleware([
    CsrfMiddleware::class,    
    WAFMiddleware::class,
    RateLimiter::class,
    CorsMiddleware::class, // enable only for API frontends you control
    SecurityHeaders::class,                     
    ContentTypeEnforcer::class,
    JsonBodyParser::class,
]);

// Health check endpoints (no authentication required)
$router->getApi('v1', '/health', [HealthController::class, 'check'], []);
$router->getApi('v1', '/ping', [HealthController::class, 'ping'], []);

// v1 Auth
$router->getApi('v1', '/mdware/auth/csrf', [CsrfController::class, 'token'], [RateLimiter::class]);
$router->postApi('v1', '/register', [AuthController::class, 'register'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class, BruteForceLockoutMiddleware::class]);
$router->postApi('v1', '/login', [AuthController::class, 'login'], [APIKeyMiddleware::class, RateLimiter::class, BruteForceLockoutMiddleware::class]);
$router->getApi('v1', '/me', [AuthController::class, 'me'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/logout', [AuthController::class, 'logout'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/profile', [AuthController::class, 'profile'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/profile/details', [AuthController::class, 'getProfileDetails'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->putApi('v1', '/profile/update', [AuthController::class, 'updateProfile'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->putApi('v1', '/profile/image', [AuthController::class, 'updateProfileImage'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->deleteApi('v1', '/profile/image', [AuthController::class, 'removeProfileImage'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/auth/reset-password', [AuthController::class, 'resetPassword'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class, BruteForceLockoutMiddleware::class]);
$router->postApi('v1', '/auth/forgot-password', [AuthController::class, 'forgotPassword'], [APIKeyMiddleware::class, RateLimiter::class]);


// v1 Admin
$router->getApi('v1', '/admin/users', [AdminController::class, 'users'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/admin/users/unlock', [AdminController::class, 'unlockUserAccount'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// v1 Students
$router->getApi('v1', '/students', [StudentController::class, 'index'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/students/show', [StudentController::class, 'show'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/students/create', [StudentController::class, 'create'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/students/update', [StudentController::class, 'update'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/students/download', [StudentController::class, 'exportCSV'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/students/upload', [StudentController::class, 'importCSV'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/students/preview', [StudentController::class, 'previewCSV'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/students/download-template', [StudentController::class, 'downloadCsvTemplate'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/students/state', [StudentController::class, 'freeze'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/students/delete', [StudentController::class, 'delete'], [APIKeyMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/students/class', [StudentController::class, 'classStudents'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// v1 Staff
$router->postApi('v1', '/staff/register', [StaffController::class, 'register'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/staff', [StaffController::class, 'getAllStaff'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/staff/filter', [StaffController::class, 'getStaffByFilter'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/staff/details', [StaffController::class, 'getStaff'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->putApi('v1', '/staff/{id}', [StaffController::class, 'updateStaff'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/staff/share-credentials', [StaffController::class, 'shareCredentials'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// Staff Assignments
$router->postApi('v1', '/staff/assign-classes', [StaffController::class, 'assignClasses'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/staff/assign-subjects', [StaffController::class, 'assignSubjects'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/staff/assignments', [StaffController::class, 'getStaffAssignments'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->deleteApi('v1', '/staff/remove-class', [StaffController::class, 'removeClassAssignment'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->deleteApi('v1', '/staff/remove-subject', [StaffController::class, 'removeSubjectAssignment'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);


// v1 Promotions
$router->postApi('v1', '/promotions/normal', [PromotionController::class, 'promoteNormal'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/promotions/special', [PromotionController::class, 'promoteSpecial'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/promotions/graduate', [PromotionController::class, 'graduate'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/promotions/history', [PromotionController::class, 'history'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/promotions/classes', [PromotionController::class, 'availableClasses'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// v1 Promotion Criteria
$router->postApi('v1', '/promotion-criteria/create', [PromotionCriteriaController::class, 'create'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/promotion-criteria/list', [PromotionCriteriaController::class, 'list'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/promotion-criteria/update', [PromotionCriteriaController::class, 'update'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/promotion-criteria/delete', [PromotionCriteriaController::class, 'delete'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// v1 Academic Management
$router->postApi('v1', '/academic/years/create', [AcademicController::class, 'createAcademicYear'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/academic/years/list', [AcademicController::class, 'listAcademicYears'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/academic/years/active', [AcademicController::class, 'getActiveAcademicYear'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/years/status', [AcademicController::class, 'updateAcademicYearStatus'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/years/delete', [AcademicController::class, 'deleteAcademicYear'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/terms/delete', [AcademicController::class, 'deleteAcademicTerm'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/terms/add', [AcademicController::class, 'addAcademicTerm'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

$router->postApi('v1', '/academic/subjects/create', [AcademicController::class, 'createSubject'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/academic/subjects/list', [AcademicController::class, 'listSubjects'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/subjects/list', [AcademicController::class, 'listSubjects'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/subjects/update', [AcademicController::class, 'updateSubject'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/subjects/delete', [AcademicController::class, 'deleteSubject'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

$router->postApi('v1', '/academic/classes/create', [AcademicController::class, 'createClass'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/academic/classes/list', [AcademicController::class, 'listClasses'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/classes/list', [AcademicController::class, 'listClasses'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/classes/update', [AcademicController::class, 'updateClass'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/classes/delete', [AcademicController::class, 'deleteClass'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

$router->postApi('v1', '/academic/class-subjects/assign', [AcademicController::class, 'assignSubjectToClass'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/class-subjects/list', [AcademicController::class, 'getClassSubjects'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/academic/class-subjects/list', [AcademicController::class, 'getClassSubjects'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/class-subjects/bulk-unassign', [AcademicController::class, 'bulkRemoveSubjectsFromClass'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

$router->postApi('v1', '/academic/teacher-subjects/assign', [AcademicController::class, 'assignSubjectToTeacher'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/teacher-subjects/list', [AcademicController::class, 'getTeacherSubjects'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

$router->postApi('v1', '/academic/scores/add', [AcademicController::class, 'addStudentScore'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/scores/submit', [AcademicController::class, 'submitScores'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/scores/student', [AcademicController::class, 'getStudentScores'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/scores/class', [AcademicController::class, 'getClassScores'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/scores/bulk', [AcademicController::class, 'bulkAddScores'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/scores/upload', [AcademicController::class, 'uploadScoresCSV'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/scores/summary/list', [AcademicController::class, 'getSummaryReports'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/scores/report/list', [AcademicController::class, 'getStudentReports'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// v1 Rankings
$router->postApi('v1', '/academic/rankings/subjects', [RankingController::class, 'subjectRankings'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/rankings/class',    [RankingController::class, 'classRankings'],   [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/rankings/level',    [RankingController::class, 'levelRankings'],   [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/rankings/school',   [RankingController::class, 'schoolRankings'],  [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/rankings/student',  [RankingController::class, 'studentRanking'],  [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

$router->postApi('v1', '/academic/activities/create', [AcademicController::class, 'createAssignmentActivity'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/activities/update', [AcademicController::class, 'updateAssignmentActivity'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/activities/delete', [AcademicController::class, 'deleteAssignmentActivity'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/activities/permanent-delete', [AcademicController::class, 'permanentDeleteAssignmentActivity'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/activities/activate', [AcademicController::class, 'activateAssignmentActivity'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/academic/activities/list', [AcademicController::class, 'listAssignmentActivities'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/activities/list', [AcademicController::class, 'listAssignmentActivities'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/academic/activities/list/inactive', [AcademicController::class, 'listInactiveAssignmentActivities'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// v1 Uploads
$router->postApi('v1', '/uploads', [UploadController::class, 'upload'], [APIKeyMiddleware::class, AuthMiddleware::class]);
$router->getApi('v1', '/uploads/file/{id}', [UploadController::class, 'getFile'], [APIKeyMiddleware::class, AuthMiddleware::class]);
// Public endpoint for serving files (no auth required for images in browser)
$router->getApi('v1', '/uploads/public/{id}', [UploadController::class, 'getPublicFile'], [RateLimiter::class]);
// Secure endpoint for session-based auth (works in browser with cookies)
$router->get('/api/v1/uploads/secure/{id}', [UploadController::class, 'getSecureFile']);


$router->postApi('v1', '/academic/classes/activities/assign', [AcademicController::class, 'assignActivityToClass'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/classes/activities/unassign', [AcademicController::class, 'unassignActivityFromClass'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/academic/classes/activities/list', [AcademicController::class, 'listClassActivityAssignments'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/classes/activities/list', [AcademicController::class, 'listClassActivityAssignments'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/classes/activities/list/individual', [AcademicController::class, 'listClassIndividualActivityAssignments'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
    

$router->postApi('v1', '/academic/grading-scheme/create', [AcademicController::class, 'createGradingScheme'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/academic/grading-scheme/list', [AcademicController::class, 'listGradingSchemes'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/grading-scheme/update', [AcademicController::class, 'updateGradingScheme'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/academic/grading-scheme/delete', [AcademicController::class, 'deleteGradingScheme'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// v1 Calendar Events
$router->postApi('v1', '/calendar/events/add', [\App\Controllers\Api\v1\CalendarEventController::class, 'create'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class, CsrfMiddleware::class]);
$router->postApi('v1', '/calendar/events/update', [\App\Controllers\Api\v1\CalendarEventController::class, 'update'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class, CsrfMiddleware::class]);
$router->postApi('v1', '/calendar/events/delete', [\App\Controllers\Api\v1\CalendarEventController::class, 'delete'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class, CsrfMiddleware::class]);
$router->getApi('v1', '/calendar/events/list', [\App\Controllers\Api\v1\CalendarEventController::class, 'list'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/calendar/events/categories', [\App\Controllers\Api\v1\CalendarEventController::class, 'listCategories'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

$router->postApi('v1', '/test/mail', [TestController::class, 'mail'], []);


// Swagger/OpenAPI documentation routes
$router->getApi('v1', '/swagger', [DocumentationController::class, 'index']);
$router->getApi('v1', '/docs', [DocumentationController::class, 'docs']);
