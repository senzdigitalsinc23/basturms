<?php

/**
 * Document Management Routes
 * 
 * Add these routes to your routes/api.php file
 * 
 * Make sure to import the DocumentController at the top of routes/api.php:
 * use App\Controllers\Api\v1\DocumentController;
 */

// Document Management Routes
$router->postApi('v1', '/documents/upload', [DocumentController::class, 'upload'], [
    APIKeyMiddleware::class, 
    AuthMiddleware::class, 
    RateLimiter::class
]);

$router->getApi('v1', '/documents/{entity_type}/{entity_id}', [DocumentController::class, 'getDocuments'], [
    APIKeyMiddleware::class, 
    AuthMiddleware::class, 
    RateLimiter::class
]);

$router->getApi('v1', '/documents/detail/{document_id}', [DocumentController::class, 'getDocument'], [
    APIKeyMiddleware::class, 
    AuthMiddleware::class, 
    RateLimiter::class
]);

$router->putApi('v1', '/documents/{document_id}/verify', [DocumentController::class, 'verifyDocument'], [
    APIKeyMiddleware::class, 
    AuthMiddleware::class, 
    RateLimiter::class
]);

$router->deleteApi('v1', '/documents/{document_id}', [DocumentController::class, 'deleteDocument'], [
    APIKeyMiddleware::class, 
    AuthMiddleware::class, 
    RateLimiter::class
]);

$router->getApi('v1', '/documents/categories', [DocumentController::class, 'getCategories'], [
    APIKeyMiddleware::class, 
    AuthMiddleware::class, 
    RateLimiter::class
]);
