<?php
namespace App\CLI;

class MakeController extends Command
{
    public function handle(array $args): void
    {
        $name = $args[0] ?? null;
        $directory = $args[1] ?? null;

        if (!$name) {
            $this->error("Please provide a controller name.");
            return;
        }

        $filename = '';
        if ($directory) {
            $filename = __DIR__ . "/../Controllers/{$directory}/{$name}Controller.php";
        }else {
            $filename = __DIR__ . "/../Controllers/{$name}Controller.php";
        }
        

        if (file_exists($filename)) {
            $this->error("Controller {$name}Controller already exists.");
            return;
        }

        $template = <<<PHP
        <?php
        namespace App\Controllers;

        class {$name}Controller extends Controller
        {
            public function index()
            {
                echo "Hello from {$name}Controller@index";
            }
        }
        PHP;

        file_put_contents($filename, $template);
        $this->info("Controller {$name}Controller created successfully.");
    }

    public function run(): void
    {
        $seederName = $this->args[0] ?? null;
        if (!$seederName) {
            echo "\033[31m[ERROR]\033[0m Seeder name required.\n";
            return;
        }

        $class = "\\Database\\Seeders\\{$seederName}";
        if (!class_exists($class)) {
            echo "\033[31m[ERROR]\033[0m Seeder {$seederName} not found.\n";
            return;
        }

        (new $class())->run();
        echo "\033[32m[SUCCESS]\033[0m Seeder {$seederName} executed.\n";
    }
}
