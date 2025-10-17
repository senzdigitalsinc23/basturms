<?php

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$smsConfig = [
    'sid' => $_ENV['SMS_ID'],
    'token' => $_ENV['TOKEN'],
    'from' => $_ENV['SMS_FROM']
];

return $smsConfig;