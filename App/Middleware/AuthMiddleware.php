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
