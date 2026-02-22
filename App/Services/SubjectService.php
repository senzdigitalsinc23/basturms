<?php

namespace App\Services;

use App\Repositories\SubjectRepository;

/**
 * Service for managing subjects.
 */
class SubjectService
{
    private SubjectRepository $repo;

    /**
     * @param SubjectRepository|null $repo Optional repository instance (defaults to new instance)
     */
    public function __construct(?SubjectRepository $repo = null)
    {
        $this->repo = $repo ?? new SubjectRepository();
    }

    /**
     * Create a new subject.
     *
     * @param string $subjectName The name of the subject.
     * @param string $subjectCode The unique code for the subject.
     * @param string $level The educational level.
     * @param string $category The category (e.g., core, elective).
     * @param string|null $description Optional description.
     * @param string $addedBy The ID of the user adding the subject.
     * @return array The result of the operation.
     * @throws \Exception If validation fails or subject exists.
     */
    public function createSubject(
        string $subjectName,
        string $subjectCode,
        string $level,
        string $category,
        ?string $description,
        string $addedBy
    ): array {
        if (empty($subjectName) || empty($subjectCode) || empty($level) || empty($category)) {
            throw new \Exception('Subject name, code, level, and category are required');
        }

        // Check for existing active subjects with the same code or same name/level combination
        $existingByCode = $this->repo->getByCode($subjectCode, true); // Check all subjects, active or dormant
        if ($existingByCode) {
            if ($existingByCode['status'] === 'dormant') {
                throw new \Exception("A dormant subject with code '{$subjectCode}' already exists. Please reactivate it or use a different code.");
            } else {
                throw new \Exception("An active subject with code '{$subjectCode}' already exists.");
            }
        }

        // Note: The unique key `idx_subject_level` in the database prevents duplicate `subject_name` and `level` combinations.
        // We don't need an explicit check here as the repository's create method will throw a PDOException if violated.

        $result = $this->repo->create($subjectName, $subjectCode, $level, $category, $description, $addedBy);

        return [
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => $result
        ];
    }

    /**
     * List subjects based on status.
     *
     * @param string $status The status to filter by ('active' or 'dormant').
     * @return array The list of subjects.
     */
    public function listSubjects(?string $status = 'active'): array
    {
        $data = $this->repo->getAll($status);
        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * Retrieve a single subject by ID.
     *
     * @param int $id The subject ID.
     * @param bool $includeDormant Whether to include dormant subjects.
     * @return array The subject details.
     * @throws \Exception If subject not found.
     */
    public function getSubject(int $id, bool $includeDormant = false): array
    {
        $subject = $this->repo->getById($id, $includeDormant);
        if (!$subject) {
            throw new \Exception('Subject not found');
        }

        return [
            'success' => true,
            'data' => $subject
        ];
    }

    /**
     * Update an existing subject's details.
     *
     * @param int|array $id The subject ID (or array of IDs for bulk update).
     * @param string $subjectName The new subject name.
     * @param string $subjectCode The new subject code.
     * @param string $level The new level.
     * @param string $category The new category.
     * @param string|null $description The new description.
     * @param string $status The new status.
     * @return array The result of the operation.
     * @throws \Exception If subject not found or validation fails.
     */
    public function updateSubject(int|array $id, string $subjectName, string $subjectCode, string $level, string $category, ?string $description, string $status): array
    {
        
        if (!is_array($id)) {
            if (empty($subjectName) || empty($subjectCode) || empty($level) || empty($category)) {
                throw new \Exception('Subject name, code, level, and category are required');
            }
    
            $subject = $this->repo->getById((int)$id, true); // Get regardless of status for update
            if (!$subject) {
                throw new \Exception('Subject not found');
            }
    
            // Check for duplicate subject code, excluding the current subject being updated
            $existingByCode = $this->repo->getByCode($subjectCode, true); 
            if ($existingByCode && (int)$existingByCode['id'] !== (int)$id) {
                throw new \Exception('Another subject with this code already exists');
            }
        }

        $this->repo->update($id, $subjectName, $subjectCode, $level, $category, $description, $status);

        return [
            'success' => true,
            'message' => 'Subject updated successfully'
        ];
    }

    /**
     * Set subject status to 'dormant' (soft delete).
     *
     * @param int|string|array<int|string> $subjects The subject ID(s) or code(s).
     * @return array Detailed results of the operation.
     * @throws \Exception If subjects not found.
     */
    public function deleteSubject(int|string|array $subjects): array
    {
        // Normalize input to array
        if (!is_array($subjects)) {
            $subjects = [$subjects];
        }

        if (empty($subjects)) {
            throw new \Exception('At least one subject ID or code is required for deletion.');
        }

        $results = [];
        $allSuccessful = true;

        foreach ($subjects as $idOrCode) {
            $idOrCode = trim((string)$idOrCode);
            if (empty($idOrCode)) {
                $results[] = [
                    'subject' => '(empty)',
                    'success' => false,
                    'message' => 'Empty subject ID/code provided.'
                ];
                $allSuccessful = false;
                continue;
            }

            try {
                // Determine if it's an ID or code and fetch the subject
                if (is_numeric($idOrCode)) {
                    $subject = $this->repo->getById((int)$idOrCode, false); // Only allow deleting active subjects by ID
                } else {
                    $subject = $this->repo->getByCode($idOrCode, false); // Only allow deleting active subjects by code
                }
                
                if (!$subject) {
                    $results[] = [
                        'subject' => $idOrCode,
                        'success' => false,
                        'message' => 'Active subject not found or already dormant.'
                    ];
                    $allSuccessful = false;
                    continue;
                }

                // Perform the soft delete
                $this->repo->updateStatus((int)$subject['id'], 'dormant');

                $results[] = [
                    'subject' => $idOrCode,
                    'success' => true,
                    'message' => 'Subject set to dormant successfully.'
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'subject' => $idOrCode,
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ];
                $allSuccessful = false;
            }
        }

        return [
            'success' => $allSuccessful,
            'message' => $allSuccessful ? 'All selected subjects set to dormant.' : 'Some subjects could not be set to dormant.',
            'results' => $results
        ];
    }
}
