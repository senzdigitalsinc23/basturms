<?php

require_once 'vendor/autoload.php';

use Database\Migrator;
use Dotenv\Dotenv;

// Load .env
if (file_exists('.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Define migrations path
$migrationsPath = __DIR__ . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'migrations';

echo "Initializing Migrator...\n";

try {
    $migrator = new Migrator($migrationsPath);
    
    echo "Running migrations...\n";
    $migrator->migrate();
    
    echo "\nAll pending migrations completed successfully!\n";
} catch (\Throwable $e) {
    echo "\nError during migration: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
