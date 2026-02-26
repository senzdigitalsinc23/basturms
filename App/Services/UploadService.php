<?php

namespace App\Services;

use App\Repositories\UploadRepository;
use Exception;

class UploadService
{
    private UploadRepository $repo;
    private string $storagePath;

    private const ALLOWED_DOC_TYPES = [
        'profile_picture',
        'signature',
        'staff_signature',
        'student_document',
        'staff_document'
    ];

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    public function __construct()
    {
        $this->repo = new UploadRepository();
        $this->storagePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
    }

    /**
     * Handle the file upload process.
     *
     * @param array $fileData The $_FILES['field'] array
     * @param string $docType Category of the document
     * @return array Result with success status and metadata or error message
     * @throws Exception
     */
    public function upload(array $fileData, string $docType, ?string $docId = null): array
    {
        // 1. Basic Validation
        if (!in_array($docType, self::ALLOWED_DOC_TYPES)) {
            throw new Exception("Invalid document type: $docType");
        }

        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error code: " . $fileData['error']);
        }

        if ($fileData['size'] > self::MAX_FILE_SIZE) {
            throw new Exception("File size too large. Max allowed is 5MB.");
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileData['tmp_name']);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($fileData['tmp_name']);
        } else {
            // Manual fallback based on name
            $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            $mapping = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            $mimeType = $mapping[$extension] ?? 'application/octet-stream';
        }

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new Exception("Invalid file type: $mimeType. Allowed: JPG, PNG, PDF, DOC, DOCX.");
        }

        // 2. Prepare Directory
        $subDir = $docType . 's'; // e.g., profile_pictures
        $targetDir = $this->storagePath . DIRECTORY_SEPARATOR . $subDir;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // 3. Generate Descriptive & Unique Filename
        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        if (empty($extension)) {
            // fallback for mime types
            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
            ];
            $extension = $extensions[$mimeType] ?? 'bin';
        }

        // Format: {doc_id}_{doc_type}_{unique_part}.{ext}
        // If doc_id is missing, use a random string
        $prefix = !empty($docId) ? $docId . '_' . $docType : $docType;
        $uniqueName = $prefix . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extension;
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $uniqueName;

        // 4. Move File
        if (!$this->moveFile($fileData['tmp_name'], $targetFile)) {
            throw new Exception("Failed to move uploaded file.");
        }

        // 5. Log in DB
        // Generate full web URL for frontend access via the file serving endpoint
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $appUrl = rtrim($appUrl, '/'); // Remove trailing slash if present
        
        // Store relative path in database for file system access
        $relativeUrl = "uploads/$subDir/$uniqueName";

        $logData = [
            'doc_id' => $docId,
            'doc_name' => basename($fileData['name']),
            'doc_type' => $docType,
            'url' => $relativeUrl, // Store relative path for file system
            'file_type' => $mimeType,
            'file_size' => $fileData['size']
        ];

        $uploadId = $this->repo->logUpload($logData);

        if (!$uploadId) {
            // Cleanup file if DB failed
            unlink($targetFile);
            throw new Exception("Failed to log upload in database.");
        }

        // Return full URL pointing to the file serving endpoint
        // Use public endpoint for profile pictures (no auth required)
        // Use secure session-based endpoint for signatures (works in browser with cookies)
        // Use authenticated API endpoint for documents (requires Bearer token)
        if ($docType === 'profile_picture') {
            $fullUrl = $appUrl . '/api/v1/uploads/public/' . $uploadId;
        } elseif (in_array($docType, ['signature', 'staff_signature'])) {
            $fullUrl = $appUrl . '/api/v1/uploads/secure/' . $uploadId;
        } else {
            $fullUrl = $appUrl . '/api/v1/uploads/file/' . $uploadId;
        }

        return [
            'success' => true,
            'upload_id' => $uploadId,
            'url' => $fullUrl,
            'doc_name' => $logData['doc_name']
        ];
    }

    /**
     * Move the file to its final destination.
     * Uses move_uploaded_file for web uploads and rename for CLI/tests.
     */
    protected function moveFile(string $source, string $destination): bool
    {
        if (is_uploaded_file($source)) {
            return move_uploaded_file($source, $destination);
        }
        return rename($source, $destination);
    }

    /**
     * Get upload metadata.
     */
    public function getUpload(int $id): ?array
    {
        return $this->repo->getById($id);
    }

    /**
     * Get full physical path of a file.
     */
    public function getPhysicalPath(int $id): ?string
    {
        $upload = $this->getUpload($id);
        if (!$upload) return null;

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $upload['url']);
    }

    /**
     * Update user's profile picture reference
     *
     * @param int $userId User's primary key ID
     * @param string $docId The doc_id from uploads table
     * @return bool Success status
     */
    public function updateUserProfilePicture(int $userId, string $docId): bool
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                UPDATE users 
                SET profile_picture_id = ? 
                WHERE id = ?
            ");
            
            return $stmt->execute([$docId, $userId]);
        } catch (\Exception $e) {
            error_log("Failed to update user profile picture: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete old profile picture for a user
     * Removes both the file from disk and the database record
     *
     * @param int $userId User's primary key ID
     * @return bool Success status
     */
    public function deleteOldProfilePicture(int $userId): bool
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            // Get the current profile picture doc_id
            $stmt = $db->prepare("SELECT profile_picture_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$user || empty($user['profile_picture_id'])) {
                // No existing profile picture to delete
                return true;
            }
            
            $oldDocId = $user['profile_picture_id'];
            
            // Get the upload record
            $stmt = $db->prepare("SELECT id, url FROM uploads WHERE doc_id = ?");
            $stmt->execute([$oldDocId]);
            $upload = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($upload) {
                // Delete the physical file
                $filePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $upload['url']);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Delete the database record
                $stmt = $db->prepare("DELETE FROM uploads WHERE id = ?");
                $stmt->execute([$upload['id']]);
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Failed to delete old profile picture: " . $e->getMessage());
            // Don't fail the upload if deletion fails
            return false;
        }
    }

    /**
     * Update staff's signature reference
     *
     * @param string $userId User's user_id (staff_id)
     * @param string $docId The doc_id from uploads table
     * @return bool Success status
     */
    public function updateStaffSignature(string $userId, string $docId): bool
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                UPDATE staff 
                SET signature_id = ? 
                WHERE staff_id = ?
            ");
            
            return $stmt->execute([$docId, $userId]);
        } catch (\Exception $e) {
            error_log("Failed to update staff signature: " . $e->getMessage());
            return false;
        }
    }
}
