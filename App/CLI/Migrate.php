<?php
namespace App\CLI;

class Migrate extends Command
{
    public function handle(array $args): void
    {
        $command = $args[0] ?? 'migrate'; // Default to migrate

        if ($command === 'migrate') {
            $migrator = new \Database\Migrator(__DIR__ . '/../../Database/migrations');
            $this->info("Starting migrations...");
            $migrator->migrate();
            $this->info("Migrations completed.");
        } elseif ($command === 'rollback') {
            $migrator = new \Database\Migrator(__DIR__ . '/../../Database/migrations');
            $this->info("Starting rollback...");
            $migrator->rollback();
            $this->info("Rollback completed.");
        } else {
            $this->error("Unknown command: {$command}. Available commands: migrate, rollback.");
        }
    }

    
}
