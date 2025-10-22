<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\LoggingService;

class AuditMiddleware
{
    private LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        // Get the current user ID from session or request
        $userId = $this->getCurrentUserId($request);
        
        // Log the action
        $action = $this->getActionFromRequest($request);
        $details = $this->getActionDetails($request);
        
        $this->loggingService->logAudit($action, $details, $userId);
        
        // Continue with the request
        return $next($request, $response);
    }

    private function getCurrentUserId(Request $request): ?string
    {
        // Try to get user ID from session, headers, or request
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        
        // Check for user ID in headers
        $userId = $request->getHeader('X-User-ID');
        if ($userId) {
            return $userId;
        }
        
        // Check for user ID in request data
        $postData = $request->getPost();
        if (isset($postData['user_id'])) {
            return $postData['user_id'];
        }
        
        return null;
    }

    private function getActionFromRequest(Request $request): string
    {
        $method = $request->getMethod();
        $path = $request->getPath();
        
        // Map HTTP methods to actions
        $actionMap = [
            'GET' => 'view',
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete'
        ];
        
        $baseAction = $actionMap[$method] ?? 'unknown';
        
        // Extract resource from path
        $pathParts = explode('/', trim($path, '/'));
        $resource = end($pathParts);
        
        return $baseAction . '_' . $resource;
    }

    private function getActionDetails(Request $request): string
    {
        $method = $request->getMethod();
        $path = $request->getPath();
        $data = $request->getPost();
        
        $details = [
            'method' => $method,
            'path' => $path,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Include relevant data based on the action
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $details['data'] = $this->sanitizeData($data);
        }
        
        return json_encode($details);
    }

    private function sanitizeData(array $data): array
    {
        // Remove sensitive information
        $sensitiveFields = ['password', 'token', 'secret', 'key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        return $data;
    }
}
