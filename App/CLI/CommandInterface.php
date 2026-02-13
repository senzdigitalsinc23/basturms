<?php
namespace App\CLI;

interface CommandInterface
{
    public function handle(array $args): void;
}
