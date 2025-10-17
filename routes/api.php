<?php

use App\Controllers\Api\DocumentationController;
use App\Controllers\Api\v1\AdminController;
use App\Controllers\Api\v1\AuthController;
use App\Controllers\Api\v1\StudentController;
use App\Middleware\APIKeyMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\SecurityHeaders;
use App\Middleware\CorsMiddleware;
use App\Middleware\ContentTypeEnforcer;
use App\Middleware\JsonBodyParser;

// Global API middleware (order matters)
$router->middleware([
    SecurityHeaders::class,
    CorsMiddleware::class,                  // enable only for API frontends you control
    ContentTypeEnforcer::class,
    JsonBodyParser::class,
]);

// v1 Auth
$router->postApi('v1', '/register', [AuthController::class, 'register'], [APIKeyMiddleware::class, AuthMiddleware::class]);
$router->postApi('v1', '/login', [AuthController::class, 'login'], [APIKeyMiddleware::class]);
$router->getApi('v1', '/me', [AuthController::class, 'me'], [APIKeyMiddleware::class, AuthMiddleware::class]);
$router->getApi('v1', '/logout', [AuthController::class, 'logout'], [APIKeyMiddleware::class, AuthMiddleware::class]);
$router->getApi('v1', '/profile', [AuthController::class, 'profile'], [APIKeyMiddleware::class, AuthMiddleware::class]);

// v1 Admin
$router->getApi('v1', '/admin/users', [AdminController::class, 'users'], [APIKeyMiddleware::class, AuthMiddleware::class]);

// v1 Students
$router->getApi('v1', '/students', [StudentController::class, 'index'], [AuthMiddleware::class, APIKeyMiddleware::class]);
$router->getApi('v1', '/students/show', [StudentController::class, 'show'], [APIKeyMiddleware::class]);
$router->getApi('v1', '/students/download', [StudentController::class, 'exportCSV'], [APIKeyMiddleware::class, AuthMiddleware::class]);
$router->postApi('v1', '/students/upload', [StudentController::class, 'importCSV'], [APIKeyMiddleware::class, AuthMiddleware::class]);
$router->postApi('v1', '/students/preview', [StudentController::class, 'previewCSV'], [APIKeyMiddleware::class, AuthMiddleware::class]);
$router->getApi('v1', '/students/download-template', [StudentController::class, 'downloadCsvTemplate'], [APIKeyMiddleware::class, AuthMiddleware::class]);
$router->postApi('v1', '/students/create', [StudentController::class, 'create'], [APIKeyMiddleware::class, AuthMiddleware::class]);
$router->postApi('v1', '/students/delete', [StudentController::class, 'freeze'], [APIKeyMiddleware::class, AuthMiddleware::class]);

/* 
$router->getApi('v1', '/students', [Api\V1\StudentController::class, 'index'], [
    ApiKeyMiddleware::class,
    KeyRateLimiterMiddleware::class
]); */
//Documentation endpoints
/* $router->get('/api/swagger', [DocumentationController::class, 'index']);
$router->get('/api/docs', function () {
    include __DIR__ . '/../views/layouts/docs-ui.php'; // The HTML with SwaggerUIBundle
}); */