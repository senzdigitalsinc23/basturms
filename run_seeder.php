<?php

require_once __DIR__ . '/vendor/autoload.php';

use Database\Seeder;
use Dotenv\Dotenv;

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Get seeder name from command line argument
$seederName = $argv[1] ?? null;

if (!$seederName) {
    echo "\033[31mError:\033[0m Please specify a seeder name.\n";
    echo "\033[33mUsage:\033[0m php run_seeder.php ValidationSeeder\n";
    exit(1);
}

// Define seeders path
$seedersPath = __DIR__ . '/Database/seeders';
$seederFile = $seedersPath . '/' . $seederName . '.php';

echo "\033[36m╔════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║     AGH Validation System - Database Seeder           ║\033[0m\n";
echo "\033[36m╚════════════════════════════════════════════════════════╝\033[0m\n\n";

if (!file_exists($seederFile)) {
    echo "\033[31m✗ Seeder file not found:\033[0m {$seederFile}\n";
    echo "\n\033[33mAvailable seeders:\033[0m\n";
    $files = glob($seedersPath . '/*.php');
    foreach ($files as $file) {
        echo "  - " . basename($file, '.php') . "\n";
    }
    exit(1);
}

try {
    echo "\033[32m✓\033[0m Loading seeder: {$seederName}\n";
    
    // Include and run the seeder
    $seeder = require $seederFile;
    
    if (!$seeder instanceof Seeder) {
        echo "\033[31m✗ Error:\033[0m Seeder must return an instance of Database\\Seeder\n";
        exit(1);
    }
    
    echo "\033[32m✓\033[0m Running seeder...\n\n";
    $seeder->run();
    
    echo "\n\033[32m╔════════════════════════════════════════════════════════╗\033[0m\n";
    echo "\033[32m║  ✓ Seeder completed successfully!                     ║\033[0m\n";
    echo "\033[32m╚════════════════════════════════════════════════════════╝\033[0m\n";
} catch (\PDOException $e) {
    echo "\n\033[31m✗ Database Error:\033[0m " . $e->getMessage() . "\n";
    exit(1);
} catch (\Throwable $e) {
    echo "\n\033[31m✗ Error during seeding:\033[0m " . $e->getMessage() . "\n";
    echo "\n\033[33mStack trace:\033[0m\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
