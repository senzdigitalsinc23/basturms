<?php

namespace App\Services;

use App\Repositories\ClassSubjectRepository;
use App\Repositories\ClassRepository;
use App\Repositories\SubjectRepository;

/**
 * Service for managing class-subject assignments.
 */
class ClassSubjectService
{
    private ClassSubjectRepository $repo;
    private ClassRepository $classRepo;
    private SubjectRepository $subjectRepo;

    public function __construct()
    {
        $this->repo = new ClassSubjectRepository();
        $this->classRepo = new ClassRepository();
        $this->subjectRepo = new SubjectRepository();
    }

    /**
     * Assign multiple subjects to multiple classes.
     *
     * @param array $classIds The list of class IDs.
     * @param array $subjectIds The list of subject IDs.
     * @param string $assignedBy The user ID performing the assignment.
     * @return array The results of the assignment process.
     * @throws \Exception If no valid assignments provided.
     */
    public function assignSubjectToClass(array $classIds, array $subjectIds, string $assignedBy): array
    {
        $successfulAssignments = [];
        $failedAssignments = [];
        $assignmentsToCommit = [];

        // OPTIMIZATION: Batch check existence before loops
        $existingClasses = $this->classRepo->existsBatch($classIds);
        $existingSubjects = $this->subjectRepo->existsBatch($subjectIds);

        foreach ($classIds as $classId) {
            // Use pre-fetched data instead of querying in loop
            if (!isset($existingClasses[$classId])) {
                foreach ($subjectIds as $subjectId) {
                    $failedAssignments[] = [
                        'class_id' => $classId,
                        'subject_id' => $subjectId,
                        'reason' => 'Class not found'
                    ];
                }
                continue;
            }

            foreach ($subjectIds as $subjectId) {
                // Use pre-fetched data instead of querying in loop
                if (!isset($existingSubjects[$subjectId])) {
                    $failedAssignments[] = [
                        'class_id' => $classId,
                        'subject_id' => $subjectId,
                        'reason' => 'Subject not found'
                    ];
                    continue;
                }
                if ($this->repo->exists((int)$classId, (int)$subjectId)) {
                    $failedAssignments[] = [
                        'class_id' => $classId,
                        'subject_id' => $subjectId,
                        'reason' => 'Subject already assigned to class'
                    ];
                    continue;
                }

                $assignmentsToCommit[] = [
                    'class_id' => $classId,
                    'subject_id' => $subjectId
                ];
            }
        }

        if (empty($assignmentsToCommit)) {
            if (empty($failedAssignments)) {
                throw new \Exception('No valid assignments provided.');
            } else {
                return [
                    'success' => false,
                    'message' => 'No subjects were assigned successfully.',
                    'data' => [
                        'assigned_count' => 0,
                        'failed_assignments' => $this->mapFailedAssignmentsToNames($failedAssignments)
                    ]
                ];
            }
        }

        $result = $this->repo->bulkAssign($assignmentsToCommit, $assignedBy);

        foreach ($assignmentsToCommit as $assignment) {
            // Assuming all committed assignments were successful if bulk query passed
            $successfulAssignments[] = $assignment;
        }

        return [
            'success' => true,
            'message' => 'Assignments processed',
            'data' => [
                'assigned_count' => $result['rows_affected'],
                'successful_assignments' => $this->mapSuccessfulAssignmentsToNames($successfulAssignments),
                'failed_assignments' => $this->mapFailedAssignmentsToNames($failedAssignments)
            ]
        ];
    }

    /**
     * Map successful assignments to their names for response.
     *
     * @param array $assignments The successful assignments.
     * @return array The mapped assignments.
     */
    private function mapSuccessfulAssignmentsToNames(array $assignments): array
    {
        $mapped = [];
        $subjectIds = array_column($assignments, 'subject_id');
        $subjects = $this->subjectRepo->findByIds($subjectIds);
        $subjectMap = array_column($subjects, 'subject_name', 'id');

        $classIds = array_column($assignments, 'class_id');
        $classes = $this->classRepo->findByIds($classIds);
        $classMap = array_column($classes, 'class_name', 'id');

        foreach ($assignments as $assignment) {
            $mapped[] = [
                'class_id' => $assignment['class_id'],
                'class_name' => $classMap[$assignment['class_id']] ?? 'Unknown Class',
                'subject_id' => $assignment['subject_id'],
                'subject_name' => $subjectMap[$assignment['subject_id']] ?? 'Unknown Subject'
            ];
        }
        return $mapped;
    }

    /**
     * Map failed assignments to their names and reasons for response.
     *
     * @param array $assignments The failed assignments.
     * @return array The mapped failed assignments.
     */
    private function mapFailedAssignmentsToNames(array $assignments): array
    {
        $mapped = [];
        $subjectIds = array_column($assignments, 'subject_id');
        $subjects = $this->subjectRepo->findByIds($subjectIds);
        $subjectMap = array_column($subjects, 'subject_name', 'id');

        $classIds = array_column($assignments, 'class_id');
        $classes = $this->classRepo->findByIds($classIds);
        $classMap = array_column($classes, 'class_name', 'id');

        foreach ($assignments as $assignment) {
            $mapped[] = [
                'class_id' => $assignment['class_id'],
                'class_name' => $classMap[$assignment['class_id']] ?? 'Unknown Class',
                'subject_id' => $assignment['subject_id'],
                'subject_name' => $subjectMap[$assignment['subject_id']] ?? 'Unknown Subject',
                'reason' => $assignment['reason']
            ];
        }
        return $mapped;
    }

    // Remove this method as its functionality is merged into assignSubjectToClass
    /*
    public function bulkAssignSubjectsToClass(int $classId, array $subjectIds, string $assignedBy): array
    {
        if (!$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        $validSubjectIds = [];
        $invalidSubjectIds = [];
        foreach ($subjectIds as $subjectId) {
            if ($this->subjectRepo->exists($subjectId)) {
                if (!$this->repo->exists($classId, $subjectId)) {
                    $validSubjectIds[] = $subjectId;
                } else {
                    $invalidSubjectIds[$subjectId] = 'Subject already assigned';
                }
            } else {
                $invalidSubjectIds[$subjectId] = 'Subject not found';
            }
        }

        if (empty($validSubjectIds)) {
            throw new \Exception('No valid subjects to assign or all already assigned.');
        }

        $result = $this->repo->bulkAssignSubjectsToClass($classId, $validSubjectIds, $assignedBy);

        return [
            'success' => true,
            'message' => 'Subjects assigned to class successfully',
            'data' => [
                'assigned_count' => $result['rows_affected'],
                'assigned_subjects' => $result['subject_ids'],
                'failed_assignments' => $invalidSubjectIds
            ]
        ];
    }
    */

    /**
     * Get all subjects assigned to a class.
     *
     * @param int $classId The class ID.
     * @return array The list of assigned subjects.
     * @throws \Exception If class not found.
     */
    public function getClassSubjects(int $classId): array
    {
        
        if ($classId != 0 && !$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        $subjects = $this->repo->getSubjectsByClass($classId);

        return [
            'success' => true,
            'data' => $subjects
        ];
    }

    /**
     * Get all classes assigned to a subject.
     *
     * @param int $subjectId The subject ID.
     * @return array The list of assigned classes.
     * @throws \Exception If subject not found.
     */
    public function getSubjectClasses(int $subjectId): array
    {
        if (!$this->subjectRepo->exists($subjectId)) {
            throw new \Exception('Subject not found');
        }

        $classes = $this->repo->getClassesBySubject($subjectId);

        return [
            'success' => true,
            'data' => $classes
        ];
    }

    /**
     * Remove a subject assignment from a class.
     *
     * @param int $classId The class ID.
     * @param int $subjectId The subject ID.
     * @return array The result of the operation.
     * @throws \Exception If assignment not found.
     */
    public function removeSubjectFromClass(int $classId, int $subjectId): array
    {
        if (!$this->repo->exists($classId, $subjectId)) {
            throw new \Exception('Subject not assigned to this class');
        }

        $this->repo->removeSubjectFromClass($classId, $subjectId);

        return [
            'success' => true,
            'message' => 'Subject removed from class successfully'
        ];
    }

    /**
     * Remove multiple subjects from a class.
     *
     * @param int $classId The class ID.
     * @param array $subjectIds The list of subject IDs to remove.
     * @return array The result of the operation.
     * @throws \Exception If class not found or no subjects to unassign.
     */
    public function bulkRemoveSubjectsFromClass(int $classId, array $subjectIds): array
    {
        if (!$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        $removableSubjectIds = [];
        $nonExistentSubjectIds = [];

        foreach ($subjectIds as $subjectId) {
            if ($this->repo->exists($classId, (int)$subjectId)) {
                $removableSubjectIds[] = (int)$subjectId;
            } else {
                $nonExistentSubjectIds[] = (int)$subjectId;
            }
        }

        if (empty($removableSubjectIds)) {
            throw new \Exception('No subjects to unassign or all already unassigned.');
        }

        $result = $this->repo->bulkRemoveSubjectsFromClass($classId, $removableSubjectIds);

        return [
            'success' => true,
            'message' => 'Subjects unassigned from class successfully',
            'data' => [
                'unassigned_count' => $result['rows_affected'],
                'unassigned_subjects' => $result['subject_ids'],
                'failed_unassignments' => $nonExistentSubjectIds
            ]
        ];
    }

    /**
     * List all class-subject assignments.
     *
     * @return array The list of all assignments.
     */
    public function listAll(): array
    {
        $data = $this->repo->getAll();

        return [
            'success' => true,
            'data' => $data
        ];
    }
}
