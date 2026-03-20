<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class DocumentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $db = null)
    {
        $this->pdo = $db ?? Database::getInstance()->getConnection();
    }

    /**
     * Generate unique document ID
     * Format: DOC{year}{month}{sequence} e.g., DOC2602001
     */
    public function generateDocumentId(): string
    {
        $yearMonth = date('ym'); // e.g., 2602 for February 2026
        $prefix = 'DOC' . $yearMonth;
        
        $sql = "SELECT document_id FROM documents 
                WHERE document_id LIKE ? 
                ORDER BY document_id DESC LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["{$prefix}%"]);
        $lastId = $stmt->fetchColumn();
        
        if ($lastId) {
            $sequenceStr = substr($lastId, strlen($prefix));
            $sequence = (int)$sequenceStr + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('%s%03d', $prefix, $sequence);
    }

    /**
     * Create a new document record
     */
    public function createDocument(array $data): bool
    {
        $sql = "INSERT INTO documents (
            document_id, entity_type, entity_id, category_id, document_name,
            original_filename, file_path, file_size, file_type, file_extension,
            description, uploaded_by, upload_date, expiry_date, metadata
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['document_id'],
            $data['entity_type'],
            $data['entity_id'],
            $data['category_id'],
            $data['document_name'],
            $data['original_filename'],
            $data['file_path'],
            $data['file_size'],
            $data['file_type'],
            $data['file_extension'],
            $data['description'] ?? null,
            $data['uploaded_by'],
            $data['expiry_date'] ?? null,
            $data['metadata'] ?? null
        ]);
    }

    /**
     * Get documents by entity
     */
    public function getDocumentsByEntity(string $entityId, string $status = 'active'): array {
        $sql = "SELECT d.*, dc.category_name, dc.category_code
                FROM documents d
                LEFT JOIN document_categories dc ON d.category_id = dc.id
                WHERE d.entity_id = ? AND d.status = ?";
        
        $params = [$entityId, $status];
        
        /* if ($categoryId) {
            $sql .= " AND d.category_id = ?";
            $params[] = $categoryId;
        } */
        
        $sql .= " ORDER BY d.upload_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get document by ID
     */
    public function getDocumentById(string $documentId): ?array
    {
        $sql = "SELECT d.*, dc.category_name, dc.category_code
                FROM documents d
                LEFT JOIN document_categories dc ON d.category_id = dc.id
                WHERE d.document_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$documentId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update document status
     */
    public function updateDocumentStatus(string $documentId, string $status): bool
    {
        $sql = "UPDATE documents SET status = ?, updated_at = NOW() WHERE document_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $documentId]);
    }

    /**
     * Delete document (soft delete)
     */
    public function deleteDocument(string $documentId): bool
    {
        return $this->updateDocumentStatus($documentId, 'deleted');
    }

    /**
     * Permanently delete document (hard delete)
     */
    public function permanentlyDeleteDocument(string $documentId): bool
    {
        $sql = "DELETE FROM documents WHERE document_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$documentId]);
    }

    /**
     * Verify document
     */
    public function verifyDocument(string $documentId, string $verifiedBy): bool
    {
        $sql = "UPDATE documents 
                SET is_verified = 1, verified_by = ?, verified_at = NOW(), updated_at = NOW()
                WHERE document_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$verifiedBy, $documentId]);
    }

    /**
     * Get all document categories
     */
    public function getAllCategories(?string $entityType = null): array
    {
        $sql = "SELECT * FROM document_categories WHERE is_active = 1";
        $params = [];
        
        if ($entityType) {
            $sql .= " AND (entity_type = ? OR entity_type = 'both')";
            $params[] = $entityType;
        }
        
        $sql .= " ORDER BY category_name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get category by ID
     */
    public function getCategoryById(int $categoryId): ?array
    {
        $sql = "SELECT * FROM document_categories WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$categoryId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Count documents by entity
     */
    public function countDocumentsByEntity(string $entityType, string $entityId, string $status = 'active'): int
    {
        $sql = "SELECT COUNT(*) FROM documents 
                WHERE entity_type = ? AND entity_id = ? AND status = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$entityType, $entityId, $status]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create a new document category
     */
    public function createCategory(array $data): int
    {
        $sql = "INSERT INTO document_categories (
            category_name, category_code, entity_type, description, is_active
        ) VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['category_name'],
            $data['category_code'],
            $data['entity_type'],
            $data['description'] ?? null,
            $data['is_active'] ?? true
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update a document category
     */
    public function updateCategory(int $categoryId, array $data): bool
    {
        $sql = "UPDATE document_categories SET 
                category_name = ?, 
                category_code = ?, 
                entity_type = ?, 
                description = ?,
                updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['category_name'],
            $data['category_code'],
            $data['entity_type'],
            $data['description'] ?? null,
            $categoryId
        ]);
    }

    /**
     * Delete a document category (soft delete)
     */
    public function deleteCategory(int $categoryId): bool
    {
        $sql = "UPDATE document_categories SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$categoryId]);
    }

    /**
     * Permanently delete a document category
     */
    public function permanentlyDeleteCategory(int $categoryId): bool
    {
        // Check if category is in use
        if ($this->isCategoryInUse($categoryId)) {
            return false;
        }

        $sql = "DELETE FROM document_categories WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$categoryId]);
    }

    /**
     * Check if category code already exists
     */
    public function categoryCodeExists(string $categoryCode, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) FROM document_categories WHERE category_code = ? AND id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$categoryCode, $excludeId]);
        } else {
            $sql = "SELECT COUNT(*) FROM document_categories WHERE category_code = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$categoryCode]);
        }
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if category is in use by any documents
     */
    public function isCategoryInUse(int $categoryId): bool
    {
        $sql = "SELECT COUNT(*) FROM documents WHERE category_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$categoryId]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Restore a soft-deleted category
     */
    public function restoreCategory(int $categoryId): bool
    {
        $sql = "UPDATE document_categories SET is_active = 1, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$categoryId]);
    }

    /**
     * Get all categories including inactive ones
     */
    public function getAllCategoriesIncludingInactive(?string $entityType = null): array
    {
        $sql = "SELECT * FROM document_categories";
        $params = [];
        
        if ($entityType) {
            $sql .= " WHERE (entity_type = ? OR entity_type = 'both')";
            $params[] = $entityType;
        }
        
        $sql .= " ORDER BY id ASC, is_active DESC, category_name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
