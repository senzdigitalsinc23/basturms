<?php

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        // Debug: log incoming Authorization header
        $logPath = dirname(__DIR__, 2) . '/storage/logs/api_debug.log';
        $authHeader = getallheaders()['Authorization'] ?? getallheaders()['authorization'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        //file_put_contents($logPath, date('c') . " [AuthMiddleware] From IP $ip Authorization: $authHeader\n", FILE_APPEND);

        if (!Session::get('user')) {
            // JWT check
            $headers = getallheaders();
            $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            
            if ($authorization && preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
                $jwt = $matches[1];
                // Get AuthService instance (assume global DI container or static access)
                $authService = isset($GLOBALS['container']) && $GLOBALS['container'] ? $GLOBALS['container']->get(\App\Services\AuthService::class) : null;
                if ($authService) {
                    $userDTO = $authService->validateToken($jwt);
                    if ($userDTO) {
                        Session::set('user', $userDTO->toArrayWithoutPassword());
                        Session::set('user_id', $userDTO->id);
                    }
                }
            }
        }

        return $next($request,$response);
    }
}
