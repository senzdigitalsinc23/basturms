<?php

namespace Database\ORM;

use App\Core\Database;
use App\Core\Request;
use App\Models\User;
use PDO;

abstract class Model
{
    protected static string $table;
    protected array $attributes = [];
    protected array $relations = [];
    protected PDO $db;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function __get($key)
    {
        // Lazy load relation if defined
        if (method_exists($this, $key)) {
            if (!isset($this->relations[$key])) {
                $this->relations[$key] = $this->$key();
            }
            return $this->relations[$key];
        }

        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public static function all($newTable = '')
    {
        $table = $newTable != '' ? $newTable : static::$table;
        
        // SECURITY: Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name');
        }
        
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT * FROM {$table}";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public static function select($fields = [], $newTable = '')
    {
        $table = $newTable != '' ? $newTable : static::$table;
        
        // SECURITY: Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name');
        }
        
        $db = Database::getInstance()->getConnection();

        $fields = implode(',',$fields);

        $sql = "SELECT $fields FROM {$table}";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public static function find(int $id): ?static
    {
        $table = static::$table;
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT * FROM {$table} WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $rows = $stmt->fetchObject(static::class);

        return $rows ?: null;
    }

    public static function where(string $column, $value, $newTable = '')
    {        
        $table = $newTable != '' ? $newTable : static::$table;
        
        // SECURITY: Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name');
        }
        
        // SECURITY: Validate column name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new \InvalidArgumentException('Invalid column name');
        }
        
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT * FROM {$table} WHERE {$column} = :value LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['value' => $value]);
        $rows = $stmt->fetchObject(static::class);

        return $rows ?: null;
    }

    public static function create(array $data, $newTable = "")
    {
        $table = $newTable != '' ? $newTable : static::$table;
        
        // SECURITY: Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name');
        }
        
        $db = Database::getInstance()->getConnection();

        $columns = implode(",", array_keys($data));

        $placeholders = implode(",:", array_keys($data));

        $placeholders = ":" . $placeholders;        

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($data);

        $id = (int)$db->lastInsertId();
        return static::find($id);
    }

    // -------------------
    // 🚀 Relation Helpers
    // -------------------

    protected function hasOne(string $related, string $foreignKey, string $localKey = 'id')
    {
        return $related::where($foreignKey, $this->$localKey) ?? null;
    }

    protected function hasMany(string $related, string $foreignKey, string $localKey = 'id')
    {
        return $related::where($foreignKey, $this->$localKey);
    }

    protected function belongsTo(string $related, string $foreignKey, string $ownerKey = 'id')
    {
        return $related::where($ownerKey, $this->$foreignKey) ?? null;
    }

    protected function belongsToMany(string $related, string $pivotTable, string $foreignKey, string $relatedKey)
    {
        $db = Database::getInstance()->getConnection();
        $table = static::$table;
        $sql = "SELECT r.* FROM {$related} r JOIN {$pivotTable} p ON r.id = p.{$relatedKey} WHERE p.{$foreignKey} = :fk";
        $stmt = $db->prepare($sql);
        $stmt->execute(['fk' => $this->id]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, $related);
    }

    
    public static function paginate($limit = 10, $offset = 0, $orderBy = '', $order = 'ASC')
    {
        $db = Database::getInstance()->getConnection();

        $table = static::$table;
        
        // SECURITY: Validate table name to prevent SQL injection
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name');
        }
        
        // SECURITY: Whitelist ORDER direction
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'ASC';
        }
        
        $sql = "SELECT * FROM {$table}";

        // Only add ORDER BY if column is specified
        // Note: Child classes should override this method with column whitelisting
        if ($orderBy !== '') {
            // Basic validation - alphanumeric and underscore only
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $orderBy)) {
                throw new \InvalidArgumentException('Invalid ORDER BY column name');
            }
            $sql .= " ORDER BY {$orderBy} {$order}";
        }
        
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    public static function countAll()
    {
        $db = Database::getInstance()->getConnection();

        $table = static::$table;

        $sql = "SELECT COUNT(*) as total FROM $table";
        $params = [];


        $stmt = $db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }


}
