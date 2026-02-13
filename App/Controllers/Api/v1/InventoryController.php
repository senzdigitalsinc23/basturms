<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;

class InventoryController
{
    /**
     * Lists inventory operations.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        $response->setContent((string)json_encode(['message' => 'InventoryController index method']));
        $response->setStatusCode(200);
        return $response;
    }
}
