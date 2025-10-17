<?php

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$emailConfig = [
    'host' => $_ENV['MAIL_HOST'],
    'username' => $_ENV['MAIL_USER'],
    'password' => $_ENV['MAIL_PASS'],
    'from' => $_ENV['MAIL_FROM'],
    'name' => $_ENV['MAIL_NAME'],
    'port'  => $_ENV['MAIL_PORT'],
    'encryption' => $_ENV['MAIL_ENCRYPTION']
];

return $emailConfig;