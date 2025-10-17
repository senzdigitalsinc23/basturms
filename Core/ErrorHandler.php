<?php

namespace App\Core;

use Throwable;

class ErrorHandler
{
    protected Logger $logger;
    protected bool $displayErrors;

    public function __construct(Logger $logger, bool $displayErrors = false)
    {
        $this->logger = $logger;
        $this->displayErrors = $displayErrors;
    }

    public function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', $this->displayErrors ? '1' : '0');

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        $this->logger->error("* PHP Error: {$message} in {$file} on line {$line}");
        if ($this->displayErrors) {
            echo "<b>PHP Error:</b> {$message} in {$file} on line {$line}";
        }
        return true; // prevent default PHP error handler
    }

    public function handleException(Throwable $exception): void
    {
        $this->logger->error(
            "Uncaught Exception: {$exception->getMessage()} in {$exception->getFile()} on line {$exception->getLine()}",
            ['trace' => $exception->getTrace()]
        );

        if ($this->displayErrors) {
            echo "<pre>Uncaught Exception: " . $exception . "</pre>";
        } else {
            http_response_code(500);
            echo "Something went wrong. Please try again later.";
        }
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error) {
            $this->logger->error("* Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
        }
    }
}
