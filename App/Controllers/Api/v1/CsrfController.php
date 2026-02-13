<?php

namespace App\Controllers\Api\v1;

use App\Core\Session;
use App\Core\Response;

class CsrfController
{
    /**
     * Retrieves the current CSRF token.
     *
     * @return Response
     */
    public function token(): Response
    {
        $token = Session::token();
        $response = new Response();
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent((string)json_encode(['csrf_token' => $token]));
        return $response;
    }
}
