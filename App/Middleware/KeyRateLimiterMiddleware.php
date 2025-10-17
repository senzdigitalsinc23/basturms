<?php
// app/Middleware/KeyRateLimiterMiddleware.php
namespace App\Middleware;

use App\Support\ApiPath;

class KeyRateLimiterMiddleware
{
    private int $max;
    private int $window; // seconds
    private string $prefix = 'rl:';

    public function __construct()
    {
        $this->max    = (int)($_ENV['API_RATE_MAX'] ?? 120);    // requests
        $this->window = (int)($_ENV['API_RATE_WINDOW'] ?? 60);  // per N seconds
    }

    public function handle(): void
    {
        if (!ApiPath::isApi() || ApiPath::isWhitelisted()) {
            return;
        }

        $key = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? null);
        $id  = $key ?: ('ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $now = time();
        $bucketKey = $this->prefix . hash('sha256', $id . '|' . ($_SERVER['REQUEST_METHOD'] ?? '') . '|' . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));

        $bucket = $this->get($bucketKey) ?? [];

        // prune
        $bucket = array_filter($bucket, fn($t) => ($now - $t) < $this->window);

        $remaining = $this->max - count($bucket);
        $reset     = $this->window - ($now - (empty($bucket) ? $now : min($bucket)));

        if ($remaining <= 0) {
            $this->headers(0, $reset);
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too Many Requests']);
            exit;
        }

        $bucket[] = $now;
        $this->set($bucketKey, $bucket, $this->window);
        $this->headers($remaining - 1, $reset);
    }

    private function headers(int $remaining, int $reset): void
    {
        header('RateLimit-Limit: ' . $this->max);
        header('RateLimit-Remaining: ' . max(0, $remaining));
        header('RateLimit-Reset: ' . max(0, $reset));
    }

    private function apcu(): bool { return function_exists('apcu_enabled') && apcu_enabled(); }

    private function get(string $key): ?array
    {
        if ($this->apcu()) {
            $ok = apcu_fetch($key, $hit);
            return $hit ? $ok : null;
        }
        $file = sys_get_temp_dir() . '/' . $key;
        if (!is_file($file)) return null;
        $json = @file_get_contents($file);
        return $json ? json_decode($json, true) : null;
    }

    private function set(string $key, array $value, int $ttl): void
    {
        if ($this->apcu()) {
            apcu_store($key, $value, $ttl);
            return;
        }
        $file = sys_get_temp_dir() . '/' . $key;
        @file_put_contents($file, json_encode($value));
    }
}
