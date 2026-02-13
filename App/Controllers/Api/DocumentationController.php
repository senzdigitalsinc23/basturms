<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\View;
use App\Core\Response; // Import the Response class
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Basturms School Management API",
    version: "1.0.0",
    description: "A comprehensive API for managing school operations (Academics, Students, Admissions)."
)]
#[OA\Server(
    url: "http://localhost:8000/api/v1",
    description: "Development server"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    in: "header",
    name: "X-API-Key",
    description: "API Key for authentication"
)]
class DocumentationController extends Controller
{
    protected View $view;

    /**
     * DocumentationController constructor.
     *
     * @param View $view View engine for rendering documentation UI if enabled
     */
    public function __construct(View $view) {
        $this->view = $view;
    }

    /**
     * Returns the Swagger OpenAPI JSON.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function index(\App\Core\Request $request, Response $response): Response
    {
        try {
            $baseDir = dirname(__DIR__, 3);
            $openapi = \OpenApi\Generator::scan([
                realpath($baseDir . '/App/Controllers')
            ]);
            
            $response->setContent($openapi->toJson());
            $response->setStatusCode(200);
            $response->setHeader('Content-Type', 'application/json');
            return $response;
        } catch (\Throwable $e) {
            $response->setStatusCode(500);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent((string)json_encode([
                'error' => 'API Documentation Generation Failed',
                'message' => $e->getMessage()
            ]));
            return $response;
        }
    }

    /**
     * Returns the Swagger UI.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function docs(\App\Core\Request $request, Response $response): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Basturms API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" >
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"> </script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"> </script>
    <script>
    window.onload = function() {
      const ui = SwaggerUIBundle({
        url: "/api/v1/swagger",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "StandaloneLayout"
      })
      window.ui = ui
    }
  </script>
</body>
</html>
HTML;
        $response->setContent($html);
        $response->setStatusCode(200);
        $response->setHeader('Content-Type', 'text/html');
        return $response;
    }
}

