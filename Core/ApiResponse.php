<?php

namespace App\Core;

/**
 * Standardized API response formatter
 * 
 * Provides consistent response format across all API endpoints
 * with support for success, error, pagination, and various HTTP status codes.
 */
class ApiResponse
{
    /**
     * Success response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return array Formatted response
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): array
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        http_response_code($statusCode);
        return $response;
    }

    /**
     * Error response
     *
     * @param string $message Error message
     * @param mixed $errors Error details
     * @param int $statusCode HTTP status code
     * @param string|null $errorCode Error code for client identification
     * @return array Formatted response
     */
    public static function error(
        string $message,
        $errors = null,
        int $statusCode = 400,
        ?string $errorCode = null
    ): array {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        http_response_code($statusCode);
        return $response;
    }

    /**
     * Paginated response
     *
     * @param array $data Response data
     * @param int $total Total number of items
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @param string $message Success message
     * @return array Formatted response with pagination
     */
    public static function paginated(
        array $data,
        int $total,
        int $page,
        int $perPage,
        string $message = 'Success'
    ): array {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'count' => count($data),
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0,
                'has_more' => ($page * $perPage) < $total,
            ]
        ];
    }

    /**
     * Created response (201)
     *
     * @param mixed $data Created resource data
     * @param string $message Success message
     * @return array Formatted response
     */
    public static function created($data, string $message = 'Resource created successfully'): array
    {
        return self::success($data, $message, 201);
    }

    /**
     * Accepted response (202)
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @return array Formatted response
     */
    public static function accepted($data = null, string $message = 'Request accepted for processing'): array
    {
        return self::success($data, $message, 202);
    }

    /**
     * No content response (204)
     *
     * @return void
     */
    public static function noContent(): void
    {
        http_response_code(204);
    }

    /**
     * Bad request response (400)
     *
     * @param string $message Error message
     * @param mixed $errors Error details
     * @return array Formatted response
     */
    public static function badRequest(string $message = 'Bad request', $errors = null): array
    {
        return self::error($message, $errors, 400, 'BAD_REQUEST');
    }

    /**
     * Unauthorized response (401)
     *
     * @param string $message Error message
     * @return array Formatted response
     */
    public static function unauthorized(string $message = 'Unauthorized'): array
    {
        return self::error($message, null, 401, 'UNAUTHORIZED');
    }

    /**
     * Forbidden response (403)
     *
     * @param string $message Error message
     * @return array Formatted response
     */
    public static function forbidden(string $message = 'Forbidden'): array
    {
        return self::error($message, null, 403, 'FORBIDDEN');
    }

    /**
     * Not found response (404)
     *
     * @param string $message Error message
     * @return array Formatted response
     */
    public static function notFound(string $message = 'Resource not found'): array
    {
        return self::error($message, null, 404, 'NOT_FOUND');
    }

    /**
     * Validation error response (422)
     *
     * @param array $errors Validation errors
     * @param string $message Error message
     * @return array Formatted response
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): array {
        return self::error($message, $errors, 422, 'VALIDATION_ERROR');
    }

    /**
     * Internal server error response (500)
     *
     * @param string $message Error message
     * @param mixed $errors Error details (only in debug mode)
     * @return array Formatted response
     */
    public static function serverError(
        string $message = 'Internal server error',
        $errors = null
    ): array {
        // Only include error details in debug mode
        $includeErrors = ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? $errors : null;
        
        return self::error($message, $includeErrors, 500, 'SERVER_ERROR');
    }

    /**
     * Service unavailable response (503)
     *
     * @param string $message Error message
     * @return array Formatted response
     */
    public static function serviceUnavailable(
        string $message = 'Service temporarily unavailable'
    ): array {
        return self::error($message, null, 503, 'SERVICE_UNAVAILABLE');
    }

    /**
     * Custom response with metadata
     *
     * @param mixed $data Response data
     * @param array $meta Additional metadata
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return array Formatted response
     */
    public static function withMeta(
        $data,
        array $meta,
        string $message = 'Success',
        int $statusCode = 200
    ): array {
        $response = self::success($data, $message, $statusCode);
        $response['meta'] = $meta;
        
        return $response;
    }

    /**
     * Collection response with count
     *
     * @param array $data Collection data
     * @param string $message Success message
     * @return array Formatted response
     */
    public static function collection(array $data, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'count' => count($data)
        ];
    }
}
