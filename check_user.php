<?php
// Check user authentication status
require_once 'vendor/autoload.php';

if (file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT id, email, password, status FROM users WHERE email = ?');
    $stmt->execute(['senzu.dogi23@gmail.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "User found:\n";
        echo "ID: {$user['id']}\n";
        echo "Email: {$user['email']}\n";
        echo "Status: {$user['status']}\n";
        echo "Password: " . ($user['password'] ? 'SET (' . substr($user['password'], 0, 20) . '...)' : 'NULL') . "\n";

        // Test password verification
        $testPassword = '123456';
        if ($user['password']) {
            $verified = password_verify($testPassword, $user['password']);
            echo "Password verification (123456): " . ($verified ? 'SUCCESS' : 'FAILED') . "\n";
        } else {
            echo "Password is null - cannot verify\n";
        }
    } else {
        echo "User not found in database\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
