<?php
namespace Database;

use PDO;
use App\Core\Database;
use Database\ORM\SchemaBuilder;

abstract class Migration
{
    protected PDO $db;
    protected $schema;

    public function __construct()
    {
         $this->schema = new SchemaBuilder();
        $this->db = Database::getInstance()->getConnection();
    }

    abstract public function up(): void;
    abstract public function down(): void;

    public function execute(string $sql): void
    {
        $this->db->exec($sql);
    }

    
}
