<?php

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        try {
            // Get Authorization header - try multiple methods
            $authorization = '';
            
            // Method 1: getallheaders()
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            }
            
            // Method 2: $_SERVER
            if (empty($authorization)) {
                $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            }
            
            // Method 3: apache_request_headers()
            if (empty($authorization) && function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            }
            
            // Debug logging (optional - remove in production)
            $logPath = dirname(__DIR__, 2) . '/storage/logs/auth_debug.log';
            $debugInfo = [
                'timestamp' => date('Y-m-d H:i:s'),
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
                'authorization_header' => $authorization ? substr($authorization, 0, 50) . '...' : 'EMPTY',
                'all_headers' => function_exists('getallheaders') ? array_keys(getallheaders()) : 'N/A'
            ];
            @file_put_contents($logPath, json_encode($debugInfo, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
            
            // Extract JWT token from Bearer format
            if (empty($authorization)) {
                return $this->unauthorizedResponse($response, 'Missing or invalid authorization header');
            }
            
            if (!preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
                return $this->unauthorizedResponse($response, 'Authorization header must be in format: Bearer {token}');
            }
            
            $jwt = trim($matches[1]);
            
            if (empty($jwt)) {
                return $this->unauthorizedResponse($response, 'JWT token is empty');
            }
            
            // Validate JWT token
            $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
            
            try {
                $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
                
                // Check if token is expired
                if (isset($decoded->exp) && $decoded->exp < time()) {
                    return $this->unauthorizedResponse($response, 'Token has expired', 403);
                }
                
                // Attach user data to request for use in controllers
                $userData = [
                    'user_id' => $decoded->user_id ?? null,
                    'email' => $decoded->email ?? null,
                    'role' => $decoded->role ?? null,
                    'unit_id' => $decoded->unit_id ?? null
                ];
                
                $request->setAttribute('user', $userData);
                
                // Continue to next middleware/controller
                return $next($request, $response);
                
            } catch (\Firebase\JWT\ExpiredException $e) {
                return $this->unauthorizedResponse($response, 'Token has expired', 403);
            } catch (\Firebase\JWT\SignatureInvalidException $e) {
                return $this->unauthorizedResponse($response, 'Invalid token signature');
            } catch (\Exception $e) {
                return $this->unauthorizedResponse($response, 'Invalid token: ' . $e->getMessage());
            }
            
        } catch (\Throwable $e) {
            return $this->unauthorizedResponse($response, 'Authentication failed: ' . $e->getMessage());
        }
    }
    
    private function unauthorizedResponse(Response $response, string $message, int $statusCode = 401): Response
    {
        $response->setStatusCode($statusCode);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'success' => false,
            'message' => $message
        ]));
        return $response;
    }
}
