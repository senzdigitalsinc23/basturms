<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Core\Database;
use PDO;

/**
 * Base test case for all tests
 * 
 * Provides common functionality for testing including:
 * - Database setup/teardown
 * - Mock creation helpers
 * - Assertion helpers
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Database connection for tests
     */
    protected ?PDO $db = null;

    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load environment variables
        $this->loadEnvironment();
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        $this->db = null;
        parent::tearDown();
    }

    /**
     * Load environment variables for testing
     */
    protected function loadEnvironment(): void
    {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }

    /**
     * Get database connection for testing
     */
    protected function getDatabase(): PDO
    {
        if ($this->db === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $dbname = $_ENV['DB_NAME'] ?? 'basturms_db';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $this->db = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }

        return $this->db;
    }

    /**
     * Create a mock object with fluent interface
     */
    protected function mockObject(string $className): object
    {
        return $this->createMock($className);
    }

    /**
     * Assert array has keys
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array should have key: $key");
        }
    }

    /**
     * Assert response structure
     */
    protected function assertResponseStructure(array $response, bool $shouldSucceed = true): void
    {
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertEquals($shouldSucceed, $response['success']);

        if ($shouldSucceed) {
            $this->assertArrayHasKey('message', $response);
        } else {
            $this->assertArrayHasKey('error', $response);
        }
    }

    /**
     * Assert validation error structure
     */
    protected function assertValidationError(array $response, ?string $field = null): void
    {
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('errors', $response);
        
        if ($field !== null) {
            $this->assertArrayHasKey($field, $response['errors']);
        }
    }
}
