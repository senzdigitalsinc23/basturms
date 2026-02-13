<?php

namespace App\Services;

use App\Repositories\TeacherSubjectRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\ClassRepository;

/**
 * Service for managing teacher-subject assignments.
 */
class TeacherSubjectService
{
    private TeacherSubjectRepository $repo;
    private SubjectRepository $subjectRepo;
    private ClassRepository $classRepo;

    public function __construct()
    {
        $this->repo = new TeacherSubjectRepository();
        $this->subjectRepo = new SubjectRepository();
        $this->classRepo = new ClassRepository();
    }

    /**
     * Assign a subject to a teacher.
     *
     * @param string $staffId The teacher's staff ID.
     * @param int $subjectId The subject ID.
     * @param int|null $classId Optional class ID.
     * @param string|null $academicYear Optional academic year.
     * @param string $assignedBy The user ID performing the assignment.
     * @return array The result of the assignment.
     * @throws \Exception If validation fails or assignment exists.
     */
    public function assignSubjectToTeacher(string $staffId, int $subjectId, ?int $classId = null, ?string $academicYear = null, string $assignedBy = 'system'): array
    {
        if (empty($staffId)) {
            throw new \Exception('Staff ID is required');
        }

        if (!$this->subjectRepo->exists($subjectId)) {
            throw new \Exception('Subject not found');
        }

        if ($classId && !$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        if ($this->repo->exists($staffId, $subjectId, $classId, $academicYear)) {
            throw new \Exception('Subject already assigned to this teacher for the specified class and academic year');
        }

        $result = $this->repo->assign($staffId, $subjectId, $classId, $academicYear, $assignedBy);

        return [
            'success' => true,
            'message' => 'Subject assigned to teacher successfully',
            'data' => $result
        ];
    }

    /**
     * Get all subjects assigned to a teacher.
     *
     * @param string $staffId The teacher's staff ID.
     * @param string|null $academicYear Optional academic year filter.
     * @return array The list of assigned subjects.
     */
    public function getTeacherSubjects(string $staffId, ?string $academicYear = null): array
    {
        $subjects = $this->repo->getTeacherSubjects($staffId, $academicYear);

        return [
            'success' => true,
            'data' => $subjects
        ];
    }

    /**
     * Get all teachers assigned to a subject.
     *
     * @param int $subjectId The subject ID.
     * @param int|null $classId Optional class ID.
     * @param string|null $academicYear Optional academic year.
     * @return array The list of teachers.
     * @throws \Exception If validation fails.
     */
    public function getSubjectTeachers(int $subjectId, ?int $classId = null, ?string $academicYear = null): array
    {
        if (!$this->subjectRepo->exists($subjectId)) {
            throw new \Exception('Subject not found');
        }

        if ($classId && !$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        $teachers = $this->repo->getSubjectTeachers($subjectId, $classId, $academicYear);

        return [
            'success' => true,
            'data' => $teachers
        ];
    }

    /**
     * Remove a subject assignment from a teacher.
     *
     * @param int $assignmentId The ID of the assignment to remove.
     * @return array The result of the operation.
     */
    public function removeSubjectFromTeacher(int $assignmentId): array
    {
        $this->repo->remove($assignmentId);

        return [
            'success' => true,
            'message' => 'Subject removed from teacher successfully'
        ];
    }

    /**
     * List all teacher-subject assignments.
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
