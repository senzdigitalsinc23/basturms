<?php
// app/Middleware/CsrfMiddleware.php
namespace App\Middleware;

class CsrfMiddleware
{
    // Paths that should be excluded from CSRF protection
    private array $excludedPaths = [
        '/api/v1/validation/auth/login',
        '/api/v1/validation/auth/me',
        '/api/v1/validation/auth/change-password',
        '/api/v1/validation/staff',
        '/api/v1/validation/units',
        '/api/v1/validation/settings',
        '/api/v1/validation/departments',
        '/api/v1/validations',
        '/api/v1/staff/comprehensive',
        '/api/v1/staff/import',
    ];

    public function handle($request = null, $response = null, $next = null)
    {
        // Get the current request URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        
        // Check if this path should be excluded from CSRF protection
        foreach ($this->excludedPaths as $excludedPath) {
            if (strpos($requestPath, $excludedPath) === 0) {
                // Skip CSRF check for excluded paths
                if ($next) return $next($request, $response);
                return $response;
            }
        }
        
        // Only protect state-changing requests
        $method = $_SERVER['REQUEST_METHOD'];
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $token = $headers['X-CSRF-TOKEN'] ?? $headers['x-csrf-token'] ?? $request->input('_csrf', '');

            if (!$token || $token !== ($_SESSION['_csrf_token'] ?? '')) {
                $resp = $response ?? new \App\Core\Response();
                $resp->setStatusCode(419);
                $resp->setHeader('Content-Type', 'application/json');
                $resp->setContent(json_encode([
                    'success' => false,
                    'message' => 'Invalid request. CSRF token mismatch.',
                ]));
                return $resp;
            }
        }
        if ($next) return $next($request, $response);
        return $response;
    }
}
