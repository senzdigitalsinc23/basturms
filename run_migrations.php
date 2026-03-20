<?php

require_once __DIR__ . '/vendor/autoload.php';

use Database\Migrator;
use Dotenv\Dotenv;

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Define migrations path
$migrationsPath = __DIR__ . '/Database/migrations';

echo "\033[36m╔════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║     AGH Validation System - Database Migration        ║\033[0m\n";
echo "\033[36m╚════════════════════════════════════════════════════════╝\033[0m\n\n";

echo "\033[33mDatabase Configuration:\033[0m\n";
echo "  Host: " . ($_ENV['DB_HOST'] ?? 'Not set') . "\n";
echo "  Database: " . ($_ENV['DB_NAME'] ?? 'Not set') . "\n";
echo "  User: " . ($_ENV['DB_USER'] ?? 'Not set') . "\n\n";

try {
    echo "\033[32m✓\033[0m Initializing Migrator...\n";
    $migrator = new Migrator($migrationsPath);
    
    echo "\033[32m✓\033[0m Running migrations...\n\n";
    $migrator->migrate();
    
    echo "\n\033[32m╔════════════════════════════════════════════════════════╗\033[0m\n";
    echo "\033[32m║  ✓ All migrations completed successfully!             ║\033[0m\n";
    echo "\033[32m╚════════════════════════════════════════════════════════╝\033[0m\n";
} catch (\PDOException $e) {
    echo "\n\033[31m✗ Database Error:\033[0m " . $e->getMessage() . "\n";
    echo "\n\033[33mTroubleshooting:\033[0m\n";
    echo "  1. Ensure MySQL/MariaDB is running\n";
    echo "  2. Verify database credentials in .env file\n";
    echo "  3. Create database: CREATE DATABASE " . ($_ENV['DB_NAME'] ?? 'agh_validations') . ";\n";
    exit(1);
} catch (\Throwable $e) {
    echo "\n\033[31m✗ Error during migration:\033[0m " . $e->getMessage() . "\n";
    echo "\n\033[33mStack trace:\033[0m\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
