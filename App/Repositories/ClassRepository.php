<?php

namespace App\Repositories;


use App\Core\Cache;
use App\Core\Database;
use PDO;

class ClassRepository
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

    public function create(string $classId, string $className, ?string $levelId = null): array
    {
        $sql = "
            INSERT INTO classes (class_id, class_name, level_id, status)
            VALUES (:class_id, :class_name, :level_id, :status)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':class_name' => $className,
            ':level_id' => $levelId,
            ':status' => 'active'
        ]);

        $this->clearCache();

        return [
            'id' => $this->db->lastInsertId(),
            'class_id' => $classId,
            'class_name' => $className,
            'level_id' => $levelId,
            'status'   => 'active'
        ];
    }

    public function getAll(string $status = 'active'): array
    {
        $cacheKey = 'classes_all_' . $status;

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $sql = "SELECT id, class_id, class_name, level_id, status FROM classes WHERE status = :status ORDER BY class_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':status' => $status]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->cache->set($cacheKey, $results, self::CACHE_TTL);
        return $results;
    }

    public function getById(int|string $id): ?array
    {
        $sql = is_int($id) ? "SELECT id, class_id, class_name, level_id FROM classes WHERE id = :id AND status='active'" : "SELECT id, class_id, class_name, level_id FROM classes WHERE class_id = :id AND status='active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, class_id, class_name, level_id FROM classes WHERE id IN ({$placeholders}) AND status='active' ORDER BY class_name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCode(string $classId): ?array
    {
        $sql = "SELECT id, class_id, class_name, level_id FROM classes WHERE class_id = :class_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':class_id' => $classId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int|array $id, string $classId, string $className, string $status, ?string $levelId = null): bool
    {
        if (is_array($id)) {
            foreach ($id as $key) {
                $sql = "UPDATE `classes` SET `status` = 'active' WHERE `id` = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':id' => $key]);
            }
            
            $this->clearCache();
            return true;
        }

        $sql = "UPDATE `classes` SET `class_id` = :class_id, `class_name` = :class_name, `level_id` = :level_id, `status` = :status WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':class_id' => $classId, 
            ':class_name' => $className, 
            ':level_id' => $levelId,
            ':status' => $status, 
            ':id' => $id]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    public function delete(int|string $id): bool
    {
        $sql = "UPDATE classes SET status='inactive' WHERE class_id = :class_id";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([':class_id' => $id]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    public function exists(string $id): bool
    {
        $sql = "SELECT `id` FROM classes WHERE id = :class_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':class_id' => $id]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Batch check if classes exist (optimized for bulk operations)
     * 
     * @param array $ids Array of class IDs
     * @return array Map of class_id => true (only existing classes)
     */
    public function existsBatch(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id FROM classes WHERE id IN ($placeholders)";
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
        $this->cache->forget('classes_all_active');
        $this->cache->forget('classes_all_inactive'); // Assuming inactive is used
    }
}
