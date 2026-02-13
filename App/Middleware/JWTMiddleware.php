<?php

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTMiddleware implements MiddlewareInterface
{
    private string $secret;

    public function __construct(string $secret = null)
    {
        // Fallback so it doesn't crash if env isn't wired yet
        $this->secret = $secret ?? ($_ENV['JWT_SECRET'] ?? 'change_me');
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
            $response->setStatusCode(401);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['error' => 'Unauthorized']));
            return $response;
        }

        $token = $m[1];

        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

            // Optionally attach the user to the request (if your Request supports it)
            if (method_exists($request, 'setUser') && isset($decoded->sub)) {
                if (class_exists(\App\Models\User::class) && method_exists(\App\Models\User::class, 'find')) {
                    $user = \App\Models\User::find((int)$decoded->sub);
                    if ($user && method_exists($request, 'setUser')) {
                        $request->setUser($user);
                    }
                }
            }
        } catch (\Throwable $e) {
            $response->setStatusCode(401);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['error' => 'Invalid or expired token']));
            return $response;
        }

        return $next($request, $response);
    }
}
