<?php

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

return [
    'app' => $_ENV['APP_NAME'],
    'env' => $_ENV['APP_ENV'],
    'debug' => $_ENV['APP_DEBUG'],
    'url' => $_ENV['APP_URL'],
    'api_key'   => $_ENV['API_KEY']
];
