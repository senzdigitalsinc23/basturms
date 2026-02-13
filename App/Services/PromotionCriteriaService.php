<?php

namespace App\Services;

use App\Models\PromotionCriteria;
use App\Core\Database;
use App\Exceptions\ValidationException;
use PDO;

/**
 * Service for managing promotion criteria.
 */
class PromotionCriteriaService
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
     * Create a new promotion criteria.
     *
     * @param array $data The data to create the criteria.
     * @param string $userId The ID of the user creating the criteria.
     * @return array The result of the operation.
     * @throws ValidationException If validation fails.
     * @throws \Exception If the operation fails.
     */
    public function createCriteria(array $data, string $userId): array
    {
        $validation = $this->validationService->validate($data, [
            'level_id' => 'required',
            'min_score' => 'required|numeric',
            'min_pass_mark' => 'required|numeric',
            'min_electives' => 'required|numeric'
        ]);

        if (!$validation['success']) {
            throw new ValidationException($validation['errors'], 'Validation failed');
        }

        $data['added_by'] = $userId;
        $data['added_on'] = date('Y-m-d H:i:s');

        // Use static create method from Model
        try {
            /** @var PromotionCriteria|null $criteria */
            $criteria = PromotionCriteria::create($data);
        } catch (\Exception $e) {
            throw new \Exception("Model::create failed: " . $e->getMessage());
        }

        if ($criteria) {
            return [
                'success' => true,
                'message' => 'Promotion criteria created successfully',
                'data' => $criteria->toArray()
            ];
        }

        throw new \Exception("Failed to save promotion criteria");
    }

    /**
     * List all promotion criteria.
     *
     * @return array The list of criteria.
     */
    public function listCriteria(): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM promotion_criteria ORDER BY level_id ASC");
        $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $criteria
        ];
    }

    /**
     * Update an existing promotion criteria.
     *
     * @param int $id The ID of the criteria to update.
     * @param array $data The data to update.
     * @param string $userId The ID of the user performing the update.
     * @return array The result of the operation.
     * @throws ValidationException If the criteria is not found.
     * @throws \Exception If the operation fails.
     */
    public function updateCriteria(int $id, array $data, string $userId): array
    {
        $criteria = PromotionCriteria::find($id);
        if (!$criteria) {
            throw new ValidationException(['id' => ['Criteria not found']], 'Validation failed');
        }

        // Build update query manually as Model doesn't support update instance method
        $db = Database::getInstance()->getConnection();
        
        $fields = [];
        $params = [];
        
        if (isset($data['level_id'])) { $fields[] = "level_id = ?"; $params[] = $data['level_id']; }
        if (isset($data['min_score'])) { $fields[] = "min_score = ?"; $params[] = (int)$data['min_score']; }
        if (isset($data['min_pass_mark'])) { $fields[] = "min_pass_mark = ?"; $params[] = (int)$data['min_pass_mark']; }
        if (isset($data['min_electives'])) { $fields[] = "min_electives = ?"; $params[] = (int)$data['min_electives']; }
        
        if (empty($fields)) {
             return [
                'success' => true,
                'message' => 'No changes made',
                'data' => $criteria->toArray()
            ];
        }
        
        $params[] = $id; // For WHERE id = ?
        
        $sql = "UPDATE promotion_criteria SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute($params)) {
             // Fetch updated
             $updated = PromotionCriteria::find($id);
             return [
                'success' => true,
                'message' => 'Promotion criteria updated successfully',
                'data' => $updated ? $updated->toArray() : []
            ];
        }

        throw new \Exception("Failed to update promotion criteria");
    }

    /**
     * Delete a promotion criteria.
     *
     * @param int $id The ID of the criteria to delete.
     * @return array The result of the operation.
     * @throws ValidationException If the criteria is not found.
     * @throws \Exception If the operation fails.
     */
    public function deleteCriteria(int $id): array
    {
        $criteria = PromotionCriteria::find($id);
        if (!$criteria) {
            throw new ValidationException(['id' => ['Criteria not found']], 'Validation failed');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM promotion_criteria WHERE id = ?");

        if ($stmt->execute([$id])) {
            return [
                'success' => true,
                'message' => 'Promotion criteria deleted successfully'
            ];
        }

        throw new \Exception("Failed to delete promotion criteria");
    }
}
