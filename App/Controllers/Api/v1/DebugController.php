<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class DebugController extends Controller
{
    public function headers(Request $request, Response $response): Response
    {
        $allHeaders = function_exists('getallheaders') ? getallheaders() : [];
        
        $serverHeaders = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $serverHeaders[$key] = $value;
            }
        }
        
        return $this->jsonResponse($response, [
            'success' => true,
            'getallheaders' => $allHeaders,
            'server_headers' => $serverHeaders,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN'
        ]);
    }
    
    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data, JSON_PRETTY_PRINT));
        $response->setStatusCode($statusCode);
        return $response;
    }
}
