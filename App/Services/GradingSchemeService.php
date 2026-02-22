<?php

namespace App\Services;

use App\Models\GradingScheme;
use App\Exceptions\ValidationException;
use App\Core\Database;
use PDO;

/**
 * Service for managing grading schemes.
 */
class GradingSchemeService
{
    private ValidationService $validationService;

    /**
     * @param ValidationService $validationService
     */
    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Create a new grading scheme entry.
     *
     * @param array $data The data for the new grading entry.
     * @param string $userId The ID of the user creating the entry.
     * @return array The result of the operation.
     * @throws ValidationException If validation fails or ranges overlap.
     */
    public function createGrading(array $data, string $userId): array
    {
        $validation = $this->validationService->validate($data, [
            'grade' => 'required',
            'grade_from' => 'required|numeric',
            'grade_to' => 'required|numeric',
            'remarks' => 'required'
        ]);

        if (!$validation['success']) {
            throw new ValidationException($validation['errors'], 'Validation failed');
        }

        // Basic range validation
        if ($data['grade_from'] > $data['grade_to']) {
            throw new ValidationException(['grade_from' => ['Grade from cannot be greater than grade to']], 'Validation failed');
        }

        // Unique grade label check
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM grading_scheme WHERE grade = ?");
        $stmt->execute([$data['grade']]);
        if ($stmt->fetchColumn() > 0) {
            throw new ValidationException(['grade' => ['Grade label already exists']], 'Validation failed');
        }

        // Range overlap check
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM grading_scheme 
            WHERE (? BETWEEN grade_from AND grade_to) 
            OR (? BETWEEN grade_from AND grade_to)
            OR (grade_from BETWEEN ? AND ?)
        ");
        $stmt->execute([$data['grade_from'], $data['grade_to'], $data['grade_from'], $data['grade_to']]);
        if ($stmt->fetchColumn() > 0) {
            throw new ValidationException(['grade_range' => ['Grade range overlaps with an existing entry']], 'Validation failed');
        }

        $data['added_by'] = $userId;
        $data['added_on'] = date('Y-m-d H:i:s');

        $grading = GradingScheme::create($data);

        return [
            'success' => true,
            'message' => 'Grading scheme entry created successfully',
            'data' => $grading->toArray()
        ];
    }

    /**
     * List all grading scheme entries.
     *
     * @return array The list of grading entries.
     */
    public function listGrading(): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM grading_scheme ORDER BY grade_from DESC");
        $gradings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $gradings
        ];
    }

    /**
     * Update a grading scheme entry.
     *
     * @param int $id The ID of the entry (currently mapped to 'grade' implementation).
     * @param array $data The data to update.
     * @return array The result of the operation.
     * @throws ValidationException If entry not found or validation fails.
     */
    public function updateGrading(int $id, array $data): array
    {
        $grading = GradingScheme::where('grade', $data['grade'], 'grading_scheme');
        if (!$grading) {
            throw new ValidationException(['id' => ['Grading entry not found']], 'Validation failed');
        }

        $validation = $this->validationService->validate($data, [
            'grade' => 'required',
            'grade_from' => 'required|numeric',
            'grade_to' => 'required|numeric',
            'remarks' => 'required'
        ]);

        if (!$validation['success']) {
            throw new ValidationException($validation['errors'], 'Validation failed');
        }

        $db = Database::getInstance()->getConnection();

        // Unique grade label check (excluding self)
        /*  $stmt = $db->prepare("SELECT COUNT(*) FROM grading_scheme WHERE grade = ?");
        $stmt->execute([$data['grade']]);
        if ($stmt->fetchColumn() > 0) {
            throw new ValidationException(['grade' => ['Grade label already exists']], 'Validation failed');
        }

        // Range overlap check (excluding self)
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM grading_scheme 
            WHERE grade != ? AND (
                (? BETWEEN grade_from AND grade_to) 
                OR (? BETWEEN grade_from AND grade_to)
                OR (grade_from BETWEEN ? AND ?)
            )
        ");
        $stmt->execute([$data['grade'], $data['grade_from'], $data['grade_to'], $data['grade_from'], $data['grade_to']]);
        if ($stmt->fetchColumn() > 0) {
            throw new ValidationException(['grade_range' => ['Grade range overlaps with an existing entry']], 'Validation failed');
        } */

        
        $stmt = $db->prepare("
            UPDATE grading_scheme 
            SET grade = ?, grade_from = ?, grade_to = ?, remarks = ? 
            WHERE grade = ?
        ");
        $stmt->execute([
            $data['grade'],
            $data['grade_from'],
            $data['grade_to'],
            $data['remarks'],
            $data['grade']
        ]);

        return [
            'success' => true,
            'message' => 'Grading scheme entry updated successfully'
        ];
    }

    /**
     * Delete a grading scheme entry.
     *
     * @param int $id The ID (grade label) of the entry to delete.
     * @return array The result of the operation.
     * @throws ValidationException If entry not found.
     */
    public function deleteGrading(int $id): array
    {
        
        $grading = GradingScheme::where('grade', $id, 'grading_scheme');

        if (!$grading) {
            throw new ValidationException(['id' => ['Grading entry not found']], 'Validation failed');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM grading_scheme WHERE grade = ?");
        $stmt->execute([$id]);

        return [
            'success' => true,
            'message' => 'Grading scheme entry deleted successfully'
        ];
    }
}
