<?php

namespace App\Services;

use App\Repositories\RankingRepository;
use App\Repositories\ClassRepository;

/**
 * Service for retrieving ranking data.
 */
class RankingService
{
    private RankingRepository $repo;
    private ClassRepository $classRepo;

    public function __construct()
    {
        $this->repo      = new RankingRepository();
        $this->classRepo = new ClassRepository();
    }

    /**
     * Get subject-level rankings for a class in a given term.
     *
     * @param int    $classId
     * @param string $academicYear
     * @param string $term
     * @param int|null $subjectId Optional – filter to a single subject
     * @return array
     * @throws \Exception
     */
    public function getSubjectRankings(int $classId, string $academicYear, string $term, ?int $subjectId = null): array
    {
        if (empty($academicYear) || empty($term)) {
            throw new \Exception('Academic year and term are required');
        }

        if (!$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        $data = $this->repo->getSubjectRankings($classId, $academicYear, $term, $subjectId);

        return [
            'success' => true,
            'data'    => $data,
        ];
    }

    /**
     * Get overall class rankings for a term.
     *
     * @param int    $classId
     * @param string $academicYear
     * @param string $term
     * @return array
     * @throws \Exception
     */
    public function getClassRankings(int $classId, string $academicYear, string $term): array
    {
        if (empty($academicYear) || empty($term)) {
            throw new \Exception('Academic year and term are required');
        }

        if (!$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        $data = $this->repo->getClassRankings($classId, $academicYear, $term);

        return [
            'success' => true,
            'data'    => $data,
        ];
    }

    /**
     * Get a single student's full ranking summary for a term.
     *
     * @param string $studentNo
     * @param string $academicYear
     * @param string $term
     * @return array
     * @throws \Exception
     */
    public function getStudentRanking(string $studentNo, string $academicYear, string $term): array
    {
        if (empty($studentNo) || empty($academicYear) || empty($term)) {
            throw new \Exception('Student number, academic year, and term are required');
        }

        $data = $this->repo->getStudentTermRanking($studentNo, $academicYear, $term);

        if (!$data) {
            throw new \RuntimeException("No ranking data found for student '{$studentNo}' in {$academicYear} {$term}");
        }

        return [
            'success' => true,
            'data'    => $data,
        ];
    }

    /**
     * Get rankings for all students in a specific school level.
     *
     * @param string $levelId
     * @param string $academicYear
     * @param string $term
     * @return array
     * @throws \Exception
     */
    public function getLevelRankings(string $levelId, string $academicYear, string $term): array
    {
        if (empty($levelId) || empty($academicYear) || empty($term)) {
            throw new \Exception('School level, academic year, and term are required');
        }

        $data = $this->repo->getLevelRankings($levelId, $academicYear, $term);

        return [
            'success' => true,
            'data'    => $data,
        ];
    }

    /**
     * Get rankings for all students in the entire school.
     *
     * @param string $academicYear
     * @param string $term
     * @return array
     * @throws \Exception
     */
    public function getSchoolRankings(string $academicYear, string $term): array
    {
        if (empty($academicYear) || empty($term)) {
            throw new \Exception('Academic year and term are required');
        }

        $data = $this->repo->getSchoolRankings($academicYear, $term);

        return [
            'success' => true,
            'data'    => $data,
        ];
    }
}
