<?php

namespace App\Exceptions;

use App\Core\Response;
use Throwable;
use PDOException;

/**
 * Global exception handler for the application
 * 
 * Converts exceptions to standardized JSON responses
 */
class ExceptionHandler
{
    /**
     * Handle an exception and return appropriate response
     */
    public static function handle(Throwable $e): Response
    {
        $response = new Response();

        // Handle custom application exceptions
        if ($e instanceof BaseException) {
            return self::handleBaseException($e, $response);
        }

        // Handle PDO exceptions
        if ($e instanceof PDOException) {
            return self::handlePDOException($e, $response);
        }

        // Handle generic exceptions
        return self::handleGenericException($e, $response);
    }

    /**
     * Handle BaseException instances
     */
    private static function handleBaseException(BaseException $e, Response $response): Response
    {
        $response->setStatusCode($e->getStatusCode());
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($e->toArray()));
        
        // Log the exception
        self::logException($e);
        
        return $response;
    }

    /**
     * Handle PDOException instances
     */
    private static function handlePDOException(PDOException $e, Response $response): Response
    {
        $dbException = DatabaseException::fromPDO($e);
        return self::handleBaseException($dbException, $response);
    }

    /**
     * Handle generic exceptions
     */
    private static function handleGenericException(Throwable $e, Response $response): Response
    {
        $isDebug = ($_ENV['APP_DEBUG'] ?? false) === true || ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        
        $data = [
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => $isDebug ? $e->getMessage() : 'An internal error occurred',
                'status' => 500
            ]
        ];

        // Include trace in debug mode
        if ($isDebug) {
            $data['error']['trace'] = $e->getTrace();
            $data['error']['file'] = $e->getFile();
            $data['error']['line'] = $e->getLine();
        }

        $response->setStatusCode(500);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        
        // Log the exception
        self::logException($e);
        
        return $response;
    }

    /**
     * Log exception to error log
     */
    private static function logException(Throwable $e): void
    {
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        error_log($logMessage);
    }

    /**
     * Render exception as JSON (for use in catch blocks)
     */
    public static function toJson(Throwable $e): string
    {
        if ($e instanceof BaseException) {
            return $e->toJson();
        }

        $isDebug = ($_ENV['APP_DEBUG'] ?? false) === true || ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        
        $data = [
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => $isDebug ? $e->getMessage() : 'An internal error occurred',
                'status' => 500
            ]
        ];

        if ($isDebug) {
            $data['error']['trace'] = $e->getTrace();
            $data['error']['file'] = $e->getFile();
            $data['error']['line'] = $e->getLine();
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Get HTTP status code from exception
     */
    public static function getStatusCode(Throwable $e): int
    {
        if ($e instanceof BaseException) {
            return $e->getStatusCode();
        }

        if ($e instanceof PDOException) {
            return 500;
        }

        return 500;
    }
}
