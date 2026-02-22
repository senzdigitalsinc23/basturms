<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class UploadRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Log a new upload in the database.
     *
     * @param array $data
     * @return int|null The ID of the new upload, or null on failure.
     */
    public function logUpload(array $data): ?int
    {
        $sql = "INSERT INTO uploads (doc_id, doc_name, doc_type, url, file_type, file_size) 
                VALUES (:doc_id, :doc_name, :doc_type, :url, :file_type, :file_size)";
        
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'doc_id' => $data['doc_id'] ?? null,
            'doc_name' => $data['doc_name'],
            'doc_type' => $data['doc_type'],
            'url' => $data['url'],
            'file_type' => $data['file_type'],
            'file_size' => $data['file_size']
        ]);

        return $success ? (int) $this->db->lastInsertId() : null;
    }

    /**
     * Get upload metadata by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM uploads WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get uploads by document type.
     *
     * @param string $docType
     * @return array
     */
    public function getByType(string $docType): array
    {
        $sql = "SELECT * FROM uploads WHERE doc_type = :doc_type ORDER BY uploaded_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['doc_type' => $docType]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
