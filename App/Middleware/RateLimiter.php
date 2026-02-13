<?php
// app/Middleware/RateLimiterMiddleware.php
namespace App\Middleware;

class RateLimiter
{
    private int $maxRequests;
    private int $perSeconds;
    private string $prefix;
    private int $maxViolations = 5;
    private int $banDuration = 3600; // 1 hour

    public function __construct()
    {
        $this->maxRequests = (int)($_ENV['RATE_LIMIT_MAX'] ?? 1000); // requests
        $this->perSeconds  = (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 1000); // seconds
        $this->prefix      = 'ratelimit_'; 
    }

    public function handle($request = null, $response = null, $next = null)
    {
        $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        // Sanitize IP for filename
        $safeIp = str_replace([':', '.'], '_', $ip);
        
        $path = $_SERVER['REQUEST_METHOD'] . ':' . ($_SERVER['REQUEST_URI'] ?? '/');
        $key  = $this->prefix . hash('sha256', $ip . '|' . $path);
        
        $banKey = 'ban_' . $safeIp;
        $violationKey = 'violation_' . $safeIp;

        // 1. Check if Banned
        $banData = $this->get($banKey);
        if ($banData) {
            $retryAfter = $banData['expires_at'] - time();
            if ($retryAfter > 0) {
                return $this->response429($response, $retryAfter, 'Access temporarily suspended due to repeated rate limit violations.');
            }
            // Ban expired, remove it (optional, lazy cleanup handled by file overwrite usually)
        }

        $now = time();
        $bucket = $this->get($key) ?? [];

        // Clean old
        $bucket = array_filter($bucket, fn($t) => ($now - $t) < $this->perSeconds);

        if (count($bucket) >= $this->maxRequests) {
            // Count violation
            $violations = $this->get($violationKey) ?? ['count' => 0, 'expires_at' => 0];
            
            // If violation record is old/expired, reset
            if ($violations['expires_at'] < $now) {
                $violations = ['count' => 0, 'expires_at' => $now + ($this->perSeconds * 10)]; // memory for 10x window
            }

            $violations['count']++;
            $violations['expires_at'] = $now + ($this->perSeconds * 10);
            $this->set($violationKey, $violations, 600); 

            // Check if processed into Ban
            if ($violations['count'] >= $this->maxViolations) {
                // BAN THEM
                $this->set($banKey, ['expires_at' => $now + $this->banDuration], $this->banDuration);
                return $this->response429($response, $this->banDuration, 'Access temporarily suspended due to repeated rate limit violations.');
            }

            return $this->response429($response, $this->perSeconds);
        }

        $bucket[] = $now;
        $this->set($key, $bucket, $this->perSeconds);
        if ($next) return $next($request, $response);
        return $response;
    }

    private function response429($response, $retryAfter, $message = 'Too Many Requests')
    {
        $resp = $response ?? new \App\Core\Response();
        $resp->setStatusCode(429);
        $resp->setHeader('Retry-After', max(1, $retryAfter));
        $resp->setHeader('Content-Type', 'application/json');
        $resp->setContent(json_encode([
            'success' => false,
            'code' => 429,
            'message' => $message]));
        return $resp;
    }

    private function apcuEnabled(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }

    private function getStoragePath(string $key): string
    {
        // Ensure no invalid chars in filename
        $safeKey = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $key);
        return dirname(__DIR__, 2) . '/storage/cache/' . $safeKey . '.cache';
    }

    private function get(string $key): ?array
    {
        if ($this->apcuEnabled()) {
            $ok = apcu_fetch($key, $success);
            return $success ? $ok : null;
        }
        $file = $this->getStoragePath($key);
        if (!file_exists($file)) return null;
        $json = file_get_contents($file);
        
        // Basic expiry check for file cache
        $data = $json ? json_decode($json, true) : null;
        if (!$data) return null;

        return $data;
    }

    private function set(string $key, array $value, int $ttl): void
    {
        if ($this->apcuEnabled()) {
            apcu_store($key, $value, $ttl);
            return;
        }
        $file = $this->getStoragePath($key);
        file_put_contents($file, json_encode($value));
    }
}
