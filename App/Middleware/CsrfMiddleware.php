<?php
// app/Middleware/CsrfMiddleware.php
namespace App\Middleware;

class CsrfMiddleware
{
    public function handle($request = null, $response = null, $next = null)
    {
        //echo json_encode(strtoupper((function_exists('getallheaders') ? getallheaders() : []))['X-CSRF-TOKEN']);exit;
        // Only protect state-changing requests
        $method = $_SERVER['REQUEST_METHOD'];
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $token = $headers['X-CSRF-TOKEN'] ?? $headers['x-csrf-token'] ?? $request->input('_csrf', '');

            //echo json_encode($_SESSION);exit;
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
