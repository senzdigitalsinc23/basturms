<?php
namespace App\Core;

class Cache
{
    protected string $path;

    public function __construct(string $path = __DIR__ . '/../storage/cache')
    {
        $this->path = $path;

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    protected function getFile(string $key): string
    {
        return $this->path . '/' . md5($key) . '.cache';
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $data = [
            'expires_at' => time() + $ttl,
            'value' => $value,
        ];
        file_put_contents($this->getFile($key), serialize($data));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFile($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));
        if ($data['expires_at'] < time()) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function forget(string $key): void
    {
        $file = $this->getFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function clear(): void
    {
        foreach (glob($this->path . '/*.cache') as $file) {
            unlink($file);
        }
    }
}
