<?php
namespace App\CLI;

class App
{
    protected array $argv;
    protected array $commands = [];

    public function __construct(array $argv)
    {
        $this->argv = $argv;

        // Register commands here
        $this->commands = [
            'migrate' => new Migrate(),
            'rollback' => new Rollback(),
            'make:controller' => new MakeController(),
            'make:model' => new MakeModel(),
            'make:view'  => new MakeView(),
            'seed' => new Seeder(),
            'run' => new DevServer(),
            // Add more commands as you want
        ];
    }

    public function run(): void
    {
        $commandName = $this->argv[1] ?? null;
        $args = array_slice($this->argv, 2);

        if (!$commandName || !isset($this->commands[$commandName])) {
            $this->printHelp();
            exit(1);
        }

        $command = $this->commands[$commandName];
        $command->handle($args);
    }

    protected function printHelp(): void
    {
        echo "Usage:\n";
        echo "  php cli.php [command] [options]\n\n";
        echo "Available commands:\n";
        foreach (array_keys($this->commands) as $command) {
            echo "  - {$command}\n";
        }
    }
}
