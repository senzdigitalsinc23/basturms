<?php

namespace App\Services;

use App\Models\ClassActivityAssignment;
use App\Models\AssignmentActivity;
use App\Models\Activity;
use App\Exceptions\ValidationException;
use App\Core\Database;
use App\Core\Session;
use Database\ORM\Model;
use JsonException;
use PDO;

/**
 * Service for assigning and unassigning activities to classes.
 */
class ClassActivityAssignmentService
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
     * Assigns an activity to a class.
     *
     * @param array $data The assignment data.
     * @param string $userId The ID of the user performing the assignment.
     * @return array The result of the operation.
     * @throws ValidationException If validation fails or activity not found.
     */
    public function assignActivity(array $data, string $userId): array
    {
        $validation = $this->validationService->validate($data, [
            'class_id' => 'required',
            'act_id' => 'required' // This is the activity_id from assignment_activities
        ]);

        if (!$validation['success']) {
            throw new ValidationException((array)$validation['errors'], 'Validation failed');
        }

        // Check if activity exists
        $activity = AssignmentActivity::where('activity_id', (string)$data['act_id']);
        
        if (!$activity) {
            throw new ValidationException(['act_id' => ['Assignment activity not found']], 'Validation failed');
        }

        // Check if already assigned
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM class_activity_assignment WHERE class_id = ? AND act_id = ?");
        $stmt->execute([$data['class_id'], $data['act_id']]);
        if ($stmt->fetch()) {
            throw new ValidationException(['act_id' => ['Activity is already assigned to this class']], 'Validation failed');
        }

        $data['assigned_by'] = $userId;
        $data['assigned_on'] = date('Y-m-d H:i:s');
        $data['status'] = 'active';
        $data['academic_year'] = Session::get('user')['academic_year'];
        $data['term'] = Session::get('user')['term'];

        $assignment = ClassActivityAssignment::create($data);

        return [
            'success' => true,
            'message' => 'Activity successfully assigned to class',
            'data' => $assignment->toArray()
        ];
    }

    /**
     * Unassigns an activity from a class.
     *
     * @param string $classId The class ID.
     * @param string $actId The activity ID.
     * @return array The result of the operation.
     * @throws ValidationException If assignment not found.
     */
    public function unassignActivity(string $classId, string $actId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM class_activity_assignment WHERE class_id = ? AND act_id = ?");
        $stmt->execute([$classId, $actId]);

        if ($stmt->rowCount() === 0) {
            throw new ValidationException(['act_id' => ['Assignment not found']], 'Validation failed');
        }

        return [
            'success' => true,
            'message' => 'Activity unassigned from class successfully'
        ];
    }

    /**
     * Lists all activities assigned to a class.
     *
     * @param string $classId The class ID.
     * @return array The list of assigned activities.
     */
    public function listClassActivities(string $classId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT aa.*, ca.assigned_on, ca.assigned_by
            FROM assignment_activities aa
            JOIN class_activity_assignment ca ON aa.activity_id = ca.act_id
            WHERE ca.class_id = ?
        ");
        $stmt->execute([$classId]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $activities
        ];
    }

    public function listIndividualClassActivities(string $classId): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT aa.*, ca.assigned_on, ca.assigned_by
            FROM assignment_activities aa
            JOIN class_activity_assignment ca ON aa.activity_id = ca.act_id
            WHERE ca.class_id = ?
        ");
        $stmt->execute([$classId]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $activities
        ];
    }
}
