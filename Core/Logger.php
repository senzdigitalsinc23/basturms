<?php

namespace App\Core;

class Logger
{
    protected string $logPath;
    protected bool $jsonFormat;

    /**
     * @param string $logPath
     * @param bool $jsonFormat If true, logs are written as JSON objects (one per line)
     */
    public function __construct(string $logPath, bool $jsonFormat = false)
    {
        $this->logPath = rtrim($logPath, '/');
        $this->jsonFormat = $jsonFormat;
    }

    /**
     * Log a message with rotation (daily). Keeps 7 days of logs.
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        if ($this->jsonFormat) {
            $logEntry = [
                'timestamp' => $date,
                'ip' => $remote_ip,
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ];
            $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        } else {
            $contextString = !empty($context) ? json_encode($context) : '';
            $logLine = "[{$date}] [{$remote_ip}] {$level}: {$message} {$contextString}" . PHP_EOL;
        }

        // Always use app.log for all messages in tests and general use
        $file = 'app.log';

        // Daily log rotation: filename.YYYY-MM-DD.log
        $today = date('Y-m-d');
        $rotatedFile = $this->logPath . "/" . $file . ".{$today}.log";

        // File size-based rotation: if >5MB, rotate (keep 5 backups)
        $maxSize = 5 * 1024 * 1024; // 5MB
        $maxBackups = 5;

        // Write first, then check for rotation (ensures file is rotated as soon as it exceeds threshold)
        file_put_contents($rotatedFile, $logLine, FILE_APPEND);
        clearstatcache(true, $rotatedFile);
        if (file_exists($rotatedFile) && filesize($rotatedFile) > $maxSize) {
            $this->rotateLogFile($rotatedFile, $maxBackups);
        }

        // Keep only 7 days of logs
        $this->cleanupOldLogs($file, 7);
    }

    /**
     * Rotate log file if it exceeds max size. Keeps up to $maxBackups backups.
     */
    protected function rotateLogFile(string $file, int $maxBackups): void
    {
        // $file is like app.log.YYYY-MM-DD.log
        // Rotate backups: .4 -> .5, .3 -> .4, ..., .1 -> .2
        for ($i = $maxBackups; $i >= 1; $i--) {
            $rotated = $file . ".{$i}";
            $prev = $i === 1 ? $file : $file . "." . ($i - 1);
            if (file_exists($rotated)) {
                @unlink($rotated);
            }
            if (file_exists($prev)) {
                @rename($prev, $rotated);
            }
        }
        // After rotation, create a new empty log file for continued logging
        @touch($file);
        // Optionally flush file system buffers (best effort)
        if (function_exists('fflush')) {
            foreach (glob($file . '.*') as $f) {
                $h = @fopen($f, 'r+');
                if ($h) { fflush($h); fclose($h); }
            }
        }
    }

    /**
     * Remove log files older than $days days for a given base log file.
     */
    protected function cleanupOldLogs(string $baseFile, int $days): void
    {
        $pattern = $this->logPath . "/" . $baseFile . ".*.log";
        $files = glob($pattern);
        $now = time();
        foreach ($files as $file) {
            if (preg_match('/\\.(\\d{4}-\\d{2}-\\d{2})\\.log$/', $file, $matches)) {
                $fileDate = strtotime($matches[1]);
                if ($fileDate !== false && $now - $fileDate > $days * 86400) {
                    @unlink($file);
                }
            }
        }
    }

    public function debug(string $message, array $context = []): void { $this->log('DEBUG', $message, $context); }
    public function info(string $message, array $context = []): void { $this->log('INFO', $message, $context); }
    public function notice(string $message, array $context = []): void { $this->log('NOTICE', $message, $context); }
    public function warning(string $message, array $context = []): void { $this->log('WARNING', $message, $context); }
    public function error(string $message, array $context = []): void { $this->log('ERROR', $message, $context); }
    public function critical(string $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
    public function alert(string $message, array $context = []): void { $this->log('ALERT', $message, $context); }
    public function emergency(string $message, array $context = []): void { $this->log('EMERGENCY', $message, $context); }
}
