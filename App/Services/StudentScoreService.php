<?php

namespace App\Services;

use App\Repositories\StudentScoreRepository;
use App\Repositories\SubjectRepository;
use App\Repositories\ClassRepository;

/**
 * Service for managing student scores.
 */
class StudentScoreService
{
    private StudentScoreRepository $repo;
    private SubjectRepository $subjectRepo;
    private ClassRepository $classRepo;

    public function __construct()
    {
        $this->repo = new StudentScoreRepository();
        $this->subjectRepo = new SubjectRepository();
        $this->classRepo = new ClassRepository();
    }

    /**
     * Add or update a student's score for a subject.
     *
     * @param string $studentNo The student number.
     * @param int $subjectId The subject ID.
     * @param string $academicYear The academic year.
     * @param string $term The term.
     * @param int $classId The class ID.
     * @param float $score The score value.
     * @param string|null $grade The grade (optional).
     * @param string|null $remarks Remarks (optional).
     * @param string $enteredBy The user ID entering the score.
     * @return array The result of the operation.
     * @throws \Exception If validation fails.
     */
    public function addScore(string $studentNo, int $subjectId, string $academicYear, string $term, int $classId, float $score, ?string $grade = null, ?string $remarks = null, string $enteredBy = 'system'): array
    {
        if (empty($studentNo) || !$subjectId || empty($academicYear) || empty($term) || !$classId) {
            throw new \Exception('All required fields must be provided');
        }

        if ($score < 0 || $score > 100) {
            throw new \Exception('Score must be between 0 and 100');
        }

        if (!$this->subjectRepo->exists($subjectId)) {
            throw new \Exception('Subject not found');
        }

        if (!$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        $result = $this->repo->addScore($studentNo, $subjectId, $academicYear, $term, $classId, $score, $grade, $remarks, $enteredBy);

        return [
            'success' => true,
            'message' => 'Score recorded successfully',
            'data' => $result
        ];
    }

    /**
     * Add an activity score for a student.
     *
     * @param string $studentNo
     * @param int $subjectId
     * @param int $activityId
     * @param int $classId
     * @param string $academicYear
     * @param string $term
     * @param float $score
     * @param string $enteredBy
     * @return array
     */
    public function addActivityScore(
        string $studentNo, 
        int $subjectId, 
        int $activityId, 
        int $classId, 
        string $academicYear, 
        string $term, 
        float $score, 
        string $enteredBy
    ): array {
        if (empty($studentNo) || !$subjectId || !$activityId || !$classId || empty($academicYear) || empty($term)) {
            throw new \Exception('All required fields must be provided');
        }

        if ($score < 0 || $score > 100) {
            throw new \Exception('Score must be between 0 and 100');
        }

        // Validate existences
        if (!$this->subjectRepo->exists($subjectId)) {
            throw new \Exception('Subject not found');
        }
        if (!$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        return $this->repo->addActivityScore(
            $studentNo,
            $subjectId,
            $activityId,
            $classId,
            $academicYear,
            $term,
            $score,
            $enteredBy
        );
    }

    /**
     * Get scores for a specific student.
     *
     * @param string $studentNo The student number.
     * @param string|null $academicYear Optional academic year filter.
     * @param string|null $term Optional term filter.
     * @return array The list of scores.
     */
    public function getStudentScores(string $studentNo, ?string $academicYear = null, ?string $term = null): array
    {
        $scores = $this->repo->getStudentScores($studentNo, $academicYear, $term);

        return [
            'success' => true,
            'data' => $scores
        ];
    }

    /**
     * Get scores for an entire class.
     *
     * @param int $classId The class ID.
     * @param string $academicYear The academic year.
     * @param string $term The term.
     * @param int|null $subjectId Optional subject filter.
     * @return array The list of scores.
     * @throws \Exception If class not found.
     */
    public function getClassScores(int $classId, string $academicYear, string $term, ?int $subjectId = null): array
    {
        if (!$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        if ($subjectId && !$this->subjectRepo->exists($subjectId)) {
            throw new \Exception('Subject not found');
        }

        $scores = $this->repo->getClassScores($classId, $academicYear, $term, $subjectId);

        return [
            'success' => true,
            'data' => $scores
        ];
    }

    /**
     * Get scores for a specific subject.
     *
     * @param int $subjectId The subject ID.
     * @param string $academicYear The academic year.
     * @param string $term The term.
     * @param int|null $classId Optional class filter.
     * @return array The list of scores.
     * @throws \Exception If subject or class not found.
     */
    public function getSubjectScores(int $subjectId, string $academicYear, string $term, ?int $classId = null): array
    {
        if (!$this->subjectRepo->exists($subjectId)) {
            throw new \Exception('Subject not found');
        }

        if ($classId && !$this->classRepo->exists($classId)) {
            throw new \Exception('Class not found');
        }

        $scores = $this->repo->getSubjectScores($subjectId, $academicYear, $term, $classId);

        return [
            'success' => true,
            'data' => $scores
        ];
    }

    /**
     * Add or update scores in bulk.
     *
     * @param array $scores The list of score entries.
     * @param string $enteredBy The user ID entering the scores.
     * @return array The summary of the operation.
     * @throws \Exception If no scores provided.
     */
    public function bulkAddScores(array $scores, string $enteredBy = 'system'): array
    {
        if (empty($scores)) {
            throw new \Exception('No scores provided');
        }

        $result = $this->repo->bulkAddScores($scores, $enteredBy);

        return [
            'success' => true,
            'summary' => $result
        ];
    }

    /**
     * Import scores from a CSV file.
     *
     * @param string $filePath The path to the CSV file.
     * @param string $enteredBy The user ID performing the import.
     * @return array The summary of the import.
     * @throws \Exception If file not found or invalid CSV.
     */
    public function importFromCSV(string $filePath, string $enteredBy = 'system'): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception('CSV file not found');
        }

        $scores = [];
        $errors = [];
        $lineNumber = 0;

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 0, ',');
            if (!$headers) {
                fclose($handle);
                throw new \Exception('Invalid CSV file');
            }

            $headerMap = array_flip($headers);
            $requiredColumns = ['student_no', 'subject_id', 'academic_year', 'term', 'class_id', 'score'];
            foreach ($requiredColumns as $col) {
                if (!isset($headerMap[$col])) {
                    fclose($handle);
                    throw new \Exception('Missing required column: ' . $col);
                }
            }

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $lineNumber++;
                if (count($row) !== count($headers)) {
                    $errors[] = "Line " . ($lineNumber + 1) . ": Column count mismatch";
                    continue;
                }

                $data = array_combine($headers, $row);

                $scoreEntry = [
                    'student_no' => trim((string)($data['student_no'] ?? '')),
                    'subject_id' => (int)trim((string)($data['subject_id'] ?? 0)),
                    'academic_year' => trim((string)($data['academic_year'] ?? '')),
                    'term' => trim((string)($data['term'] ?? '')),
                    'class_id' => (int)trim((string)($data['class_id'] ?? 0)),
                    'score' => (float)trim((string)($data['score'] ?? 0)),
                    'grade' => trim((string)($data['grade'] ?? '')) ?: null,
                    'remarks' => trim((string)($data['remarks'] ?? '')) ?: null,
                ];

                if (empty($scoreEntry['student_no']) || !$scoreEntry['subject_id'] || !isset($data['score'])) {
                    $errors[] = "Line " . ($lineNumber + 1) . ": Missing required fields";
                    continue;
                }

                if ($scoreEntry['score'] < 0 || $scoreEntry['score'] > 100) {
                    $errors[] = "Line " . ($lineNumber + 1) . ": Invalid score value (must be 0-100)";
                    continue;
                }

                $scores[] = $scoreEntry;
            }

            fclose($handle);
        } else {
            throw new \Exception('Could not open CSV file');
        }

        if (empty($scores)) {
            throw new \Exception('No valid scores found in CSV');
        }

        $result = $this->repo->bulkAddScores($scores, $enteredBy);

        if (!empty($errors)) {
            $result['warnings'] = $errors;
        }

        return [
            'success' => true,
            'summary' => $result
        ];
    }

    /**
     * Delete a score entry by its ID.
     *
     * @param int $id The score entry ID.
     * @return array The result of the operation.
     */
    public function deleteScore(int $id): array
    {
        $this->repo->deleteScore($id);

        return [
            'success' => true,
            'message' => 'Score deleted successfully'
        ];
    }

    /**
     * Get summary reports for students.
     * 
     * @param string|null $studentNo
     * @param string|null $academicYear
     * @param string|null $term
     * @return array
     */
    public function getSummaryReports(?string $studentNo = null, ?string $academicYear = null, ?string $term = null): array
    {
        $reports = $this->repo->getSummaryReports($studentNo, $academicYear, $term);
        
        return [
            'success' => true,
            'data' => $reports
        ];
    }

    /**
     * Get aggregated student reports for students.
     * 
     * @param string|null $studentNo
     * @param string|null $academicYear
     * @param string|null $term
     * @return array
     */
    public function getStudentReports(?string $studentNo = null, ?string $academicYear = null, ?string $term = null, ?int $classId = null, ?string $search = null): array
    {
        $reports = $this->repo->getStudentReports($studentNo, $academicYear, $term, $classId, $search);
        
        return [
            'success' => true,
            'data' => $reports
        ];
    }
}
