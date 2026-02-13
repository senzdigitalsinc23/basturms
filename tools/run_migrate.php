<?php
require __DIR__ . '/../vendor/autoload.php';

use Database\Migrator;

$migrator = new Migrator(__DIR__ . '/../Database/migrations');
$migrator->migrate();

echo "Migrations finished.\n";
