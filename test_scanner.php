<<<<<<< Current (Your changes)
=======
<?php

require_once 'vendor/autoload.php';
use OpenApi\Generator;
use OpenApi\Attributes as OA;

#[OA\Info(title: 'Test', version: '1.0.0')]
#[OA\Get(path: '/test', responses: [new OA\Response(response: 200, description: 'OK')])]
class TestController {
    public function test() {}
}

try {
    $openapi = Generator::scan([__FILE__]);
    $json = $openapi->toJson();
    $decoded = json_decode($json);
    echo 'Paths found: ' . count((array)$decoded->paths) . PHP_EOL;
    echo 'Paths: ' . implode(', ', array_keys((array)$decoded->paths)) . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
>>>>>>> Incoming (Background Agent changes)
