<?php

namespace App\Core;

use Psr\Log\LogLevel;

/**
 * Logger factory for creating configured logger instances
 */
class LoggerFactory
{
    private static ?Logger $instance = null;

    /**
     * Get singleton logger instance
     *
     * @return Logger
     */
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = self::createLogger();
        }

        return self::$instance;
    }

    /**
     * Create a new logger instance
     *
     * @param string|null $logPath Custom log path
     * @param string|null $minLevel Custom minimum level
     * @return Logger
     */
    public static function createLogger(?string $logPath = null, ?string $minLevel = null): Logger
    {
        // Determine log level based on environment
        if ($minLevel === null) {
            $env = $_ENV['APP_ENV'] ?? 'production';
            $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
            
            if ($env === 'development' || $debug) {
                $minLevel = LogLevel::DEBUG;
            } elseif ($env === 'testing') {
                $minLevel = LogLevel::INFO;
            } else {
                $minLevel = LogLevel::WARNING; // Production
            }
        }

        return new Logger($logPath, $minLevel);
    }

    /**
     * Create logger for specific component
     *
     * @param string $component Component name (e.g., 'auth', 'database', 'api')
     * @return Logger
     */
    public static function createComponentLogger(string $component): Logger
    {
        $logPath = dirname(__DIR__) . "/storage/logs/{$component}.log";
        return self::createLogger($logPath);
    }

    /**
     * Reset singleton instance (useful for testing)
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}