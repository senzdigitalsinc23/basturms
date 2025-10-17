<?php

namespace App\Middleware;

class CsrfMiddleware
{
    public function handle(): void
    {
        // Only protect state-changing requests
        $method = $_SERVER['REQUEST_METHOD'];
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $headers = getallheaders();
            $token = $headers['X-CSRF-TOKEN'] ?? $_POST['_csrf'] ?? '';

            if (!$token || $token !== ($_SESSION['csrf_token'] ?? '')) {
                http_response_code(419); // 419 Authentication Timeout
                header('Content-Type: application/json');
                echo json_encode(['error' => 'CSRF token mismatch']);
                exit;
            }
        }
    }
}
