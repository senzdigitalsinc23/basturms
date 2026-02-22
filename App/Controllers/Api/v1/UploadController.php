<?php

namespace App\Controllers\Api\v1;

use App\Core\Request;
use App\Core\Response;
use App\Services\UploadService;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Uploads",
    description: "API endpoints for file uploads and management"
)]
class UploadController
{
    private UploadService $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    #[OA\Post(
        path: "/uploads",
        summary: "Upload a file",
        description: "Uploads a file (profile picture, signature, or document) and returns metadata.",
        tags: ["Uploads"],
        security: [["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["file", "doc_type"],
                    properties: [
                        new OA\Property(property: "file", type: "string", format: "binary", description: "The file to upload"),
                        new OA\Property(property: "doc_type", type: "string", description: "Type of document (profile_picture, staff_signature, student_document, staff_document)"),
                        new OA\Property(property: "doc_id", type: "string", description: "ID of the student or staff member (e.g., WR-TK001-LBA02001)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Upload successful"),
            new OA\Response(response: 400, description: "Upload failed")
        ]
    )]
    public function upload(Request $request, Response $response): Response
    {
        try {
            $files = $request->getFiles();
            $data = $request->getPost();

            if (empty($files['file'])) {
                throw new Exception("No file uploaded.");
            }

            $docType = $data['doc_type'] ?? '';
            $docId = $data['doc_id'] ?? null;
            
            $result = $this->uploadService->upload($files['file'], $docType, $docId);

            $response->setContent(json_encode($result));
            return $response;
        } catch (Exception $e) {
            $response->setStatusCode(400);
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        }
    }

    #[OA\Get(
        path: "/uploads/file/{id}",
        summary: "Access an uploaded file",
        description: "Securely retrieves and serves an uploaded file from storage.",
        tags: ["Uploads"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "File content"),
            new OA\Response(response: 404, description: "File not found")
        ]
    )]
    public function getFile(Request $request, Response $response, array $params): Response
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $upload = $this->uploadService->getUpload($id);

            if (!$upload) {
                throw new Exception("File record not found.");
            }

            $path = $this->uploadService->getPhysicalPath($id);

            if (!$path || !file_exists($path)) {
                throw new Exception("File not found on disk.");
            }

            // Professional enhancement: Set appropriate headers for file delivery
            $response->setHeader('Content-Type', $upload['file_type']);
            $response->setHeader('Content-Length', (string) $upload['file_size']);
            $response->setHeader('Content-Disposition', 'inline; filename="' . $upload['doc_name'] . '"');
            
            $content = file_get_contents($path);
            $response->setContent($content);
            
            return $response;
        } catch (Exception $e) {
            $response->setStatusCode(404);
            $response->setContent(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response;
        }
    }
}
