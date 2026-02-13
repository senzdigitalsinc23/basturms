<?php

namespace App\Services;

use App\Repositories\ClassRepository;

/**
 * Service for managing classes.
 */
class ClassService
{
    private ClassRepository $repo;

    public function __construct()
    {
        $this->repo = new ClassRepository();
    }

    /**
     * Check if a class ID already exists.
     *
     * @param string $classId The class ID to check.
     * @param int|null $excludeId An optional ID to exclude from the check (for updates).
     * @return void
     * @throws \Exception If the class ID exists.
     */
    private function checkIfClassIdExists(string $classId, ?int $excludeId = null): void
    {
        
        $existing = $this->repo->getByCode($classId);
        if ($existing && ($excludeId === null || $existing['id'] !== $excludeId)) {
            throw new \Exception("Class ID '{$classId}' already exists");
        }
    }

    /**
     * Create a new class.
     *
     * @param string $classId The class ID/code.
     * @param string $className The name of the class.
     * @return array The result of the operation.
     * @throws \Exception If validation fails or class ID exists.
     */
    public function createClass(string $classId, string $className, ?string $levelId = null): array
    {
        if (empty($classId) || empty($className)) {
            throw new \Exception('Class code and name are required');
        }

        $this->checkIfClassIdExists($classId);

        $result = $this->repo->create($classId, $className, $levelId);

        return [
            'success' => true,
            'message' => 'Class created successfully',
            'data' => $result
        ];
    }

    /**
     * List all classes by status.
     *
     * @param string $status The status to filter by ('active' or 'dormant').
     * @return array The list of classes.
     */
    public function listClasses(?string $status = 'active'): array
    {
        $data = $this->repo->getAll($status);
        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * Get a single class by ID.
     *
     * @param int $id The class ID.
     * @return array The class details.
     * @throws \Exception If class not found.
     */
    public function getClass(int $id): array
    {
        $class = $this->repo->getById($id);
        if (!$class) {
            throw new \Exception('Class not found');
        }

        return [
            'success' => true,
            'data' => $class
        ];
    }

    /**
     * Update an existing class.
     *
     * @param int|array $id The class ID (or array of IDs for bulk update).
     * @param string $classId The new class ID/code.
     * @param string $className The new class name.
     * @param string $status The new status.
     * @param string|null $levelId The new level ID.
     * @return array The result of the operation.
     * @throws \Exception If validation fails.
     */
    public function updateClass(int|array $id , string $classId, string $className, string $status, ?string $levelId = null): array
    {
        if (! is_array($id)) {
            if (empty($className)) {
                throw new \Exception('Class name is required');
            }

            if (empty($id)) {
                throw new \Exception('ID of class is required');
            }

            if (empty($classId)) {
                throw new \Exception('Class ID is required');
            }
            
            /* $class = $this->repo->getById($classId);

            echo json_encode($class);exit;
            if (!$class) {
                throw new \Exception('Class not found');
            } */

            $this->checkIfClassIdExists($classId, (int)$id);
        }
               

        $this->repo->update($id, $classId, $className, $status, $levelId);

        return [
            'success' => true,
            'message' => 'Class updated successfully'
        ];
    }

    /**
     * Delete multiple classes.
     *
     * @param array $ids The list of class IDs to delete.
     * @return array The result of the bulk operation.
     * @throws \Exception If no IDs provided.
     */
    public function deleteClasses(array $ids): array
    {
        if (empty($ids)) {
            throw new \Exception('No class IDs provided for deletion');
        }

        $results = [
            'success' => true,
            'message' => 'Selected classes processed',
            'deleted_count' => 0,
            'failed_count' => 0,
            'failures' => []
        ];

        foreach ($ids as $id) {
            try {
                $class = $this->repo->getById((int)$id);
                if (!$class) {
                    $results['failed_count']++;
                    $results['failures'][] = ['id' => $id, 'message' => 'Class not found'];
                    continue;
                }

                $this->repo->delete((int)$id);
                $results['deleted_count']++;
            } catch (\Exception $e) {
                $results['failed_count']++;
                $results['failures'][] = ['id' => $id, 'message' => $e->getMessage()];
                $results['success'] = false;
            }
        }

        if ($results['deleted_count'] > 0 && $results['failed_count'] === 0) {
            $results['message'] = 'All selected classes deleted successfully';
        } elseif ($results['deleted_count'] > 0 && $results['failed_count'] > 0) {
            $results['message'] = 'Some classes deleted, some failed';
        } elseif ($results['deleted_count'] === 0 && $results['failed_count'] > 0) {
            $results['message'] = 'No classes were deleted due to errors';
        }

        return $results;
    }
}
