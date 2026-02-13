<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuthLog;
use App\Core\Request;

/**
 * Service for logging audit and authentication events.
 */
class LoggingService
{
    private Request $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Log audit actions.
     *
     * @param string $action The action performed.
     * @param string|null $details Additional details about the action.
     * @param string|null $userId The ID of the user performing the action.
     * @return void
     */
    public function logAudit(string $action, ?string $details = null, ?string $userId = null): void
    {
        try {
            $clientInfo = $this->getClientInfo();

            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'details' => $details,
                'client_info' => json_encode($clientInfo),
                'ip_address' => $this->getClientIP(),
                'user_agent' => $this->getUserAgent()
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the main flow
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }

    /**
     * Log authentication events.
     *
     * @param string $event The event name.
     * @param string $status The status of the event (e.g., 'success', 'failure').
     * @param string|null $details Additional details about the event.
     * @param string|null $userId The ID of the user involved.
     * @return void
     */
    public function logAuth(string $event, string $status, ?string $details = null, ?string $userId = null): void
    {
        try {
            $clientInfo = $this->getClientInfo();

            // Prepare data, ensuring user_id is properly handled
            $logData = [
                'event' => $event,
                'event_status' => $status,
                'details' => $details,
                'client_info' => json_encode($clientInfo + ['user_agent' => $this->getUserAgent()]),
                'ip_address' => $this->getClientIP()
            ];

            // Only add user_id if it's not null
            if ($userId !== null) {
                $logData['user_id'] = $userId;
            }

            AuthLog::create($logData, 'auth_logs');
        } catch (\Exception $e) {
            // Log error but don't break the main flow
            error_log("Auth logging failed: " . $e->getMessage());
        }
    }

    /**
     * Log API debug errors.
     * 
     * @param string $message The error message.
     * @return void
     */
    public function logApiDebugError(string $message): void
    {
        $this->logAudit('api_debug_error', $message);
    }

    /**
     * Log system errors.
     * 
     * @param string $message The error message.
     * @return void
     */
    public function logSystemError(string $message): void
    {
        $this->logAudit('system_error', $message);
    }

    /**
     * Get client information.
     * 
     * @return array The client information array.
     */
    private function getClientInfo(): array
    {
        return [
            'ip' => $this->getClientIP(),
            'user_agent' => $this->getUserAgent(),
            'hostname' => gethostname(),
            'mac' => $this->getMacAddress(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get client IP address.
     * 
     * @return string The client IP address.
     */
    private function getClientIP(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user agent.
     * 
     * @return string The user agent string.
     */
    private function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }

    /**
     * Get MAC address (limited functionality on web).
     * 
     * @return string The MAC address or placeholder.
     */
    private function getMacAddress(): string
    {
        // Note: MAC address cannot be reliably obtained from web requests
        // This is a placeholder for the requirement
        return 'web-client';
    }
}
