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
        // Use relative URL for public access/retrieval
        $url = "uploads/$subDir/$uniqueName";

        $logData = [
            'doc_id' => $docId,
            'doc_name' => basename($fileData['name']),
            'doc_type' => $docType,
            'url' => $url,
            'file_type' => $mimeType,
            'file_size' => $fileData['size']
        ];

        $uploadId = $this->repo->logUpload($logData);

        if (!$uploadId) {
            // Cleanup file if DB failed
            unlink($targetFile);
            throw new Exception("Failed to log upload in database.");
        }

        return [
            'success' => true,
            'upload_id' => $uploadId,
            'url' => $url,
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
}
