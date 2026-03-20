<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\DocumentService;
use App\Services\AuthService;
use App\Services\LoggingService;
use App\Exceptions\ValidationException;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Document Management",
    description: "API endpoints for managing staff and student documents with categorization and verification"
)]
class DocumentController
{
    private DocumentService $documentService;
    private AuthService $authService;
    private LoggingService $logger;

    public function __construct(
        DocumentService $documentService,
        AuthService $authService,
        LoggingService $logger
    ) {
        $this->documentService = $documentService;
        $this->authService = $authService;
        $this->logger = $logger;
    }

    #[OA\Post(
        path: "/api/v1/documents/upload",
        summary: "Upload a document",
        description: "Upload a document for staff or student with category and metadata",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file", "entity_type", "entity_id", "category_id"],
                    properties: [
                        new OA\Property(
                            property: "file",
                            type: "string",
                            format: "binary",
                            description: "The document file to upload"
                        ),
                        new OA\Property(
                            property: "entity_type",
                            type: "string",
                            enum: ["staff", "student"],
                            example: "staff",
                            description: "Type of entity (staff or student)"
                        ),
                        new OA\Property(
                            property: "entity_id",
                            type: "string",
                            example: "LBAST26001",
                            description: "Staff ID or Student Number"
                        ),
                        new OA\Property(
                            property: "category_id",
                            type: "integer",
                            example: 1,
                            description: "Document category ID"
                        ),
                        new OA\Property(
                            property: "description",
                            type: "string",
                            example: "Employment contract for 2026",
                            description: "Optional document description"
                        ),
                        new OA\Property(
                            property: "expiry_date",
                            type: "string",
                            format: "date",
                            example: "2027-12-31",
                            description: "Optional expiry date for the document"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Document uploaded successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Document uploaded successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "document_id", type: "string", example: "DOC2602001"),
                                new OA\Property(property: "upload_id", type: "integer", example: 123),
                                new OA\Property(property: "url", type: "string", example: "http://localhost:8000/api/v1/uploads/file/123"),
                                new OA\Property(property: "doc_name", type: "string", example: "contract.pdf")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
    public function upload(Request $request, Response $response): Response
    {
        try {
            $files = $request->getFiles();
            $data = $request->getPost();

            if (empty($files['file'])) {
                throw new Exception("No file uploaded");
            }

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $uploadedBy = $currentUser ? $currentUser->userId : 'system';

            // Prepare metadata
            $metadata = [
                'entity_type' => $data['entity_type'] ?? '',
                'entity_id' => $data['entity_id'] ?? '',
                'category_id' => (int)($data['category_id'] ?? 0),
                'description' => $data['description'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'uploaded_by' => $uploadedBy
            ];

            $result = $this->documentService->uploadDocument($files['file'], $metadata);

            $response->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $result
            ], 201);
            return $response;

        } catch (ValidationException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 422);
            return $response;

        } catch (Exception $e) {
            $this->logger->logAudit(
                'document_upload_error',
                'Document upload failed: ' . $e->getMessage(),
                $metadata['uploaded_by'] ?? 'unknown'
            );

            $response->json([
                'success' => false,
                'message' => 'An error occurred during document upload',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Get(
        path: "/api/v1/documents/{entity_type}/{entity_id}",
        summary: "Get documents for an entity",
        description: "Retrieve all documents for a specific staff member or student",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "entity_type",
                in: "path",
                required: true,
                description: "Entity type (staff or student)",
                schema: new OA\Schema(type: "string", enum: ["staff", "student"]),
                example: "staff"
            ),
            new OA\Parameter(
                name: "entity_id",
                in: "path",
                required: true,
                description: "Staff ID or Student Number",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            ),
            new OA\Parameter(
                name: "category_id",
                in: "query",
                required: false,
                description: "Filter by category ID",
                schema: new OA\Schema(type: "integer"),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Documents retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Documents retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "document_id", type: "string"),
                                    new OA\Property(property: "document_name", type: "string"),
                                    new OA\Property(property: "category_name", type: "string"),
                                    new OA\Property(property: "file_path", type: "string"),
                                    new OA\Property(property: "file_size", type: "integer"),
                                    new OA\Property(property: "upload_date", type: "string"),
                                    new OA\Property(property: "is_verified", type: "boolean"),
                                    new OA\Property(property: "expiry_date", type: "string")
                                ]
                            )
                        ),
                        new OA\Property(property: "count", type: "integer", example: 5)
                    ]
                )
            )
        ]
    )]
    public function getDocuments(Request $request, Response $response, array $params): Response
    {

        try {
            //$entityType = $params['entity_type'] ?? '';
            $entityId = $request->getQuery('entity_id') ?? '';

            $documents = $this->documentService->getEntityDocuments($entityId);

            $response->json([
                'success' => true,
                'message' => 'Documents retrieved successfully',
                'data' => $documents,
                'count' => count($documents)
            ]);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while retrieving documents',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Get(
        path: "/api/v1/documents/detail/{document_id}",
        summary: "Get document details",
        description: "Retrieve detailed information about a specific document",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "document_id",
                in: "path",
                required: true,
                description: "Document ID",
                schema: new OA\Schema(type: "string"),
                example: "DOC2602001"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Document details retrieved successfully"
            ),
            new OA\Response(
                response: 404,
                description: "Document not found"
            )
        ]
    )]
    public function getDocument(Request $request, Response $response, array $params): Response
    {
        try {
            $documentId = $params['document_id'] ?? '';

            $document = $this->documentService->getDocumentById($documentId);

            if (!$document) {
                $response->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
                return $response;
            }

            $response->json([
                'success' => true,
                'message' => 'Document retrieved successfully',
                'data' => $document
            ]);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while retrieving document',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Put(
        path: "/api/v1/documents/{document_id}/verify",
        summary: "Verify a document",
        description: "Mark a document as verified by an authorized user",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "document_id",
                in: "path",
                required: true,
                description: "Document ID",
                schema: new OA\Schema(type: "string"),
                example: "DOC2602001"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Document verified successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Document verified successfully")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Document not found"
            )
        ]
    )]
    public function verifyDocument(Request $request, Response $response, array $params): Response
    {
        try {
            $documentId = $params['document_id'] ?? '';

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $verifiedBy = $currentUser ? $currentUser->userId : 'system';

            $result = $this->documentService->verifyDocument($documentId, $verifiedBy);

            $response->json($result);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while verifying document',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Delete(
        path: "/api/v1/documents/{document_id}",
        summary: "Delete a document",
        description: "Soft delete a document (marks as deleted, doesn't remove file)",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "document_id",
                in: "path",
                required: true,
                description: "Document ID",
                schema: new OA\Schema(type: "string"),
                example: "DOC2602001"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Document deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Document deleted successfully")
                    ]
                )
            )
        ]
    )]
    public function deleteDocument(Request $request, Response $response): Response
    {
        try {
            $documentId = $request->getQuery('document_id') ?? '';

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $deletedBy = $currentUser ? $currentUser->userId : 'system';

            $result = $this->documentService->deleteDocument($documentId, $deletedBy);

            $response->json($result);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while deleting document',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Delete(
        path: "/api/v1/documents/{document_id}/permanent",
        summary: "Permanently delete a document",
        description: "Permanently delete a document from database (cannot be undone)",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "document_id",
                in: "path",
                required: true,
                description: "Document ID",
                schema: new OA\Schema(type: "string"),
                example: "DOC2602001"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Document permanently deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Document permanently deleted successfully")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Document not found"
            )
        ]
    )]
    public function permanentlyDeleteDocument(Request $request, Response $response, array $params): Response
    {
        try {
            $documentId = $params['document_id'] ?? '';

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $deletedBy = $currentUser ? $currentUser->userId : 'system';

            $result = $this->documentService->permanentlyDeleteDocument($documentId, $deletedBy);

            $response->json($result);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while permanently deleting document',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }


    #[OA\Get(
        path: "/api/v1/documents/categories",
        summary: "Get document categories",
        description: "Retrieve all available document categories, optionally filtered by entity type",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "entity_type",
                in: "query",
                required: false,
                description: "Filter by entity type (staff or student)",
                schema: new OA\Schema(type: "string", enum: ["staff", "student"]),
                example: "staff"
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Categories retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Categories retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "category_name", type: "string"),
                                    new OA\Property(property: "category_code", type: "string"),
                                    new OA\Property(property: "entity_type", type: "string"),
                                    new OA\Property(property: "description", type: "string")
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function getCategories(Request $request, Response $response): Response
    {
        try {
            $entityType = $request->getQuery('entity_type');

            $categories = $this->documentService->getCategories($entityType);

            $response->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ]);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while retrieving categories',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Post(
        path: "/api/v1/documents/categories/create",
        summary: "Create a new document category",
        description: "Create a new category for organizing documents",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_name", "category_code", "entity_type"],
                properties: [
                    new OA\Property(property: "category_name", type: "string", example: "Contracts", description: "Category name"),
                    new OA\Property(property: "category_code", type: "string", example: "CONTRACTS", description: "Unique category code (uppercase, underscores only)"),
                    new OA\Property(property: "entity_type", type: "string", enum: ["staff", "student", "both"], example: "both", description: "Entity type"),
                    new OA\Property(property: "description", type: "string", example: "Contract documents", description: "Optional description")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Category created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Category created successfully"),
                        new OA\Property(property: "category_id", type: "integer", example: 23)
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function createCategory(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $data['created_by'] = $currentUser ? $currentUser->userId : 'system';

            $result = $this->documentService->createCategory($data);

            $response->json($result, 201);
            return $response;

        } catch (ValidationException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 422);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while creating category',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Put(
        path: "/api/v1/documents/categories/{category_id}",
        summary: "Update a document category",
        description: "Update an existing document category",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "category_id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_name", "category_code", "entity_type"],
                properties: [
                    new OA\Property(property: "category_name", type: "string", example: "Contracts"),
                    new OA\Property(property: "category_code", type: "string", example: "CONTRACTS"),
                    new OA\Property(property: "entity_type", type: "string", enum: ["staff", "student", "both"], example: "both"),
                    new OA\Property(property: "description", type: "string", example: "Contract documents")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Category updated successfully"),
            new OA\Response(response: 404, description: "Category not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function updateCategory(Request $request, Response $response, array $params): Response
    {
        try {
            $categoryId = (int)($params['category_id'] ?? 0);
            $data = $request->getPost();

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $data['updated_by'] = $currentUser ? $currentUser->userId : 'system';

            $result = $this->documentService->updateCategory($categoryId, $data);

            $response->json($result);
            return $response;

        } catch (ValidationException $e) {
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 422);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while updating category',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Delete(
        path: "/api/v1/documents/categories/{category_id}",
        summary: "Delete a document category",
        description: "Soft delete a document category (marks as inactive)",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "category_id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Category deleted successfully"),
            new OA\Response(response: 404, description: "Category not found"),
            new OA\Response(response: 400, description: "Category is in use")
        ]
    )]
    public function deleteCategory(Request $request, Response $response, array $params): Response
    {
        try {
            $categoryId = (int)($params['category_id'] ?? 0);

            // Get current user
            $currentUser = $this->authService->getCurrentUser();
            $deletedBy = $currentUser ? $currentUser->userId : 'system';

            $result = $this->documentService->deleteCategory($categoryId, $deletedBy);

            $response->json($result);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while deleting category',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Get(
        path: "/api/v1/documents/categories/all",
        summary: "Get all categories including inactive",
        description: "Retrieve all document categories including inactive ones",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "entity_type", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["staff", "student"]))
        ],
        responses: [
            new OA\Response(response: 200, description: "Categories retrieved successfully")
        ]
    )]
    public function getAllCategoriesIncludingInactive(Request $request, Response $response): Response
    {
        try {
            $entityType = $request->getQuery('entity_type');

            $categories = $this->documentService->getAllCategoriesIncludingInactive($entityType);

            $response->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ]);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while retrieving categories',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }

    #[OA\Get(
        path: "/api/v1/documents/staff/{staff_id}",
        summary: "Get all documents for a specific staff member",
        description: "Retrieve all documents associated with a specific staff member by their staff ID",
        tags: ["Document Management"],
        security: [["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "staff_id",
                in: "path",
                required: true,
                description: "Staff ID",
                schema: new OA\Schema(type: "string"),
                example: "LBAST26001"
            ),
            new OA\Parameter(
                name: "category_id",
                in: "query",
                required: false,
                description: "Filter by category ID",
                schema: new OA\Schema(type: "integer"),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Staff documents retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Staff documents retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "document_id", type: "string"),
                                    new OA\Property(property: "document_name", type: "string"),
                                    new OA\Property(property: "category_name", type: "string"),
                                    new OA\Property(property: "file_path", type: "string"),
                                    new OA\Property(property: "file_size", type: "integer"),
                                    new OA\Property(property: "upload_date", type: "string"),
                                    new OA\Property(property: "is_verified", type: "boolean"),
                                    new OA\Property(property: "expiry_date", type: "string")
                                ]
                            )
                        ),
                        new OA\Property(property: "count", type: "integer", example: 5)
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Staff not found")
        ]
    )]
    public function getStaffDocuments(Request $request, Response $response, array $params): Response
    {
        try {
            $staffId = $params['staff_id'] ?? '';

            $documents = $this->documentService->getEntityDocuments($staffId);

            $response->json([
                'success' => true,
                'message' => 'Staff documents retrieved successfully',
                'data' => $documents,
                'count' => count($documents)
            ]);
            return $response;

        } catch (Exception $e) {
            $response->json([
                'success' => false,
                'message' => 'An error occurred while retrieving staff documents',
                'error' => $e->getMessage()
            ], 500);
            return $response;
        }
    }
}
