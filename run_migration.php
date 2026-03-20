<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Database\Migrator;

$migrator = new Migrator(__DIR__ . '/Database/migrations');
$migrator->migrate();

echo "\nMigration completed!\n";
