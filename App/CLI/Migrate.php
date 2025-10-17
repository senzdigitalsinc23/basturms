<?php
namespace App\CLI;

class Migrate extends Command
{
    public function handle(array $args): void
    {
        $migrator = new Migrator(__DIR__ . '/../../database/migrations');
        $this->info("Starting migrations...");
        $migrator->migrate();
        $this->info("Migrations completed.");
    }

    
}
