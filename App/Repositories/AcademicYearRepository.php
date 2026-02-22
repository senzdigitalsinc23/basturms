<?php

namespace App\Repositories;

use App\Core\Cache;
use App\Core\Database;
use PDO;
use PDOException;

/**
 * Repository class for academic year data access operations.
 * 
 * Handles all database interactions for academic years configuration.
 */
class AcademicYearRepository
{
    private PDO $db;
    private Cache $cache;
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * AcademicYearRepository constructor.
     * 
     * Initializes the database connection and cache.
     * 
     * @param PDO|null $db Optional database connection (defaults to singleton)
     * @param Cache|null $cache Optional cache instance (defaults to new instance)
     */
    public function __construct(?PDO $db = null, ?Cache $cache = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
        $this->cache = $cache ?? new Cache();
    }

    /**
     * Creates a new academic year record.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @param int $numberOfTerms The number of terms (1-3)
     * @param string $status The status (Active, Upcoming, Completed, Archived)
     * @param string $addedBy The user ID who created this record
     * @return array Array containing the created record data including the new ID
     * @throws PDOException If database operation fails
     */
    public function create(string $academicYear, int $numberOfTerms, string $status, string $addedBy): array
    {
        $sql = "
            INSERT INTO academic_years (
                academic_year,
                number_of_terms,
                status,
                added_by
            )
            VALUES (
                :academic_year,
                :number_of_terms,
                :status,
                :added_by
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':academic_year' => $academicYear,
            ':number_of_terms' => $numberOfTerms,
            ':status' => $status,
            ':added_by' => $addedBy,
        ]);

        $insertId = (int)$this->db->lastInsertId();

        // Invalidate cache
        $this->clearCache();

        return [
            'id' => $insertId,
            'academic_year' => $academicYear,
            'number_of_terms' => $numberOfTerms,
            'status' => $status,
            'added_by' => $addedBy,
        ];
    }

    /**
     * Retrieves an academic year by ID.
     *
     * @param int $id The academic year ID
     * @return array|null The academic year record or null if not found
     * @throws PDOException If database operation fails
     */
    public function getById(int $id): ?array
    {
        $sql = "
            SELECT 
                id, 
                academic_year, 
                number_of_terms, 
                status, 
                added_by, 
                added_on,
                updated_by,
                updated_on
            FROM academic_years 
            WHERE id = :id 
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Retrieves an academic year by academic year string.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @return array|null The academic year record or null if not found
     * @throws PDOException If database operation fails
     */
    public function getByAcademicYear(string $academicYear): ?array
    {
        $sql = "
            SELECT 
                id, 
                academic_year, 
                number_of_terms, 
                status, 
                added_by, 
                added_on,
                updated_by,
                updated_on
            FROM academic_years 
            WHERE academic_year = :academic_year 
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':academic_year' => $academicYear]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Retrieves all academic years, optionally filtered by academic year search.
     *
     * @param string $searchYear Optional academic year to filter by (e.g., '2025/2026')
     * @return array Array of academic year records
     * @throws PDOException If database operation fails
     */
    public function getAll(string $searchYear = ''): array
    {
        $cacheKey = 'academic_years_all_' . md5($searchYear);
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $sql = "
            SELECT
                id,
                academic_year,
                number_of_terms,
                status,
                added_by,
                added_on,
                updated_by,
                updated_on
            FROM academic_years
        ";

        $params = [];

        if (!empty($searchYear)) {
            $sql .= " WHERE academic_year LIKE :search_year";
            $params[':search_year'] = '%' . $searchYear . '%';
        }

        $sql .= " ORDER BY academic_year DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->cache->set($cacheKey, $results, self::CACHE_TTL);
        
        return $results;
    }

    /**
     * Retrieves the currently active academic year.
     *
     * @return array|null The active academic year record or null if not found
     * @throws PDOException If database operation fails
     */
    public function getActive(): ?array
    {
        $cacheKey = 'academic_year_active';
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $sql = "
            SELECT 
                id, 
                academic_year, 
                number_of_terms, 
                status, 
                added_by, 
                added_on,
                updated_by,
                updated_on
            FROM academic_years 
            WHERE status = 'Active' 
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $data = $result ?: null;
        
        $this->cache->set($cacheKey, $data, self::CACHE_TTL);
        
        return $data;
    }

    /**
     * Updates an academic year.
     *
     * @param int $id The academic year ID
     * @param int|null $numberOfTerms The number of terms (optional)
     * @param string|null $status The status (optional)
     * @param string $updatedBy The user ID who updated this record
     * @return bool True if update was successful, false otherwise
     * @throws PDOException If database operation fails
     */
    public function update(int $id, ?int $numberOfTerms = null, ?string $status = null, string $updatedBy = 'system'): bool
    {
        $updates = [];
        $params = [':id' => $id, ':updated_by' => $updatedBy];
        
        if ($numberOfTerms !== null) {
            $updates[] = 'number_of_terms = :number_of_terms';
            $params[':number_of_terms'] = $numberOfTerms;
        }
        
        if ($status !== null) {
            $updates[] = 'status = :status';
            $params[':status'] = $status;
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = 'updated_by = :updated_by';
        $updates[] = 'updated_on = NOW()';
        
        $sql = "
            UPDATE academic_years 
            SET " . implode(', ', $updates) . "
            WHERE id = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Deletes an academic year.
     *
     * @param int $id The academic year ID
     * @return bool True if deletion was successful, false otherwise
     * @throws PDOException If database operation fails
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM academic_years WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([':id' => $id]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Deletes an academic year by academic year string.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @return bool True if deletion was successful, false otherwise
     * @throws PDOException If database operation fails
     */
    public function deleteByAcademicYear(string $academicYear): bool
    {
        $sql = "DELETE FROM academic_years WHERE academic_year = :academic_year";
        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([':academic_year' => $academicYear]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Clears repository-specific cache.
     */
    private function clearCache(): void
    {
        $this->cache->forget('academic_year_active');
        // We can't easily clear all search variations, so we assume
        // heavy search caching is for empty search. For specific searches,
        // we might need tagging or prefix clearing if the Cache class supports it.
        // For now, clearing specifically named keys.
        // A better approach would be to clear by prefix logic if supported.
        // Assuming simple usage for now.
        $this->cache->forget('academic_years_all_' . md5(''));
        
        // As a fallback for search variations, we might just accept they live for TTL
        // or implement clear by pattern if the Driver allows.
        // The current file-based cache doesn't support pattern clear easily without scanning dir.
    }
}

