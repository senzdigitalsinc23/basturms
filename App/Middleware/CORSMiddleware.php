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
        $this->allowedOrigins = array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? ''));
        $this->allowedMethods = ['GET','POST','PUT','PATCH','DELETE','OPTIONS'];
        $this->allowedHeaders = ['Content-Type','Authorization','X-CSRF-TOKEN'];
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin && ($this->allowedOrigins === [''] || in_array($origin, $this->allowedOrigins, true))) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
            $response->setHeader('Access-Control-Allow-Methods', implode(',', $this->allowedMethods));
            $response->setHeader('Access-Control-Allow-Headers', implode(',', $this->allowedHeaders));
            $response->setHeader('Access-Control-Max-Age', '600');
        }

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response->setStatusCode(204);
            return $response;
        }

        return $next($request, $response);
    }
}
