<?php

use App\Controllers\Api\DocumentationController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\v1\CsrfController;
use App\Controllers\Api\v1\ValidationAuthController;
use App\Controllers\Api\v1\ValidationStaffController;
use App\Controllers\Api\v1\ValidationUnitController;
use App\Controllers\Api\v1\ValidationController;
use App\Controllers\Api\v1\ValidationSettingsController;
use App\Controllers\Api\v1\ComprehensiveStaffController;
use App\Controllers\Api\v1\StaffImportController;
use App\Controllers\Api\v1\ChangePasswordController;
use App\Controllers\Api\v1\CorsTestController;
use App\Controllers\Api\v1\DebugController;
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

// Global API middleware (order matters - CORS must be first!)
$router->middleware([
    CorsMiddleware::class,      // CORS MUST be first to handle preflight requests
    CsrfMiddleware::class,    
    WAFMiddleware::class,
    RateLimiter::class,
    SecurityHeaders::class,                     
    ContentTypeEnforcer::class,
    JsonBodyParser::class,
]);

// Health check endpoints (no authentication required)
$router->getApi('v1', '/health', [HealthController::class, 'check'], []);
$router->getApi('v1', '/ping', [HealthController::class, 'ping'], []);
$router->getApi('v1', '/cors-test', [CorsTestController::class, 'test'], []);
$router->getApi('v1', '/debug/headers', [DebugController::class, 'headers'], []);

// v1 Auth
$router->getApi('v1', '/mdware/auth/csrf', [CsrfController::class, 'token'], [RateLimiter::class]);

// Swagger/OpenAPI documentation routes
$router->getApi('v1', '/swagger', [DocumentationController::class, 'index']);
$router->getApi('v1', '/docs', [DocumentationController::class, 'docs']);

// ============================================
// VALIDATION SYSTEM API ROUTES
// ============================================

// Validation Auth (no API key required for login)
$router->postApi('v1', '/validation/auth/login', [ValidationAuthController::class, 'login'], [RateLimiter::class, BruteForceLockoutMiddleware::class]);
$router->getApi('v1', '/validation/auth/me', [ValidationAuthController::class, 'me'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/validation/auth/change-password', [ChangePasswordController::class, 'changePassword'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// Validation Units
$router->getApi('v1', '/validation/units', [ValidationUnitController::class, 'getAllUnits'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/validation/units', [ValidationUnitController::class, 'createUnit'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// Validation Staff
$router->getApi('v1', '/validation/staff', [ValidationStaffController::class, 'getAllStaff'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->postApi('v1', '/validation/staff', [ValidationStaffController::class, 'createStaff'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/validation/departments', [ValidationStaffController::class, 'getDepartments'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// Validation Settings
$router->postApi('v1', '/validation/settings', [ValidationSettingsController::class, 'setValidationPeriod'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/validation/settings', [ValidationSettingsController::class, 'getValidationPeriod'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/validation/settings/check', [ValidationSettingsController::class, 'checkValidationAllowed'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// Validations
$router->postApi('v1', '/validations', [ValidationController::class, 'validate'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->deleteApi('v1', '/validations', [ValidationController::class, 'cancelValidation'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/validations', [ValidationController::class, 'getValidations'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/validations/unit-statistics', [ValidationController::class, 'getUnitStatistics'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/validations/export', [ValidationController::class, 'exportValidations'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// Comprehensive Staff Management
$router->postApi('v1', '/staff/create', [ComprehensiveStaffController::class, 'createComprehensive'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/staff/comprehensive/{id}', [ComprehensiveStaffController::class, 'getComprehensive'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);

// Staff Import
$router->postApi('v1', '/staff/import', [StaffImportController::class, 'importStaff'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
$router->getApi('v1', '/staff/import/template', [StaffImportController::class, 'downloadTemplate'], [APIKeyMiddleware::class, AuthMiddleware::class, RateLimiter::class]);
