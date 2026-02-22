<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Cache;

/**
 * Health check controller for monitoring application status
 * 
 * Provides endpoints to check the health of the application
 * and its dependencies (database, cache, disk space).
 */
class HealthController extends Controller
{
    /**
     * Comprehensive health check
     *
     * @return string JSON response with health status
     */
    public function check(): string
    {
        $checks = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
            'checks' => []
        ];

        // Database check
        $checks['checks']['database'] = $this->checkDatabase();
        
        // Cache check
        $checks['checks']['cache'] = $this->checkCache();
        
        // Disk space check
        $checks['checks']['disk'] = $this->checkDiskSpace();
        
        // PHP version check
        $checks['checks']['php'] = $this->checkPhp();

        // Determine overall status
        foreach ($checks['checks'] as $check) {
            if ($check['status'] === 'unhealthy') {
                $checks['status'] = 'unhealthy';
                break;
            } elseif ($check['status'] === 'warning' && $checks['status'] === 'healthy') {
                $checks['status'] = 'warning';
            }
        }

        // Set HTTP status code
        http_response_code($checks['status'] === 'healthy' ? 200 : 503);
        
        // Set content type header
        header('Content-Type: application/json');

        return json_encode($checks, JSON_PRETTY_PRINT);
    }

    /**
     * Simple health check (fast)
     *
     * @return string JSON response with basic health status
     */
    public function ping(): string
    {
        header('Content-Type: application/json');
        
        return json_encode([
            'status' => 'ok',
            'timestamp' => date('c'),
            'message' => 'Application is running'
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Check database connectivity
     *
     * @return array Database health status
     */
    private function checkDatabase(): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query('SELECT 1 as health_check');
            $result = $stmt->fetch();
            
            if ($result && $result['health_check'] == 1) {
                return [
                    'status' => 'healthy',
                    'message' => 'Database connection successful',
                    'response_time' => $this->measureResponseTime(function() use ($db) {
                        $db->query('SELECT 1');
                    })
                ];
            }
            
            return [
                'status' => 'unhealthy',
                'message' => 'Database query returned unexpected result'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check cache functionality
     *
     * @return array Cache health status
     */
    private function checkCache(): array
    {
        try {
            $cache = new Cache();
            $testKey = 'health_check_' . time();
            $testValue = 'ok';
            
            // Test write
            $cache->set($testKey, $testValue, 10);
            
            // Test read
            $value = $cache->get($testKey);
            
            // Cleanup
            $cache->forget($testKey);
            
            if ($value === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache is working correctly'
                ];
            }
            
            return [
                'status' => 'warning',
                'message' => 'Cache read/write mismatch'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Cache check failed (non-critical)',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check disk space
     *
     * @return array Disk space status
     */
    private function checkDiskSpace(): array
    {
        try {
            $path = __DIR__ . '/../../storage';
            $freeSpace = disk_free_space($path);
            $totalSpace = disk_total_space($path);
            
            if ($freeSpace === false || $totalSpace === false) {
                return [
                    'status' => 'warning',
                    'message' => 'Unable to check disk space'
                ];
            }
            
            $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
            
            $status = 'healthy';
            $message = "Disk usage: {$usedPercent}%";
            
            if ($usedPercent >= 95) {
                $status = 'unhealthy';
                $message = "Critical: Disk usage at {$usedPercent}%";
            } elseif ($usedPercent >= 90) {
                $status = 'warning';
                $message = "Warning: Disk usage at {$usedPercent}%";
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'free_space' => $this->formatBytes($freeSpace),
                'total_space' => $this->formatBytes($totalSpace),
                'used_percent' => $usedPercent
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Disk space check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check PHP configuration
     *
     * @return array PHP status
     */
    private function checkPhp(): array
    {
        $version = PHP_VERSION;
        $requiredVersion = '8.0.0';
        
        $status = version_compare($version, $requiredVersion, '>=') ? 'healthy' : 'warning';
        
        return [
            'status' => $status,
            'message' => "PHP version: {$version}",
            'version' => $version,
            'required' => $requiredVersion,
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
                'json' => extension_loaded('json'),
            ]
        ];
    }

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Measure response time of a function
     *
     * @param callable $callback
     * @return string Response time in ms
     */
    private function measureResponseTime(callable $callback): string
    {
        $start = microtime(true);
        $callback();
        $end = microtime(true);
        
        return round(($end - $start) * 1000, 2) . 'ms';
    }
}
