<?php
namespace App\Core;

class Config
{
    protected static array $config = [];
    protected static bool $loaded = false;

    /**
     * Load all PHP config files from config folder.
     *
     * @param string $configPath
     * @return void
     */
    public static function load(string $configPath): void
    {
        if (self::$loaded) {
            return;
        }

        $files = glob(rtrim($configPath, '/') . '/*.php');
        
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $config = require $file;
            if (is_array($config)) {
                self::$config[$key] = $config;
            }
        }

        //show($files);

        self::$loaded = true;
    }

    /**
     * Get config value by dot notation key, fallback to $_ENV if not found.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            throw new \Exception('Config not loaded. Call Config::load() first.');
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                // Fallback to environment variables (strtoupper + underscores)
                $envKey = strtoupper(str_replace('.', '_', $key));
    
                return $_ENV[$envKey] ?? $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
}
