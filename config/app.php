<?php

use Dotenv\Dotenv;

// Try to load .env file if it exists
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

return [
    'name' => $_ENV['APP_NAME'] ?? 'Basturms',
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => $_ENV['APP_DEBUG'] ?? 'true',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost/basturms',
    'display_errors' => $_ENV['APP_DEBUG'] ?? 'true',
    'log_path' => __DIR__ . '/../storage/logs'
];
