<?php

require_once 'vendor/autoload.php';

use OpenApi\Generator;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Basturms School Management API",
    version: "1.0.0",
    description: "A comprehensive API for managing school operations including students, academics, staff, and administration.",
    contact: new OA\Contact(name: "API Support", email: "support@basturms.com")
)]
#[OA\Server(
    url: "http://localhost:8000/api/v1",
    description: "Development server"
)]
#[OA\SecurityScheme(
    securityScheme: "ApiKeyAuth",
    type: "apiKey",
    name: "X-API-Key",
    in: "header",
    description: "API Key for authentication"
)]
#[OA\SecurityScheme(
    securityScheme: "BearerAuth",
    type: "http",
    scheme: "bearer",
    description: "JWT Bearer token"
)]
class TestDocumentationController {}

#[OA\Tag(
    name: "Academic Management",
    description: "API endpoints for managing academic years, terms, subjects, classes, and scores"
)]
class TestAcademicController {
    #[OA\Post(
        path: "/academic/years/create",
        summary: "Create a new academic year",
        description: "Creates an academic year and automatically creates all terms (Term 1, Term 2, Term 3) with default dates. Term 1 is set to Active, others to Upcoming.",
        tags: ["Academic Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["academic_year", "number_of_terms"],
                properties: [
                    new OA\Property(property: "academic_year", type: "string", example: "2024-2025", description: "Academic year identifier"),
                    new OA\Property(property: "number_of_terms", type: "integer", example: 3, description: "Number of terms (1-3)", minimum: 1, maximum: 3),
                    new OA\Property(property: "status", type: "string", example: "Active", enum: ["Active", "Upcoming", "Completed"], description: "Initial status of the academic year")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Academic year created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Academic year created successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Validation error or business rule violation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Internal server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            )
        ]
    )]
    public function createAcademicYear() {}
}

try {
    // Disable logger to avoid warnings
    $logger = new class implements \Psr\Log\LoggerInterface {
        public function emergency(\Stringable|string $message, array $context = []): void {}
        public function alert(\Stringable|string $message, array $context = []): void {}
        public function critical(\Stringable|string $message, array $context = []): void {}
        public function error(\Stringable|string $message, array $context = []): void {}
        public function warning(\Stringable|string $message, array $context = []): void {}
        public function notice(\Stringable|string $message, array $context = []): void {}
        public function info(\Stringable|string $message, array $context = []): void {}
        public function debug(\Stringable|string $message, array $context = []): void {}
        public function log($level, \Stringable|string $message, array $context = []): void {}
    };

    $openapi = Generator::scan([
        __FILE__, // This file with test classes
    ], [
        'logger' => $logger
    ]);

    $json = $openapi->toJson();

    // Validate JSON
    $decoded = json_decode($json);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON Error: " . json_last_error_msg() . "\n";
        echo "Raw output:\n";
        echo $json;
    } else {
        echo "JSON is valid!\n";
        echo "OpenAPI version: " . ($decoded->openapi ?? 'Not set') . "\n";
        echo "Title: " . ($decoded->info->title ?? 'Not set') . "\n";
        echo "Paths count: " . (isset($decoded->paths) ? count((array)$decoded->paths) : 0) . "\n";

        // Save to file for inspection
        file_put_contents('debug_swagger_output.json', $json);
        echo "JSON saved to debug_swagger_output.json\n";
    }

} catch (Exception $e) {
    echo "Error generating Swagger: " . $e->getMessage() . "\n";
}
