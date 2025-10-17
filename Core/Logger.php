<?php

namespace App\Core;

class Logger
{
    protected string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = rtrim($logPath, '/');
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context) : '';
        //show($_SERVER);
        $remote_ip = $_SERVER['REMOTE_ADDR'];
        $logLine = "[{$date}] [{$remote_ip}] {$level}: {$message} {$contextString}" . PHP_EOL;

        $identifier = explode(' ', $message)[0];

        $file = $identifier == '*' ? 'app.log' : 'db.log';

        file_put_contents($this->logPath . "/$file", $logLine, FILE_APPEND);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }
}
