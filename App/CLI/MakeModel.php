<?php
namespace App\CLI;

class MakeModel extends Command
{
    public function handle(array $args): void
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error("Please provide a model name.");
            return;
        }

        $filename = __DIR__ . "/../Models/{$name}.php";

        if (file_exists($filename)) {
            $this->error("Model {$name} already exists.");
            return;
        }

        $template = <<<PHP
        <?php
        namespace App\Models;

        use App\Core\Database;
        use App\Core\Session;
        use Database\ORM\Model;

        class Student extends Model
        {

            
            protected static string table = '$name's;

            public int \$id;
            public string \$name;
            public string \$email;
            public string \$password;
            public ?string \$created_at;
            public ?string \$updated_at;
            public string \$status;
            public ?string \$is_super_admin;
            public ?int \$role_id;

            protected \$db;

            /**
             * Hide password when converting to array.
             */
            public function toArray(): array
            {
                return [
                    'id'    => \$this->id,
                    'name'  => \$this->name,
                    'email' => \$this->email,
                    'created_at' => \$this->created_at,
                    'updated_at' => \$this->updated_at,
                    'is_super_admin' => \$this->is_super_admin,
                    'role_id' => \$this->role_id,
                    'status'   => \$this->status
                ];
            }

            public function __construct() {
                \$this->db = Database::getInstance()->getConnection();
            }

        }

        PHP;

        //show($filename);

        file_put_contents($filename, $template);
        $this->info("{$name} model created successfully.");
    }

    private function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    
}
