<?php
namespace App\CLI;

class Rollback extends Command
{
    public function handle(array $args): void
    {
        $migrator = new Migrator(__DIR__ . '/../../database/migrations');
        $this->info("Rolling back last batch...");
        $migrator->rollback();
        $this->info("Rollback completed.");
    }
}
