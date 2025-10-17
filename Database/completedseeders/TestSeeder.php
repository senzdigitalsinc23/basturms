<?php

use App\Core\Database;

class TestSeeder
{
    public function run()
    {
        $db = Database::getInstance()->getConnection();

        $users = [
            ['Alice', 'alice@example.com', 'secret123'],
            ['Bob', 'bob@example.com', 'password456'],
        ];

        foreach ($users as $user) {
            [$name, $email, $password] = $user;

            echo " → Inserting test: {$name} ({$email})\n";
            $db->prepare("INSERT INTO test (name, email, password) VALUES (?, ?, ?)")
               ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        }
    }
}
