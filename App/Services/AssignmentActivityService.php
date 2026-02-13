<?php

namespace App\Services;

use App\Models\AssignmentActivity;
use App\Models\Activity;
use App\Exceptions\ValidationException;
use App\Core\Database;
use PDO;
use Exception;

/**
 * Service for managing assignment activities and generating individual activities.
 */
class AssignmentActivityService
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
     * Create a new assignment activity.
     *
     * @param array $data The activity data.
     * @param string $userId The ID of the user creating the activity.
     * @return array The result of the creation.
     * @throws ValidationException If validation fails.
     */
    public function createActivity(array $data, string $userId): array
    {
        // Auto-generate activity_id
        $data['activity_id'] = 'ACT' . strtoupper(substr(uniqid(), -8));

        $validation = $this->validationService->validate($data, [
            'activity_id' => 'required',
            'act_name' => 'required',
            'expected_per_term' => 'required|numeric',
            'weight' => 'required|numeric',
            'academic_year' => 'required',
            'term' => 'required'
        ]);

        if (!$validation['success']) {
            throw new ValidationException((array)$validation['errors'], 'Validation failed');
        }

        // Check for duplicate activity_id
        $existing = AssignmentActivity::where('activity_id', (string)$data['activity_id']);
        if ($existing) {
            throw new ValidationException(['activity_id' => ['Activity ID already exists']], 'Validation failed');
        }

        $data['added_by'] = $userId;
        $data['added_on'] = date('Y-m-d H:i:s');
        $data['status'] = 'active';
        $data['is_standalone'] = $data['is_standalone'] ?? 0;

        $activity = AssignmentActivity::create($data);

        // Generate individual activities based on expected_per_term
        $expectedCount = (int)$data['expected_per_term'];

        if ($expectedCount === 1) {
            Activity::create([
                'act_id' => $data['activity_id'],
                'sub_activity_id' => $data['activity_id'],
                'activity_name' => $data['act_name'],
                'status' => 'active',
                'added_on' => $data['added_on']
            ]);
        }else {
            for ($i = 1; $i <= $expectedCount; $i++) {
                Activity::create([
                    'act_id' => $data['activity_id'],
                    'activity_name' => $data['act_name'] . ' ' . $i,
                    'sub_activity_id' => $data['activity_id'] . '' . (string)$i,
                    'status' => 'active',
                    'added_on' => $data['added_on']
                ]);
            }
        }
        

        return [
            'success' => true,
            'message' => 'Assignment activity created successfully and individual activities generated',
            'data' => $activity->toArray()
        ];
    }

    /**
     * List assignment activities, optionally filtered by academic year, term and status.
     *
     * @param string|null $academicYear Optional academic year filter.
     * @param string|null $term Optional term filter.
     * @param string|null $status Optional status filter (defaults to 'active').
     * @return array The list of activities.
     */
    public function listActivities(?string $academicYear = null, ?string $term = null, ?string $status = 'active'): array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM assignment_activities WHERE 1=1";
        $params = [];

        if ($academicYear) {
            $sql .= " AND academic_year = :academic_year";
            $params['academic_year'] = $academicYear;
        }

        if ($term) {
            $sql .= " AND term = :term";
            $params['term'] = $term;
        }

        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($activities)) {
            $activityIds = array_column($activities, 'activity_id');
            $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
            
            $sql = "
                SELECT ca.act_id, c.id, c.class_id, c.class_name 
                FROM class_activity_assignment ca
                JOIN classes c ON ca.class_id = c.class_id
                WHERE ca.act_id IN ($placeholders)
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($activityIds);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group assignments by activity_id
            $assignmentsByActivity = [];
            foreach ($assignments as $assignment) {
                $actId = $assignment['act_id'];
                if (!isset($assignmentsByActivity[$actId])) {
                    $assignmentsByActivity[$actId] = [];
                }
                $assignmentsByActivity[$actId][] = [
                    'id' => $assignment['id'],
                    'class_id' => $assignment['class_id'],
                    'class_name' => $assignment['class_name']
                ];
            }
            
            // Map assignments back to activities
            foreach ($activities as &$activity) {
                $activity['assigned_classes'] = $assignmentsByActivity[$activity['activity_id']] ?? [];
            }
        }

        return [
            'success' => true,
            'data' => $activities
        ];
    }

    /**
     * Update an assignment activity.
     *
     * @param string $activityId The activity ID.
     * @param array $data The updated activity data.
     * @return array The result of the update.
     * @throws ValidationException If validation fails or activity not found.
     */
    public function updateActivity(string $activityId, array $data): array
    {
        $existing = AssignmentActivity::where('activity_id', $activityId);
        if (!$existing) {
            throw new ValidationException(['activity_id' => ['Activity not found']], 'Validation failed');
        }

        $validation = $this->validationService->validate($data, [
            'act_name' => 'required',
            'expected_per_term' => 'required|numeric',
            'weight' => 'required|numeric'
        ]);

        if (!$validation['success']) {
            throw new ValidationException((array)$validation['errors'], 'Validation failed');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE assignment_activities 
            SET act_name = ?, expected_per_term = ?, weight = ? 
            WHERE activity_id = ?
        ");
        $stmt->execute([
            $data['act_name'],
            $data['expected_per_term'],
            $data['weight'],
            $activityId
        ]);

        return [
            'success' => true,
            'message' => 'Assignment activity updated successfully'
        ];
    }

    /**
     * Soft delete an assignment activity (set status to inactive).
     *
     * @param string $activityId The activity ID.
     * @return array The result of the operation.
     * @throws ValidationException If activity not found.
     */
    public function softDelete(string $activityId): array
    {
        $existing = AssignmentActivity::where('activity_id', $activityId);
        if (!$existing) {
            throw new ValidationException(['activity_id' => ['Activity not found']], 'Validation failed');
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("UPDATE assignment_activities SET status = 'inactive' WHERE activity_id = ?");
            $stmt->execute([$activityId]);

            $stmt = $db->prepare("UPDATE activities SET status = 'inactive' WHERE act_id = ?");
            $stmt->execute([$activityId]);

            $stmt = $db->prepare("UPDATE class_activity_assignment SET status = 'inactive' WHERE act_id = ?");
            $stmt->execute([$activityId]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => 'Assignment activity and related activities deactivated successfully'
        ];
    }

    /**
     * Permanent delete an assignment activity.
     *
     * @param string $activityId The activity ID.
     * @return array The result of the deletion.
     * @throws ValidationException If activity not found.
     */
    public function permanentDelete(string $activityId): array
    {
        $existing = AssignmentActivity::where('activity_id', $activityId);
        if (!$existing) {
            throw new ValidationException(['activity_id' => ['Activity not found']], 'Validation failed');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM assignment_activities WHERE activity_id = ?");
        $stmt->execute([$activityId]);

        return [
            'success' => true,
            'message' => 'Assignment activity permanently deleted'
        ];
    }

    /**
     * Reactivate an inactive assignment activity.
     *
     * @param string $activityId The activity ID.
     * @return array The result of the reactivation.
     * @throws ValidationException If activity not found.
     */
    public function activate(string $activityId): array
    {
        $existing = AssignmentActivity::where('activity_id', $activityId);
        if (!$existing) {
            throw new ValidationException(['activity_id' => ['Activity not found']], 'Validation failed');
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("UPDATE assignment_activities SET status = 'active' WHERE activity_id = ?");
            $stmt->execute([$activityId]);

            $stmt = $db->prepare("UPDATE activities SET status = 'active' WHERE act_id = ?");
            $stmt->execute([$activityId]);

            $stmt = $db->prepare("UPDATE class_activity_assignment SET status = 'active' WHERE act_id = ?");
            $stmt->execute([$activityId]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => 'Assignment activity and related activities reactivated successfully'
        ];
    }
}
