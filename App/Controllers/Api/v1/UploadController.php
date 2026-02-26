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
        description: "Uploads a file (profile picture, signature, or document) and returns metadata. The doc_id is automatically generated as user_id_randomstring.",
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
                        new OA\Property(property: "doc_type", type: "string", description: "Type of document (profile_picture, staff_signature, student_document, staff_document)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Upload successful"),
            new OA\Response(response: 400, description: "Upload failed"),
            new OA\Response(response: 401, description: "User not authenticated")
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
            
            // Get authenticated user's user_id from session
            $user = \App\Core\Session::get('user');
            if (!$user || !isset($user['user_id'])) {
                throw new Exception("User not authenticated.");
            }
            
            $userId = $user['user_id']; // This is the user_id field (e.g., "USR001")
            $userPrimaryId = $user['id']; // This is the primary key id
            
            // If this is a profile picture, delete the old one first
            if ($docType === 'profile_picture') {
                $this->uploadService->deleteOldProfilePicture($userPrimaryId);
            }
            
            // Generate doc_id in format: user_id_randomstring
            $randomString = substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $docId = $userId . '_' . $randomString;
            
            $result = $this->uploadService->upload($files['file'], $docType, $docId);

            // If this is a profile picture, update the user's profile_picture_id
            if ($docType === 'profile_picture' && $result['success']) {
                $this->uploadService->updateUserProfilePicture($userPrimaryId, $docId);
                
                // Update session with new profile picture
                $user['profile_picture_id'] = $docId;
                \App\Core\Session::set('user', $user);
            }

            // If this is a signature, update the staff's signature_id
            if (in_array($docType, ['signature', 'staff_signature']) && $result['success']) {
                $this->uploadService->updateStaffSignature($userId, $docId);
            }

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

    #[OA\Get(
        path: "/uploads/public/{id}",
        summary: "Access a public uploaded file",
        description: "Publicly retrieves and serves an uploaded file from storage without authentication. Only serves profile pictures.",
        tags: ["Uploads"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "File content"),
            new OA\Response(response: 403, description: "File type not allowed for public access"),
            new OA\Response(response: 404, description: "File not found")
        ]
    )]
    public function getPublicFile(Request $request, Response $response, array $params): Response
    {
        try {
            $id = (int) ($params['id'] ?? 0);
            $upload = $this->uploadService->getUpload($id);

            if (!$upload) {
                throw new Exception("File record not found.");
            }

            // Block access to signatures and sensitive documents
            $blockedDocTypes = ['signature', 'staff_signature', 'student_document', 'staff_document'];
            if (in_array($upload['doc_type'], $blockedDocTypes)) {
                $response->setStatusCode(403);
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'This document type requires authentication. Use /api/v1/uploads/file/{id} instead.'
                ]));
                return $response;
            }

            // Only allow public access to image files for security
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($upload['file_type'], $allowedTypes)) {
                $response->setStatusCode(403);
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'This file type is not available for public access.'
                ]));
                return $response;
            }

            $path = $this->uploadService->getPhysicalPath($id);

            if (!$path || !file_exists($path)) {
                throw new Exception("File not found on disk.");
            }

            // Set appropriate headers for file delivery
            $response->setHeader('Content-Type', $upload['file_type']);
            $response->setHeader('Content-Length', (string) $upload['file_size']);
            $response->setHeader('Content-Disposition', 'inline; filename="' . $upload['doc_name'] . '"');
            $response->setHeader('Cache-Control', 'public, max-age=31536000'); // Cache for 1 year
            
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

    #[OA\Get(
        path: "/uploads/secure/{id}",
        summary: "Access a secure uploaded file with session authentication",
        description: "Retrieves and serves an uploaded file using session-based authentication. Works in browser with cookies.",
        tags: ["Uploads"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "File content"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "File not found")
        ]
    )]
    public function getSecureFile(Request $request, Response $response, array $params): Response
    {
        try {
            // Check if user is authenticated via session
            $user = \App\Core\Session::get('user');
            if (!$user) {
                $response->setStatusCode(401);
                $response->setContent(json_encode([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ]));
                return $response;
            }

            $id = (int) ($params['id'] ?? 0);
            $upload = $this->uploadService->getUpload($id);

            if (!$upload) {
                throw new Exception("File record not found.");
            }

            $path = $this->uploadService->getPhysicalPath($id);

            if (!$path || !file_exists($path)) {
                throw new Exception("File not found on disk.");
            }

            // Set appropriate headers for file delivery
            $response->setHeader('Content-Type', $upload['file_type']);
            $response->setHeader('Content-Length', (string) $upload['file_size']);
            $response->setHeader('Content-Disposition', 'inline; filename="' . $upload['doc_name'] . '"');
            $response->setHeader('Cache-Control', 'private, max-age=3600'); // Cache for 1 hour
            
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
