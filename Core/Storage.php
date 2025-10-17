<?php
namespace App\Core;

class Storage
{
    protected string $diskPath;

    public function __construct(string $diskPath = __DIR__ . '/../storage/files')
    {
        $this->diskPath = $diskPath;

        if (!is_dir($this->diskPath)) {
            mkdir($this->diskPath, 0777, true);
        }
    }

    public function put(string $filename, string $content): string
    {
        $path = $this->diskPath . '/' . $filename;
        file_put_contents($path, $content);
        return $path;
    }

    public function putUploaded(array $file, string $newName = null): ?string
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $filename = $newName ?? basename($file['name']);
        $path = $this->diskPath . '/' . $filename;

        move_uploaded_file($file['tmp_name'], $path);

        return $path;
    }

    public function get(string $filename): ?string
    {
        $path = $this->diskPath . '/' . $filename;
        return file_exists($path) ? file_get_contents($path) : null;
    }

    public function delete(string $filename): void
    {
        $path = $this->diskPath . '/' . $filename;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function exists(string $filename): bool
    {
        return file_exists($this->diskPath . '/' . $filename);
    }
}
