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
        
        // Filter out empty strings
        $this->allowedOrigins = array_filter($this->allowedOrigins, fn($o) => !empty($o));
        
        $this->allowedMethods = ['GET','POST','PUT','PATCH','DELETE','OPTIONS'];
        $this->allowedHeaders = ['Content-Type','Authorization','X-CSRF-TOKEN','X-API-KEY','X-Api-Key'];
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        // Get the origin from the request
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // If no origin header, try to extract from referer
        if (empty($origin) && !empty($_SERVER['HTTP_REFERER'])) {
            $parsed = parse_url($_SERVER['HTTP_REFERER']);
            if ($parsed) {
                $origin = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? '');
                if (!empty($parsed['port']) && $parsed['port'] != 80 && $parsed['port'] != 443) {
                    $origin .= ':' . $parsed['port'];
                }
            }
        }
        
        // Check if origin is allowed
        $isAllowed = !empty($origin) && (
            empty($this->allowedOrigins) || 
            in_array($origin, $this->allowedOrigins, true) ||
            in_array('*', $this->allowedOrigins, true)
        );
        
        // Log for debugging (in development only)
        if ($_ENV['APP_DEBUG'] ?? false) {
            error_log("CORS Debug - Origin: $origin, Allowed: " . ($isAllowed ? 'YES' : 'NO'));
            error_log("CORS Debug - Configured origins: " . implode(', ', $this->allowedOrigins));
        }
        
        // Handle preflight OPTIONS request
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            if ($isAllowed) {
                $response->setHeader('Access-Control-Allow-Origin', $origin);
                $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
                $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
                $response->setHeader('Access-Control-Allow-Credentials', 'true');
                $response->setHeader('Access-Control-Max-Age', '86400');
                $response->setHeader('Vary', 'Origin');
            }
            $response->setStatusCode(204);
            $response->setContent('');
            return $response;
        }
        
        // For actual requests, call next middleware/controller first
        $response = $next($request, $response);
        
        // Then add CORS headers to the response
        if ($isAllowed) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
            $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
            $response->setHeader('Vary', 'Origin');
        }

        return $response;
    }
}
