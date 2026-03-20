<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\DocumentRepository;
use App\Services\UploadService;
use App\Services\LoggingService;
use App\Exceptions\ValidationException;
use Exception;

class DocumentService
{
    private Database $database;
    private DocumentRepository $documentRepository;
    private UploadService $uploadService;
    private LoggingService $logger;

    public function __construct(
        DocumentRepository $documentRepository,
        UploadService $uploadService,
        LoggingService $logger
    ) {
        $this->database = Database::getInstance();
        $this->documentRepository = $documentRepository;
        $this->uploadService = $uploadService;
        $this->logger = $logger;
    }

    /**
     * Upload a document with categorization
     * 
     * @param array $fileData File upload data from $_FILES
     * @param array $metadata Document metadata (entity_type, entity_id, category_id, etc.)
     * @return array Upload result with document_id and upload_id
     */
    public function uploadDocument(array $fileData, array $metadata): array
    {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // Validate metadata
            $this->validateMetadata($metadata);

            // Verify category exists and matches entity type
            $category = $this->documentRepository->getCategoryById($metadata['category_id']);

            //echo json_encode($category);exit;
            if (!$category) {
                throw new ValidationException(
                    ['category_id' => ['Invalid category']],
                    'Category not found'
                );
            }

            if ($category['entity_type'] !== 'both' && $category['entity_type'] !== $metadata['entity_type']) {
                throw new ValidationException(
                    ['category_id' => ['Category type mismatch']],
                    "Category '{$category['category_name']}' is for {$category['entity_type']} only. You are uploading for {$metadata['entity_type']}. Please use a category with entity_type '{$metadata['entity_type']}' or 'both'."
                );
            }

            // Determine doc_type for UploadService based on entity_type
            $docType = $metadata['entity_type'] === 'staff' ? 'staff_document' : 'student_document';
            
            // Generate doc_id for UploadService (entity_id_random)
            $uploadDocId = $metadata['entity_id'] . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
            
            // Upload file using centralized UploadService
            $uploadResult = $this->uploadService->upload($fileData, $docType, $uploadDocId);

            if (!$uploadResult['success']) {
                throw new Exception("File upload failed");
            }

            // Generate document ID for tracking
            $documentId = $this->documentRepository->generateDocumentId();

            // Get file extension
            $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);

            // File URL is already token-based from UploadService (secure and non-guessable)
            $fileUrl = $uploadResult['url'];

            // Create document record with metadata
            $documentData = [
                'document_id' => $documentId,
                'entity_type' => $metadata['entity_type'],
                'entity_id' => $metadata['entity_id'],
                'category_id' => $metadata['category_id'],
                'document_name' => $category['category_name'] . ' - ' . $metadata['entity_id'],
                'original_filename' => $fileData['name'],
                'file_path' => $fileUrl,
                'file_size' => $fileData['size'],
                'file_type' => $fileData['type'],
                'file_extension' => $extension,
                'description' => $metadata['description'] ?? null,
                'uploaded_by' => $metadata['uploaded_by'],
                'expiry_date' => $metadata['expiry_date'] ?? null,
                'metadata' => isset($metadata['additional_data']) ? json_encode($metadata['additional_data']) : null
            ];

            if (!$this->documentRepository->createDocument($documentData)) {
                throw new Exception("Failed to create document record");
            }

            // Log the action
            $this->logger->logAudit(
                'document_upload',
                "Document uploaded: {$documentId} for {$metadata['entity_type']} {$metadata['entity_id']}",
                $metadata['uploaded_by']
            );

            $db->commit();

            return [
                'success' => true,
                'document_id' => $documentId,
                'upload_id' => $uploadResult['upload_id'],
                'url' => $uploadResult['url'],
                'doc_name' => $uploadResult['doc_name'],
                'message' => 'Document uploaded successfully'
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get documents for an entity (staff or student)
     */
    public function getEntityDocuments(string $entityId): array {
        return $this->documentRepository->getDocumentsByEntity($entityId);
    }

    /**
     * Get document by ID
     */
    public function getDocumentById(string $documentId): ?array
    {
        return $this->documentRepository->getDocumentById($documentId);
    }

    /**
     * Verify document
     */
    public function verifyDocument(string $documentId, string $verifiedBy): array
    {
        if (!$this->documentRepository->verifyDocument($documentId, $verifiedBy)) {
            throw new Exception("Failed to verify document");
        }

        $this->logger->logAudit(
            'document_verification',
            "Document verified: {$documentId}",
            $verifiedBy
        );

        return [
            'success' => true,
            'message' => 'Document verified successfully'
        ];
    }

    /**
     * Delete document (soft delete)
     */
    public function deleteDocument(string $documentId, string $deletedBy): array
    {
        if (!$this->documentRepository->deleteDocument($documentId)) {
            throw new Exception("Failed to delete document");
        }

        $this->logger->logAudit(
            'document_deletion',
            "Document deleted: {$documentId}",
            $deletedBy
        );

        return [
            'success' => true,
            'message' => 'Document deleted successfully'
        ];
    }

    /**
     * Permanently delete document (hard delete)
     */
    public function permanentlyDeleteDocument(string $documentId, string $deletedBy): array
    {
        // Get document details before deletion
        $document = $this->documentRepository->getDocumentById($documentId);
        
        if (!$document) {
            throw new Exception("Document not found");
        }

        // Delete from database
        if (!$this->documentRepository->permanentlyDeleteDocument($documentId)) {
            throw new Exception("Failed to permanently delete document");
        }

        $this->logger->logAudit(
            'document_permanent_deletion',
            "Document permanently deleted: {$documentId}",
            $deletedBy
        );

        return [
            'success' => true,
            'message' => 'Document permanently deleted successfully'
        ];
    }

    /**
     * Get all document categories
     */
    public function getCategories(?string $entityType = null): array
    {
        return $this->documentRepository->getAllCategories($entityType);
    }

    /**
     * Count documents for an entity
     */
    public function countEntityDocuments(string $entityType, string $entityId): int
    {
        return $this->documentRepository->countDocumentsByEntity($entityType, $entityId);
    }

    /**
     * Validate metadata
     */
    private function validateMetadata(array $metadata): void
    {
        $required = ['entity_type', 'entity_id', 'category_id', 'uploaded_by'];
        
        foreach ($required as $field) {
            if (!isset($metadata[$field]) || empty($metadata[$field])) {
                throw new ValidationException(
                    [$field => ['Field is required']],
                    "Missing required field: {$field}"
                );
            }
        }

        // Validate entity_type
        if (!in_array($metadata['entity_type'], ['staff', 'student'])) {
            throw new ValidationException(
                ['entity_type' => ['Invalid entity type']],
                'Entity type must be either staff or student'
            );
        }
    }

    /**
     * Create a new document category
     */
    public function createCategory(array $data): array
    {
        // Validate category data
        $this->validateCategoryData($data);

        // Check if category code already exists
        if ($this->documentRepository->categoryCodeExists($data['category_code'])) {
            throw new ValidationException(
                ['category_code' => ['Category code already exists']],
                'Category code must be unique'
            );
        }

        $categoryId = $this->documentRepository->createCategory($data);

        $this->logger->logAudit(
            'category_created',
            "Category created: {$data['category_name']} ({$data['category_code']})",
            $data['created_by'] ?? 'system'
        );

        return [
            'success' => true,
            'category_id' => $categoryId,
            'message' => 'Category created successfully'
        ];
    }

    /**
     * Update a document category
     */
    public function updateCategory(int $categoryId, array $data): array
    {
        // Check if category exists
        $category = $this->documentRepository->getCategoryById($categoryId);
        if (!$category) {
            throw new Exception("Category not found");
        }

        // Validate category data
        $this->validateCategoryData($data);

        // Check if category code already exists (excluding current category)
        if ($this->documentRepository->categoryCodeExists($data['category_code'], $categoryId)) {
            throw new ValidationException(
                ['category_code' => ['Category code already exists']],
                'Category code must be unique'
            );
        }

        if (!$this->documentRepository->updateCategory($categoryId, $data)) {
            throw new Exception("Failed to update category");
        }

        $this->logger->logAudit(
            'category_updated',
            "Category updated: {$data['category_name']} (ID: {$categoryId})",
            $data['updated_by'] ?? 'system'
        );

        return [
            'success' => true,
            'message' => 'Category updated successfully'
        ];
    }

    /**
     * Delete a document category (soft delete)
     */
    public function deleteCategory(int $categoryId, string $deletedBy): array
    {
        // Check if category exists
        $category = $this->documentRepository->getCategoryById($categoryId);
        if (!$category) {
            throw new Exception("Category not found");
        }

        // Check if category is in use
        if ($this->documentRepository->isCategoryInUse($categoryId)) {
            throw new Exception("Cannot delete category that is in use by documents");
        }

        if (!$this->documentRepository->deleteCategory($categoryId)) {
            throw new Exception("Failed to delete category");
        }

        $this->logger->logAudit(
            'category_deleted',
            "Category deleted: {$category['category_name']} (ID: {$categoryId})",
            $deletedBy
        );

        return [
            'success' => true,
            'message' => 'Category deleted successfully'
        ];
    }

    /**
     * Permanently delete a document category
     */
    public function permanentlyDeleteCategory(int $categoryId, string $deletedBy): array
    {
        // Check if category exists
        $category = $this->documentRepository->getCategoryById($categoryId);
        if (!$category) {
            throw new Exception("Category not found");
        }

        // Check if category is in use
        if ($this->documentRepository->isCategoryInUse($categoryId)) {
            throw new Exception("Cannot permanently delete category that is in use by documents");
        }

        if (!$this->documentRepository->permanentlyDeleteCategory($categoryId)) {
            throw new Exception("Failed to permanently delete category");
        }

        $this->logger->logAudit(
            'category_permanently_deleted',
            "Category permanently deleted: {$category['category_name']} (ID: {$categoryId})",
            $deletedBy
        );

        return [
            'success' => true,
            'message' => 'Category permanently deleted successfully'
        ];
    }

    /**
     * Restore a soft-deleted category
     */
    public function restoreCategory(int $categoryId, string $restoredBy): array
    {
        if (!$this->documentRepository->restoreCategory($categoryId)) {
            throw new Exception("Failed to restore category");
        }

        $this->logger->logAudit(
            'category_restored',
            "Category restored (ID: {$categoryId})",
            $restoredBy
        );

        return [
            'success' => true,
            'message' => 'Category restored successfully'
        ];
    }

    /**
     * Get all categories including inactive ones
     */
    public function getAllCategoriesIncludingInactive(?string $entityType = null): array
    {
        return $this->documentRepository->getAllCategoriesIncludingInactive($entityType);
    }

    /**
     * Validate category data
     */
    private function validateCategoryData(array $data): void
    {
        $required = ['category_name', 'category_code', 'entity_type'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new ValidationException(
                    [$field => ['Field is required']],
                    "Missing required field: {$field}"
                );
            }
        }

        // Validate entity_type
        if (!in_array($data['entity_type'], ['staff', 'student', 'both'])) {
            throw new ValidationException(
                ['entity_type' => ['Invalid entity type']],
                'Entity type must be staff, student, or both'
            );
        }

        // Validate category_code format (uppercase, underscores, no spaces)
        if (!preg_match('/^[A-Z_]+$/', $data['category_code'])) {
            throw new ValidationException(
                ['category_code' => ['Invalid format']],
                'Category code must be uppercase letters and underscores only'
            );
        }
    }
}
