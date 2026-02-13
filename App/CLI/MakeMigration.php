<?php

namespace App\CLI;

class MakeMigration extends Command
{
    public function handle(array $args): void
    {
        $migrationName = $args[0] ?? null;

        if (!$migrationName) {
            $this->error("Please provide a migration name. Usage: php cli.php make:migration CreateUsersTable");
            return;
        }

        $timestamp = date('YmdHis');
        $className = $this->formatMigrationClassName($migrationName, $timestamp);
        $fileName = "{$timestamp}_" . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $migrationName)) . ".php";
        $filePath = __DIR__ . "/../../Database/migrations/{$fileName}";

        $stub = "<?php\n\nuse Database\\Migration;\nuse Database\\ORM\\SchemaBuilder;\n\nclass {$className} extends Migration\n{\n    public function up(): void\n    {\n        // Schema::create('table_name', function (Blueprint \$table) {\n        //     \$table->id();\n        //     \$table->string('name');\n        //     \$table->timestamps();\n        // });\n        \$this->schema->create('your_table_name', function(SchemaBuilder \$schema) {\n            \$schema->id();\n            \$schema->string('name', 255)->nullable(false);\n            \$schema->timestamps();\n        });\n    }\n\n    public function down(): void\n    {\n        \$this->schema->dropIfExists('your_table_name');\n    }\n}";

        if (file_put_contents($filePath, $stub)) {
            $this->success("Migration created successfully: {$fileName}");
        } else {
            $this->error("Failed to create migration file.");
        }
    }

    protected function formatMigrationClassName(string $name, string $timestamp): string
    {
        $name = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
        return "{$name}{$timestamp}";
    }
}
