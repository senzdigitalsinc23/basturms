<?php

namespace App\Core;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $content = '';

    /**
     * Response constructor.
     *
     * @param string $content
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Get content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    /**
     * Add a header
     */
    public function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    /**
     * Set content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Send headers and content to the client
     */
    public function send(): void
    {
        // Add CORS headers if not already set
        if (!isset($this->headers['Access-Control-Allow-Origin'])) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
            $allowedOrigins = array_map('trim', $allowedOrigins);
            
            if (!empty($origin) && (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins))) {
                $this->setHeader('Access-Control-Allow-Origin', $origin);
                $this->setHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
                $this->setHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-CSRF-TOKEN,X-API-KEY,X-Api-Key');
                $this->setHeader('Access-Control-Allow-Credentials', 'true');
            }
        }
        
        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo $this->content;exit;
    }

    public static function download(string $filePath, ?string $fileName = null, string $contentType = 'application/octet-stream'): void
    {
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "File not found.";
            return;
        }

        if ($fileName === null) {
            $fileName = basename($filePath);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        readfile($filePath);
        exit;
    }

    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        
        // Add CORS headers if not already set
        if (!isset($this->headers['Access-Control-Allow-Origin'])) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $allowedOrigins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
            $allowedOrigins = array_map('trim', $allowedOrigins);
            
            if (!empty($origin) && (in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins))) {
                $this->setHeader('Access-Control-Allow-Origin', $origin);
                $this->setHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS');
                $this->setHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-CSRF-TOKEN,X-API-KEY,X-Api-Key');
                $this->setHeader('Access-Control-Allow-Credentials', 'true');
            }
        }
        
        $this->setContent(json_encode($data));
        $this->send();
    }
}
