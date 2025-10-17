<?php
// app/Middleware/RateLimiterMiddleware.php
namespace App\Middleware;

class RateLimiter
{
    private int $maxRequests;
    private int $perSeconds;
    private string $prefix;

    public function __construct()
    {
        $this->maxRequests = (int)($_ENV['RATE_LIMIT_MAX'] ?? 60); // requests
        $this->perSeconds  = (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 60); // seconds
        $this->prefix      = 'ratelimit:';
    }

    public function handle(): void
    {
        $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $path = $_SERVER['REQUEST_METHOD'] . ':' . ($_SERVER['REQUEST_URI'] ?? '/');
        $key  = $this->prefix . hash('sha256', $ip . '|' . $path);

        $now = time();
        $bucket = $this->get($key) ?? [];

        // Clean old
        $bucket = array_filter($bucket, fn($t) => ($now - $t) < $this->perSeconds);

        if (count($bucket) >= $this->maxRequests) {
            http_response_code(429);
            header('Retry-After: ' . $this->perSeconds);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too Many Requests']);
            exit;
        }

        $bucket[] = $now;
        $this->set($key, $bucket, $this->perSeconds);
    }

    private function apcuEnabled(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }

    private function get(string $key): ?array
    {
        if ($this->apcuEnabled()) {
            $ok = apcu_fetch($key, $success);
            return $success ? $ok : null;
        }
        $file = sys_get_temp_dir() . '/' . $key;
        if (!file_exists($file)) return null;
        $json = file_get_contents($file);
        return $json ? json_decode($json, true) : null;
    }

    private function set(string $key, array $value, int $ttl): void
    {
        if ($this->apcuEnabled()) {
            apcu_store($key, $value, $ttl);
            return;
        }
        $file = sys_get_temp_dir() . '/' . $key;
        file_put_contents($file, json_encode($value));
        // basic TTL via cleanup is fine for temp files here
    }
}
