<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Repository class for academic setup data access operations.
 * 
 * Handles all database interactions for academic years, terms, and configurations.
 * Provides data access methods with proper error handling and parameter binding.
 */
class AcademicSetupRepository
{
    private PDO $db;

    /**
     * AcademicSetupRepository constructor.
     * 
     * Initializes the database connection.
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Sets or updates the number of terms for an academic year.
     *
     * @param string $academicYear The academic year (e.g., '2023-2024')
     * @param int $numberOfTerms The number of terms (1-3)
     * @param string $addedBy The user ID who set this (defaults to 'system')
     * @return array Array containing academic_year and number_of_terms
     * @throws PDOException If database operation fails
     */
    public function setNumberOfTerms(string $academicYear, int $numberOfTerms, string $addedBy = 'system'): array
    {
        $sql = "
            INSERT INTO academic_year_terms (academic_year, number_of_terms, added_by)
            VALUES (:academic_year, :number_of_terms, :added_by)
            ON DUPLICATE KEY UPDATE 
                number_of_terms = :number_of_terms_update
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':academic_year' => $academicYear,
            ':number_of_terms' => $numberOfTerms,
            ':number_of_terms_update' => $numberOfTerms,
            ':added_by' => $addedBy
        ]);

        return [
            'academic_year' => $academicYear,
            'number_of_terms' => $numberOfTerms,
        ];
    }

    /**
     * Retrieves the number of terms configured for an academic year.
     *
     * @param string $academicYear The academic year (e.g., '2023-2024')
     * @return int|null The number of terms or null if not found
     * @throws PDOException If database operation fails
     */
    public function getNumberOfTerms(string $academicYear): ?int
    {
        $sql = "SELECT number_of_terms FROM academic_year_terms WHERE academic_year = :academic_year LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':academic_year' => $academicYear]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int)$result['number_of_terms'] : null;
    }

    /**
     * Creates a new academic year term record.
     *
     * @param string $academicYear The academic year (e.g., '2023-2024')
     * @param string $term The term name (e.g., 'Term 1')
     * @param string $startDate The start date (Y-m-d format)
     * @param string $endDate The end date (Y-m-d format)
     * @param string $status The status (Active, Upcoming, Completed)
     * @param string $addedBy The user ID who created this record
     * @return array Array containing the created record data including the new ID
     * @throws PDOException If database operation fails
     */
    public function create(string $academicYear, string $term, string $startDate, string $endDate, string $status, string $addedBy): array
    {
        $sql = "
            INSERT INTO academic_setup (
                academic_year, 
                term, 
                start_date, 
                end_date, 
                status, 
                added_by
            )
            VALUES (
                :academic_year, 
                :term, 
                :start_date, 
                :end_date, 
                :status, 
                :added_by
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':academic_year' => $academicYear,
            ':term' => $term,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':added_by' => $addedBy,
        ]);

        $insertId = (int)$this->db->lastInsertId();

        return [
            'id' => $insertId,
            'academic_year' => $academicYear,
            'term' => $term,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'added_by' => $addedBy,
        ];
    }

    /**
     * Creates multiple academic year terms in a single transaction.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @param array $terms Array of term data with keys: term, start_date, end_date, status
     * @param string $addedBy The user ID who created these records
     * @return array Array of created records with their IDs
     * @throws PDOException If database operation fails
     */
    public function createAllTerms(string $academicYear, array $terms, string $addedBy): array
    {
        $this->db->beginTransaction();
        
        try {
            $createdTerms = [];
            $sql = "
                INSERT INTO academic_setup (
                    academic_year,
                    term,
                    start_date,
                    end_date,
                    status,
                    added_by
                )
                VALUES (
                    :academic_year,
                    :term,
                    :start_date,
                    :end_date,
                    :status,
                    :added_by
                )
            ";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($terms as $termData) {
                $stmt->execute([
                    ':academic_year' => $academicYear,
                    ':term' => $termData['term'],
                    ':start_date' => $termData['start_date'],
                    ':end_date' => $termData['end_date'],
                    ':status' => $termData['status'],
                    ':added_by' => $addedBy,
                ]);
                
                $createdTerms[] = [
                    'id' => (int)$this->db->lastInsertId(),
                    'academic_year' => $academicYear,
                    'term' => $termData['term'],
                    'start_date' => $termData['start_date'],
                    'end_date' => $termData['end_date'],
                    'status' => $termData['status'],
                    'added_by' => $addedBy,
                ];
            }
            
            $this->db->commit();
            return $createdTerms;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Retrieves all academic year terms.
     *
     * @return array Array of all academic year term records
     * @throws PDOException If database operation fails
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                id,
                academic_year,
                term,
                start_date,
                end_date,
                status,
                added_by,
                added_on
            FROM academic_setup
            ORDER BY academic_year DESC, term DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all terms for a specific academic year.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @return array Array of term records for the academic year
     * @throws PDOException If database operation fails
     */
    public function getTermsByAcademicYear(string $academicYear): array
    {
        $sql = "
            SELECT
                id,
                academic_year,
                term,
                start_date,
                end_date,
                status,
                added_by,
                added_on
            FROM academic_setup
            WHERE academic_year = :academic_year
            ORDER BY term ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':academic_year' => $academicYear]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves an academic year term by ID.
     *
     * @param int $id The academic year term ID
     * @return array|null The academic year term record or null if not found
     * @throws PDOException If database operation fails
     */
    public function getById(int $id): ?array
    {
        $sql = "
            SELECT 
                id, 
                academic_year, 
                term, 
                start_date, 
                end_date, 
                status, 
                added_by, 
                added_on
            FROM academic_setup 
            WHERE id = :id 
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Retrieves an academic year term by academic year and term name.
     *
     * @param string $academicYear The academic year (e.g., '2023-2024')
     * @param string $term The term name (e.g., 'First Term')
     * @return array|null The academic year term record or null if not found
     * @throws PDOException If database operation fails
     */
    public function getByYearAndTerm(string $academicYear, string $term): ?array
    {
        $sql = "
            SELECT 
                id, 
                academic_year, 
                term, 
                start_date, 
                end_date, 
                status, 
                added_by, 
                added_on
            FROM academic_setup 
            WHERE academic_year = :academic_year AND term = :term 
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':academic_year' => $academicYear, 
            ':term' => $term
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Retrieves the currently active academic year term.
     *
     * @return array|null The active academic year term record or null if not found
     * @throws PDOException If database operation fails
     */
    public function getActive(): ?array
    {
        $sql = "
            SELECT 
                id as academic_id, 
                academic_year, 
                term, 
                start_date, 
                end_date, 
                status, 
                added_by, 
                added_on
            FROM academic_setup 
            WHERE status = 'Active' 
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }


    /**
     * Updates an existing academic year term.
     *
     * @param int $id The academic year term ID
     * @param string $startDate The start date (Y-m-d format)
     * @param string $endDate The end date (Y-m-d format)
     * @param string $status The status (Active, Upcoming, Completed)
     * @param string $updatedBy The user ID who updated this record
     * @return bool True if update was successful, false otherwise
     * @throws PDOException If database operation fails
     */
    public function update(int $id, string $startDate, string $endDate, string $status, string $updatedBy): bool
    {
        $sql = "
            UPDATE academic_setup 
            SET 
                start_date = :start_date, 
                end_date = :end_date, 
                status = :status 
            WHERE id = :id
        ";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':id' => $id
        ]);
    }

    /**
     * Counts the number of terms for a given academic year.
     *
     * @param string $academicYear The academic year (e.g., '2023-2024')
     * @return int The count of terms for the academic year
     * @throws PDOException If database operation fails
     */
    public function countTermsByYear(string $academicYear): int
    {
        $sql = "
            SELECT COUNT(*) as count 
            FROM academic_setup 
            WHERE academic_year = :academic_year
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':academic_year' => $academicYear]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Deletes an academic year term.
     *
     * @param int $id The academic year term ID
     * @return bool True if deletion was successful, false otherwise
     * @throws PDOException If database operation fails
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM academic_setup WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Deletes all terms for a specific academic year.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @return bool True if deletion was successful, false otherwise
     * @throws PDOException If database operation fails
     */
    public function deleteTermsByAcademicYear(string $academicYear): bool
    {
        $sql = "DELETE FROM academic_setup WHERE academic_year = :academic_year";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':academic_year' => $academicYear]);
    }

    /**
     * Deletes a specific term by academic year and term name.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @param string $term The term name (e.g., 'Term 1')
     * @return bool True if deletion was successful, false otherwise
     * @throws PDOException If database operation fails
     */
    public function deleteByYearAndTerm(string $academicYear, string $term): bool
    {
        $sql = "DELETE FROM academic_setup WHERE academic_year = :academic_year AND term = :term";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':academic_year' => $academicYear,
            ':term' => $term
        ]);
    }

    /**
     * Begins a database transaction.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Commits a database transaction.
     *
     * @return void
     */
    public function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Rolls back a database transaction.
     *
     * @return void
     */
    public function rollBack(): void
    {
        $this->db->rollBack();
    }
}
