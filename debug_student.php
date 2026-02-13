<?php

require_once 'vendor/autoload.php';

use OpenApi\Generator;
use OpenApi\Attributes as OA;

// Test scanning just StudentController
try {
    $logger = new class implements \Psr\Log\LoggerInterface {
        public function emergency(\Stringable|string $message, array $context = []): void {}
        public function alert(\Stringable|string $message, array $context = []): void {}
        public function critical(\Stringable|string $message, array $context = []): void {}
        public function error(\Stringable|string $message, array $context = []): void {}
        public function warning(\Stringable|string $message, array $context = []): void {}
        public function notice(\Stringable|string $message, array $context = []): void {}
        public function info(\Stringable|string $message, array $context = []): void {}
        public function debug(\Stringable|string $message, array $context = []): void {}
        public function log($level, \Stringable|string $message, array $context = []): void {}
    };

    $openapi = Generator::scan([
        'App/Controllers/Api/v1/StudentController.php',
    ], [
        'logger' => $logger
    ]);

    $json = $openapi->toJson();
    $decoded = json_decode($json);

    echo 'StudentController - Paths found: ' . (isset($decoded->paths) ? count((array)$decoded->paths) : 0) . PHP_EOL;
    if (isset($decoded->paths)) {
        echo 'Paths: ' . implode(', ', array_keys((array)$decoded->paths)) . PHP_EOL;
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
