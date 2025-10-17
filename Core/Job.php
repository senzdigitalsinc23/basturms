<?php
namespace App\Core;

interface Job
{
    public function handle(): void;
}
