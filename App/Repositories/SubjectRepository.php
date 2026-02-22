<?php

namespace App\Repositories;


use App\Core\Cache;
use App\Core\Database;
use PDO;

class SubjectRepository
{
    private PDO $db;
    private Cache $cache;
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * @param PDO|null $db Optional database connection (defaults to singleton)
     * @param Cache|null $cache Optional cache instance (defaults to new instance)
     */
    public function __construct(?PDO $db = null, ?Cache $cache = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
        $this->cache = $cache ?? new Cache();
    }

    public function create(
        string $subjectName,
        string $subjectCode,
        string $level,
        string $category,
        ?string $description,
        string $addedBy
    ): array {
        $sql = "
            INSERT INTO subjects (
                subject_name,
                subject_code,
                level,
                category,
                description,
                added_by,
                added_on,
                status
            ) VALUES (
                :subject_name,
                :subject_code,
                :level,
                :category,
                :description,
                :added_by,
                NOW(),
                'active'
            )
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':subject_name' => $subjectName,
            ':subject_code' => $subjectCode,
            ':level' => $level,
            ':category' => $category,
            ':description' => $description,
            ':added_by' => $addedBy,
        ]);

        $this->clearCache();

        return [
            'id' => $this->db->lastInsertId(),
            'subject_name' => $subjectName,
            'subject_code' => $subjectCode,
            'level' => $level,
            'category' => $category,
            'description' => $description,
            'status' => 'active',
        ];
    }

    /**
     * Retrieves all subjects, optionally filtered by status.
     *
     * @param string|null $status Filter by 'active' or 'dormant'. If null, retrieves all subjects.
     * @return array Array of all subject records
     */
    public function getAll(?string $status = 'active'): array
    {
        $cacheKey = 'subjects_all_' . ($status ?? 'null');
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        error_log("SubjectRepository: getAll called with status: " . ($status ?? 'null'));
        $sql = "SELECT id, subject_name, subject_code, `level`, category, `description`, `status` FROM subjects";
        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY subject_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->cache->set($cacheKey, $results, self::CACHE_TTL);
        return $results;
    }

    /**
     * Retrieves a subject by ID, optionally including dormant subjects.
     *
     * @param int $id The subject ID
     * @param bool $includeDormant Whether to include dormant subjects
     * @return array|null The subject record or null if not found
     */
    public function getById(int $id, bool $includeDormant = false): ?array
    {
        $sql = "SELECT id, subject_name, subject_code, level, category, description, status, added_by, added_on FROM subjects WHERE id = :id";
        if (!$includeDormant) {
            $sql .= " AND status = 'active'";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByIds(array $ids, bool $includeDormant = false): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, subject_name, subject_code, level, category, description, status, added_by, added_on FROM subjects WHERE id IN ({$placeholders})";
        if (!$includeDormant) {
            $sql .= " AND status = 'active'";
        }
        $sql .= " ORDER BY subject_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a subject by code, optionally including dormant subjects.
     *
     * @param string $subjectCode The subject code
     * @param bool $includeDormant Whether to include dormant subjects
     * @return array|null The subject record or null if not found
     */
    public function getByCode(string $subjectCode, bool $includeDormant = false): ?array
    {
        $sql = "SELECT id, subject_name, subject_code, level, category, description, status, added_by, added_on FROM subjects WHERE subject_code = :subject_code";

        if (!$includeDormant) {
            $sql .= " AND status = 'active'";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':subject_code' => $subjectCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Updates an existing subject's details.
     *
     * @param int $id The subject ID
     * @param string $subjectName The new subject name
     * @param string $subjectCode The new subject code
     * @param string $level The new subject level
     * @param string $category The new subject category
     * @param string|null $description The new subject description
     * @return bool True if update was successful, false otherwise
     */
    public function update(int | array $id, string $subjectName, string $subjectCode, string $level, string $category, ?string $description, string $status): bool
    {
        if (is_array($id)) {
            foreach ($id as $key) {
                $sql = "UPDATE `subjects` SET `status` = 'active' WHERE `id` = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':id' => $key]);
            }
            
            $this->clearCache();
            return true;
        }

        $sql = "UPDATE subjects SET subject_name = :subject_name, subject_code = :subject_code, level = :level, category = :category, description = :description, `status` = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':subject_name' => $subjectName,
            ':subject_code' => $subjectCode,
            ':level' => $level,
            ':category' => $category,
            ':description' => $description,
            'status' => $status,
            ':id' => $id
        ]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Updates the status of a subject.
     *
     * @param int $id The subject ID
     * @param string $status The new status ('active' or 'dormant')
     * @return bool True if update was successful, false otherwise
     */
    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE subjects SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':status' => $status, ':id' => $id]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    public function exists(int $id): bool
    {
        $sql = "SELECT id FROM subjects WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Batch check if subjects exist (optimized for bulk operations)
     * 
     * @param array $ids Array of subject IDs
     * @return array Map of subject_id => true (only existing subjects)
     */
    public function existsBatch(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id FROM subjects WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id']] = true;
        }
        
        return $result;
    }

    private function clearCache(): void
    {
        $this->cache->forget('subjects_all_active');
        $this->cache->forget('subjects_all_dormant');
        $this->cache->forget('subjects_all_null');
    }
}
