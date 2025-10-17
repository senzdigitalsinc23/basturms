<?php
namespace App\CLI;

use App\Core\Database;

class Seeder extends Command
{
    protected string $seedersPath;

    public function __construct()
    {
        $this->seedersPath = __DIR__ . '/../../database/seeders';
    }

    public function handle(array $args): void
    {
        $seederName = $args[0] ?? null; // Optional: specific seeder

        if (!is_dir($this->seedersPath)) {
            $this->error("Seeders directory not found: {$this->seedersPath}");
            return;
        }

        $files = scandir($this->seedersPath);
        $seederFiles = array_filter($files, fn($file) => str_ends_with($file, '.php'));

        if (empty($seederFiles)) {
            $this->error("No seeder files found.");
            return;
        }

        if ($seederName) {
            $fileName = "{$seederName}.php";
            if (!in_array($fileName, $seederFiles)) {
                $this->error("Seeder '{$seederName}' not found.");
                return;
            }
            $this->runSeeder($fileName);
        } else {
            foreach ($seederFiles as $file) {
                $this->runSeeder($file);
            }
        }
    }

    protected function runSeeder(string $file): void
    {
        require_once $this->seedersPath . DIRECTORY_SEPARATOR . $file;

        $className = $this->getClassNameFromFile($file);
        if (!class_exists($className)) {
            $this->error("Seeder class {$className} not found in {$file}");
            return;
        }

        $seeder = new $className();

        if (!method_exists($seeder, 'run')) {
            $this->error("Seeder {$className} has no run() method.");
            return;
        }

        $this->info("Running seeder: {$className} ...");

        try {
            $seeder->run();
            $this->success("Seeder {$className} completed successfully.");
        } catch (\Throwable $e) {
            $this->error("Seeder {$className} failed: " . $e->getMessage());
        }
    }

    protected function getClassNameFromFile(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    
}
