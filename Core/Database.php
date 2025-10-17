<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    protected static ?Database $instance = null;
    protected PDO $connection;
    protected Logger $logger;

    private function __construct()
    {
        // Load config PHP files
        Config::load(dirname(__DIR__) . '/config');

        // Get DB config from your config helper
        $host = Config::get('database.host');
        $db   = Config::get('database.dbname');
        $user = Config::get('db_user');
        $pass = Config::get('db_pass');
        $driver = Config::get('db_driver');
        $charset = Config::get('database.charset', 'utf8mb4');

        $this->logger = new Logger(dirname(__DIR__) . '/storage/logs');

        $dsn = "$driver:host={$host};dbname={$db};charset={$charset}";

        

        try {
            $this->connection = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->logger->error("Database connection failed: " . $e->getMessage());
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function fetch(string $sql, array $params = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchSingle(string $sql, array $params = []): ?array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function query(string $sql, array $params = []): bool
    {
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
    }
}
