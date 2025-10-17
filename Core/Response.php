<?php

namespace App\Core;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $content = '';

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
        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        //echo json_encode(['success' => true, 'message' => 'Students retrieved successfully', 'data' => $this->headers]);

        echo $this->content;
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
        $this->setHeader('Access-Control-Allow-Origin', '*');
        $this->setHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,OPTIONS');
        $this->setContent(json_encode($data));
        $this->send();
    }
}
