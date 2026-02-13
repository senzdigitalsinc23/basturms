<?php
// app/Middleware/CorsMiddleware.php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class CorsMiddleware
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;

    public function __construct()
    {
        $corsOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
        // Remove quotes if present and split by comma
        $corsOrigins = trim($corsOrigins, '"\'');
        $this->allowedOrigins = array_map(function($origin) {
            return trim(trim($origin, '"\''));
        }, explode(',', $corsOrigins));
        $this->allowedMethods = ['GET','POST','PUT','PATCH','DELETE','OPTIONS'];
        $this->allowedHeaders = ['Content-Type','Authorization','X-CSRF-TOKEN','X-API-KEY'];
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        // Extract origin from referer if needed
        if (empty($origin) && !empty($_SERVER['HTTP_REFERER'])) {
            $parsed = parse_url($_SERVER['HTTP_REFERER']);
            if ($parsed) {
                $origin = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? '') . 
                         (!empty($parsed['port']) ? ':' . $parsed['port'] : '');
            }
        }
        
        // Filter out empty strings from allowed origins
        $this->allowedOrigins = array_filter($this->allowedOrigins, fn($o) => !empty($o));
        
        // Check if origin is allowed (or if no origins are configured, allow all for development)
        $isAllowed = empty($this->allowedOrigins) || in_array($origin, $this->allowedOrigins, true);
        
        // Handle preflight OPTIONS request first - must return CORS headers
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            if ($origin && $isAllowed) {
                $response->setHeader('Access-Control-Allow-Origin', $origin);
                $response->setHeader('Vary', 'Origin');
                $response->setHeader('Access-Control-Allow-Credentials', 'true');
                $response->setHeader('Access-Control-Allow-Methods', implode(',', $this->allowedMethods));
                $response->setHeader('Access-Control-Allow-Headers', implode(',', $this->allowedHeaders));
                $response->setHeader('Access-Control-Max-Age', '600');
            }
            $response->setStatusCode(204);
            return $response;
        }
        
        // Set CORS headers for actual requests
        if ($origin && $isAllowed) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
            $response->setHeader('Access-Control-Allow-Methods', implode(',', $this->allowedMethods));
            $response->setHeader('Access-Control-Allow-Headers', implode(',', $this->allowedHeaders));
            $response->setHeader('Access-Control-Max-Age', '600');
        }

        return $next($request, $response);
    }
}
