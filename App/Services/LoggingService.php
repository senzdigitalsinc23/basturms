<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuthLog;
use App\Core\Request;

class LoggingService
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Log audit actions
     */
    public function logAudit(string $action, string $details = null, string $userId = null): void
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
     * Log authentication events
     */
    public function logAuth(string $event, string $status, string $details = null, string $userId = null): void
    {
        try {
            $clientInfo = $this->getClientInfo();
            
            AuthLog::create([
                'user_id' => $userId,
                'event' => $event,
                'event_status' => $status,
                'details' => $details,
                'client_info' => json_encode($clientInfo + ['user_agent' => $this->getUserAgent()]),
                'ip_address' => $this->getClientIP()
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the main flow
            error_log("Auth logging failed: " . $e->getMessage());
        }
    }

    /**
     * Get client information
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
     * Get client IP address
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
     * Get user agent
     */
    private function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }

    /**
     * Get MAC address (limited functionality on web)
     */
    private function getMacAddress(): string
    {
        // Note: MAC address cannot be reliably obtained from web requests
        // This is a placeholder for the requirement
        return 'web-client';
    }
}
