<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class CorsTestController extends Controller
{
    public function test(Request $request, Response $response): Response
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? 'Not set';
        $corsConfig = $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'Not set';
        
        $data = [
            'success' => true,
            'message' => 'CORS test endpoint',
            'debug' => [
                'origin_received' => $origin,
                'cors_config' => $corsConfig,
                'all_headers' => getallheaders(),
                'response_headers' => $response->getHeaders(),
            ]
        ];
        
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data, JSON_PRETTY_PRINT));
        $response->setStatusCode(200);
        
        return $response;
    }
}
