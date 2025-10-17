<?php

namespace App\Core;

interface MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next): Response;
}
