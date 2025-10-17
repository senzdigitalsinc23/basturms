<?php

namespace App\Core;

class Request
{
    protected string $uri;
    protected string $method;
    protected array $get;
    protected array $post;
    protected array $headers;
    protected array $bodyParams = [];

    public function __construct()
    {
        $this->uri = $this->parseUri();
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->get = $_GET;
        $this->post = $_POST;
        $this->headers = $this->getAllHeaders();

        $this->bodyParams = $this->detectBodyParams();
    }

    /**
     * Get the request URI (path only, without query string)
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the HTTP method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get GET parameters
     */
    public function getQuery(string $key = null, $default = null)
    {
        if ($key === null) return $this->get;
        return $this->get[$key] ?? $default;
    }

    /**
     * Get POST parameters
     */
    public function getPost(string $key = null, $default = null)
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        

        if (stripos($contentType, "application/json") !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? [];
            $this->post = $data;
        }echo json_encode($this->post);exit;

        if ($key === null) return $this->post;
        return $this->post[$key] ?? $default;
    }

    /**
     * Get header
     */
    public function getHeader(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    protected function getAllHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }
        
        // Fallback for CLI or when getallheaders() is not available
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $header = ucwords(strtolower($header), '-');
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }

    protected function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH); // remove query string
        return rtrim($uri, '/') ?: '/';
    }

    public function input(string $key, $default = null)
    {
        return $this->bodyParams[$key]
            ?? $this->get[$key]
            ?? $this->post[$key]
            ?? $default;
    }

    protected function detectBodyParams(): array
    {
        $contentType = $this->headers['Content-Type'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        if ($this->method === 'POST' || $this->method === 'PUT' || $this->method === 'PATCH') {
            return $this->post;
        }

        return [];
    }
}
