<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\View;

class DocumentationController extends Controller
{
    protected View $view;

    public function __construct(View $view) {
        $this->view = $view;
    }

    public function index()
    {
        header('Content-Type: application/json');

        echo json_encode([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'My Framework API',
                'version' => '1.0.0',
                'description' => 'API documentation for my custom PHP framework'
            ],
            'servers' => [
                ['url' => 'http://localhost:8000/api/v1']
            ],
            'paths' => [
                '/students' => [
                    'get' => [
                        'summary' => 'List students',
                        'responses' => [
                            '200' => [
                                'description' => 'Successful response',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'object']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'post' => [
                        'summary' => 'Create a student',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'email' => ['type' => 'string']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Student created']
                        ]
                    ]
                ]
            ]
        ], JSON_PRETTY_PRINT);
    }

    public function docs() {
        return $this->view->render('layouts/docs-ui');
    }
}
