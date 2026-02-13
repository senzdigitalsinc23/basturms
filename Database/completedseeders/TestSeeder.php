<?php

namespace Database\Seeders;

use Database\Seeder;

class TestSeeder extends Seeder
{
    public function run()
    {
        $users = [
            ['Alice', 'alice@example.com', 'secret123'],
            ['Bob', 'bob@example.com', 'password456'],
        ];

        foreach ($users as $user) {
            [$name, $email, $password] = $user;

            echo " → Inserting test: {$name} ({$email})\n";
            $this->execute("INSERT INTO test (name, email, password) VALUES (?, ?, ?)",
               [$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        }
    }
}
