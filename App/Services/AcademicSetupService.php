<?php

namespace App\Services;

use App\Repositories\AcademicSetupRepository;
use App\Repositories\AcademicYearRepository;
use App\Exceptions\ValidationException;
use PDOException;

/**
 * Service class for managing academic setup operations.
 * 
 * Handles business logic for academic years, terms, and their configurations.
 * Provides validation, error handling, and transaction management.
 */
class AcademicSetupService
{
    private AcademicSetupRepository $setupRepository;
    private AcademicYearRepository $yearRepository;
    private ValidationService $validationService;

    /**
     * AcademicSetupService constructor.
     *
     * @param AcademicSetupRepository $setupRepository The repository for academic setup (terms) data access
     * @param AcademicYearRepository $yearRepository The repository for academic year data access
     * @param ValidationService $validationService The validation service for input validation
     */
    public function __construct(
        AcademicSetupRepository $setupRepository,
        AcademicYearRepository $yearRepository,
        ValidationService $validationService
    ) {
        $this->setupRepository = $setupRepository;
        $this->yearRepository = $yearRepository;
        $this->validationService = $validationService;
    }

    public function findByName(string $academicYearName): ?array
    {
        return $this->yearRepository->getByAcademicYear($academicYearName);
    }

    /**
     * Sets the number of terms for an academic year.
     *
     * @param string $academicYear The academic year (e.g., '2023-2024')
     * @param int $numberOfTerms The number of terms (1-3)
     * @return array Response array with success status, message, and data
     * @throws ValidationException If validation fails
     * @throws \RuntimeException If database operation fails
     */
    public function setNumberOfTerms(string $academicYear, int $numberOfTerms): array
    {
        $validation = $this->validationService->validate([
            'academic_year' => $academicYear,
            'number_of_terms' => $numberOfTerms
        ], [
            'academic_year' => 'required',
            'number_of_terms' => 'required|numeric|min:1|max:3'
        ]);

        if (!$validation['success']) {
            throw new ValidationException($validation['errors'], 'Validation failed');
        }

        try {
            // Check if academic year exists, if not create it
            $existingYear = $this->yearRepository->getByAcademicYear($academicYear);
            if ($existingYear === null) {
                $createdYear = $this->yearRepository->create($academicYear, $numberOfTerms, 'Upcoming', 'system');
                $result = [
                    'academic_year' => $academicYear,
                    'number_of_terms' => $numberOfTerms
                ];
            } else {
                $this->yearRepository->update($existingYear['id'], $numberOfTerms, null, 'system');
                $result = [
                    'academic_year' => $academicYear,
                    'number_of_terms' => $numberOfTerms
                ];
            }

            return [
                'success' => true,
                'message' => 'Number of terms set successfully',
                'data' => $result
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to set number of terms: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Creates an academic year and automatically creates all terms based on number_of_terms.
     * 
     * Step 1: Creates the academic year record in academic_years table
     * Step 2: Automatically creates all terms (Term 1, Term 2, Term 3) with default dates
     * Term 1 is set to Active, others are set to Upcoming.
     * Dates can be edited later.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @param int $numberOfTerms The number of terms (1-3)
     * @param string $status The status for the academic year (Active, Upcoming, Completed, Archived)
     * @param string $addedBy The user ID who created this record
     * @return array Response array with success status, message, and data
     * @throws ValidationException If validation fails
     * @throws \RuntimeException If business rules are violated or database operation fails
     */
    public function createAcademicYear(string $academicYear, int $numberOfTerms, string $status, string $addedBy): array
    {
        // Validate input
        $validation = $this->validationService->validate([
            'academic_year' => $academicYear,
            'number_of_terms' => $numberOfTerms,
            'status' => $status,
        ], [
            'academic_year' => 'required',
            'number_of_terms' => 'required|numeric|min:1|max:3',
            'status' => 'required|in:Active,Upcoming,Completed,Archived',
        ]);

        if (!$validation['success']) {
            throw new ValidationException((array)$validation['errors'], 'Validation failed');
        }

        // Check if academic year already exists
        $existingYear = $this->yearRepository->getByAcademicYear($academicYear);
        if ($existingYear !== null) {
            throw new \RuntimeException("Academic year '{$academicYear}' already exists.");
        }

        // Check if terms already exist for this academic year
        $existingCount = $this->setupRepository->countTermsByYear($academicYear);
        if ($existingCount > 0) {
            throw new \RuntimeException("Academic year '{$academicYear}' already has terms configured. Please delete existing terms first.");
        }

        try {
            // Step 1: Create academic year record
            $createdYear = $this->yearRepository->create($academicYear, $numberOfTerms, $status, $addedBy);

            // Step 2: Create all terms with default dates
            $terms = $this->generateDefaultTerms($academicYear, $numberOfTerms);
            $createdTerms = $this->setupRepository->createAllTerms($academicYear, $terms, $addedBy);

            return [
                'success' => true,
                'message' => "Academic year '{$academicYear}' with {$numberOfTerms} term(s) created successfully",
                'data' => [
                    'academic_year' => $createdYear,
                    'terms' => $createdTerms
                ]
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to create academic year: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generates default term entries based on number of terms.
     * 
     * Creates terms with default dates that can be edited later.
     * Term 1 is Active, others are Upcoming.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @param int $numberOfTerms The number of terms (1-3)
     * @return array Array of term data with default dates
     */
    private function generateDefaultTerms(string $academicYear, int $numberOfTerms): array
    {
        $terms = [];
        
        // Extract year from academic year (e.g., '2025/2026' -> 2025)
        $yearParts = explode('/', $academicYear);
        $startYear = (int)($yearParts[0] ?? date('Y'));
        
        // Term 1 - Active (default dates: September to December)
        $terms[] = [
            'term' => 'Term 1',
            'start_date' => $startYear . '-09-01',
            'end_date' => $startYear . '-12-20',
            'status' => 'Active',
        ];

        // Term 2 - Upcoming (default dates: January to April of next year)
        if ($numberOfTerms >= 2) {
            $terms[] = [
                'term' => 'Term 2',
                'start_date' => ($startYear + 1) . '-01-15',
                'end_date' => ($startYear + 1) . '-04-20',
                'status' => 'Upcoming',
            ];
        }

        // Term 3 - Upcoming (default dates: May to September)
        if ($numberOfTerms >= 3) {
            $terms[] = [
                'term' => 'Term 3',
                'start_date' => ($startYear + 1) . '-05-20',
                'end_date' => ($startYear + 1) . '-09-05',
                'status' => 'Upcoming',
            ];
        }

        return $terms;
    }

    /**
     * Retrieves all academic years with their associated terms, optionally filtered by academic year.
     *
     * @param string $searchYear Optional academic year to filter by (e.g., '2025/2026')
     * @return array Response array with success status and nested data structure
     * @throws \RuntimeException If database operation fails
     */
    public function listAcademicYears(string $searchYear = ''): array
    {
        try {
            // Fetch academic years, filtered by search if provided
            $academicYears = $this->yearRepository->getAll($searchYear);

            $result = [];
            foreach ($academicYears as $year) {
                // Fetch terms for this academic year
                $terms = $this->setupRepository->getTermsByAcademicYear($year['academic_year']);

                $result[] = [
                    'academic_year' => [
                        'year' => $year['academic_year'],
                        'status' => $year['status']
                    ],
                    'terms' => $terms
                ];
            }

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to retrieve academic years: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retrieves the currently active academic year term.
     *
     * @return array Response array with success status and data
     * @throws \RuntimeException If no active academic year is found or database operation fails
     */
    public function getActiveAcademicYear(): array
    {
        try {
            $active = $this->setupRepository->getActive();
            if ($active === null) {
                throw new \RuntimeException('No active academic year term found');
            }

            return [
                'success' => true,
                'data' => $active
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to retrieve active academic year: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Updates an existing academic year term.
     *
     * @param int $id The academic year term ID
     * @param string $startDate The start date (Y-m-d format)
     * @param string $endDate The end date (Y-m-d format)
     * @param string $status The status (Active, Upcoming, Completed)
     * @param string $updatedBy The user ID who updated this record
     * @return array Response array with success status and message
     * @throws ValidationException If validation fails
     * @throws \RuntimeException If record not found or database operation fails
     */
    public function updateAcademicYear(int $id, string $startDate, string $endDate, string $status, string $updatedBy): array
    {
        $validation = $this->validationService->validate([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
        ], [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:Active,Upcoming,Completed'
        ]);

        if (!$validation['success']) {
            throw new ValidationException($validation['errors'], 'Validation failed');
        }

        $existing = $this->setupRepository->getById($id);
        if ($existing === null) {
            throw new \RuntimeException('Academic year term not found');
        }

        try {
            $success = $this->setupRepository->update($id, $startDate, $endDate, $status, $updatedBy);
            if (!$success) {
                throw new \RuntimeException('Failed to update academic year term');
            }

            return [
                'success' => true,
                'message' => 'Academic year term updated successfully'
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to update academic year term: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Updates the status of an academic year term.
     * 
     * If setting status to 'Active', automatically deactivates any currently active term.
     *
     * @param int $id The academic year term ID
     * @param string $status The new status (Active, Upcoming, Completed)
     * @param string $updatedBy The user ID who updated this record
     * @return array Response array with success status and message
     * @throws ValidationException If validation fails
     * @throws \RuntimeException If record not found or database operation fails
     */
    public function updateStatus(int $id, string $status, string $updatedBy): array
    {
        $validation = $this->validationService->validate(
            ['status' => $status],
            ['status' => 'required|in:Active,Upcoming,Completed']
        );

        if (!$validation['success']) {
            throw new ValidationException($validation['errors'], 'Validation failed');
        }

        $existing = $this->setupRepository->getById($id);
        if ($existing === null) {
            throw new \RuntimeException('Academic year term not found');
        }

        try {
            // If activating this term, deactivate any currently active term within the same academic year
            if ($status === 'Active') {
                $currentActive = $this->setupRepository->getActive();
                if ($currentActive !== null && (int)$currentActive['id'] !== $id && $currentActive['academic_year'] === $existing['academic_year']) {
                    $this->setupRepository->update(
                        (int)$currentActive['id'],
                        $currentActive['start_date'],
                        $currentActive['end_date'],
                        'Upcoming',
                        $updatedBy
                    );
                }
            }

            $success = $this->setupRepository->update(
                $id,
                $existing['start_date'],
                $existing['end_date'],
                $status,
                $updatedBy
            );

            if (!$success) {
                throw new \RuntimeException('Failed to update academic year status');
            }

            return [
                'success' => true,
                'message' => 'Academic year status updated successfully'
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to update academic year status: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Updates the status of an academic year.
     *
     * If setting status to 'Active', automatically deactivates any currently active academic year.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @param string $status The new status (Active, Upcoming, Completed, Archived)
     * @param string $updatedBy The user ID who updated this record
     * @return array Response array with success status and message
     * @throws ValidationException If validation fails
     * @throws \RuntimeException If record not found or database operation fails
     */
    public function updateAcademicYearStatus(string $academicYear, string $status, string $updatedBy): array
    {
        $validation = $this->validationService->validate(
            ['status' => $status],
            ['status' => 'required|in:Active,Upcoming,Completed,Archived']
        );

        if (!$validation['success']) {
            throw new ValidationException($validation['errors'], 'Validation failed');
        }

        $existing = $this->yearRepository->getByAcademicYear($academicYear);
        if ($existing === null) {
            throw new \RuntimeException("Academic year '{$academicYear}' not found");
        }

        try {
            // If activating this academic year, deactivate any currently active academic year
            if ($status === 'Active') {
                $currentActive = $this->yearRepository->getActive();
                if ($currentActive !== null && $currentActive['academic_year'] !== $academicYear) {
                    $this->yearRepository->update(
                        (int)$currentActive['id'],
                        null,
                        'Upcoming', // Set previous active to Upcoming
                        $updatedBy
                    );
                }
            }

            $success = $this->yearRepository->update(
                (int)$existing['id'],
                null, // Don't update number_of_terms
                $status,
                $updatedBy
            );

            if (!$success) {
                throw new \RuntimeException('Failed to update academic year status');
            }

            return [
                'success' => true,
                'message' => 'Academic year status updated successfully'
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to update academic year status: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Deletes an academic year term.
     *
     * @param int $id The academic year term ID
     * @return array Response array with success status and message
     * @throws \RuntimeException If record not found or database operation fails
     */
    public function delete(int $id): array
    {
        $existing = $this->setupRepository->getById($id);
        if ($existing === null) {
            throw new \RuntimeException('Academic year term not found');
        }

        try {
            $success = $this->setupRepository->delete($id);
            if (!$success) {
                throw new \RuntimeException('Failed to delete academic year term');
            }

            return [
                'success' => true,
                'message' => 'Academic year term deleted successfully'
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to delete academic year term: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Deletes one or more academic years and all their associated terms.
     *
     * @param string|array $academicYears The academic year(s) to delete (single string or array of strings)
     * @return array Response array with success status, message, and detailed results
     * @throws \RuntimeException If any academic year operation fails
     * @throws ValidationException If validation fails
     */
    public function deleteAcademicYear($academicYears): array
    {
        // Normalize input to array
        if (is_string($academicYears)) {
            $academicYears = [$academicYears];
        }

        if (!is_array($academicYears) || empty($academicYears)) {
            throw new ValidationException(['years' => ['At least one academic year is required']], 'Validation failed');
        }

        // Validate and prepare data for each academic year
        $validYears = [];
        $errors = [];

        foreach ($academicYears as $academicYear) {
            $academicYear = trim((string)$academicYear);
            if (empty($academicYear)) {
                $errors[] = 'Empty academic year value provided';
                continue;
            }

            // Check if academic year exists
            $existingYear = $this->yearRepository->getByAcademicYear($academicYear);
            if ($existingYear === null) {
                $errors[] = "Academic year '{$academicYear}' not found";
                continue;
            }

            // Check if academic year is currently active
            if ($existingYear['status'] === 'Active') {
                $errors[] = "Cannot delete active academic year '{$academicYear}'. Please set another academic year as active first.";
                continue;
            }

            // Check if academic year is completed
            if ($existingYear['status'] === 'Completed') {
                $errors[] = "Cannot delete completed academic year '{$academicYear}'. Completed academic years must be preserved for historical records.";
                continue;
            }

            $validYears[] = [
                'year' => $academicYear,
                'data' => $existingYear
            ];
        }

        // If there are validation errors, fail the entire operation
        if (!empty($errors)) {
            throw new \RuntimeException('Validation failed: ' . implode('; ', $errors));
        }

        // If no valid years to delete, return early
        if (empty($validYears)) {
            return [
                'success' => false,
                'message' => 'No valid academic years to delete',
                'results' => []
            ];
        }

        $results = [];
        $allSuccessful = true;

        try {
            // Start transaction for all deletions
            $this->setupRepository->beginTransaction();

            foreach ($validYears as $yearInfo) {
                $academicYear = (string)$yearInfo['year'];

                try {
                    // Delete all terms for this academic year
                    $this->setupRepository->deleteTermsByAcademicYear($academicYear);

                    // Delete the academic year record
                    $yearDeleted = $this->yearRepository->deleteByAcademicYear($academicYear);

                    if ($yearDeleted) {
                        $results[] = [
                            'academic_year' => $academicYear,
                            'success' => true,
                            'message' => 'Deleted successfully'
                        ];
                    } else {
                        $results[] = [
                            'academic_year' => $academicYear,
                            'success' => false,
                            'message' => 'Failed to delete academic year record'
                        ];
                        $allSuccessful = false;
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'academic_year' => $academicYear,
                        'success' => false,
                        'message' => 'Error: ' . $e->getMessage()
                    ];
                    $allSuccessful = false;
                }
            }

            if ($allSuccessful) {
                $this->setupRepository->commit();

                $deletedCount = count(array_filter($results, fn($r) => $r['success']));
                $totalCount = count($results);

                return [
                    'success' => true,
                    'message' => "Successfully deleted {$deletedCount} of {$totalCount} academic year(s) and all associated terms",
                    'results' => $results
                ];
            } else {
                $this->setupRepository->rollBack();

                $deletedCount = count(array_filter($results, fn($r) => $r['success']));
                $failedCount = count($results) - $deletedCount;

                return [
                    'success' => false,
                    'message' => "Partial failure: {$deletedCount} deleted, {$failedCount} failed",
                    'results' => $results
                ];
            }
        } catch (PDOException $e) {
            $this->setupRepository->rollBack();
            throw new \RuntimeException('Database error during bulk deletion: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Deletes a specific academic term.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @param string $term The term to delete (e.g., 'Term 1')
     * @return array Response array with success status and message
     * @throws \RuntimeException If term not found or database operation fails
     */
    public function deleteAcademicTerm(string $academicYear, string $term): array
    {
        // Check if the term exists
        $existingTerm = $this->setupRepository->getByYearAndTerm($academicYear, $term);
        if ($existingTerm === null) {
            throw new \RuntimeException("Term '{$term}' for academic year '{$academicYear}' not found");
        }

        // Check if the term is currently active
        if ($existingTerm['status'] === 'Active') {
            throw new \RuntimeException("Cannot delete active term '{$term}' for academic year '{$academicYear}'. Please set another term as active first.");
        }

        // Check if the term is completed
        if ($existingTerm['status'] === 'Completed') {
            throw new \RuntimeException("Cannot delete completed term '{$term}' for academic year '{$academicYear}'. Completed terms must be preserved for historical records.");
        }

        try {
            $success = $this->setupRepository->deleteByYearAndTerm($academicYear, $term);
            if (!$success) {
                throw new \RuntimeException('Failed to delete academic term');
            }

            return [
                'success' => true,
                'message' => "Academic term '{$term}' for year '{$academicYear}' deleted successfully"
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to delete academic term: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Adds a new term to an existing academic year.
     *
     * @param string $academicYear The academic year (e.g., '2025/2026')
     * @param string $term The term name (e.g., 'Term 1', 'Term 2', etc.)
     * @param string $startDate The start date (Y-m-d format)
     * @param string $endDate The end date (Y-m-d format)
     * @param string $status The status (Active, Upcoming, Completed)
     * @param string $addedBy The user ID who added this record
     * @return array Response array with success status and data
     * @throws ValidationException If validation fails
     * @throws \RuntimeException If academic year not found, term already exists, or database operation fails
     */
    public function addAcademicTerm(string $academicYear, string $term, string $startDate, string $endDate, string $status, string $addedBy): array
    {
        $validation = $this->validationService->validate([
            'academic_year' => $academicYear,
            'term' => $term,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
        ], [
            'academic_year' => 'required',
            'term' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:Active,Upcoming,Completed',
        ]);

        if (!$validation['success']) {
            throw new ValidationException($validation['errors'], 'Validation failed');
        }

        // Check if academic year exists
        $existingYear = $this->yearRepository->getByAcademicYear($academicYear);
        if ($existingYear === null) {
            throw new \RuntimeException("Academic year '{$academicYear}' not found");
        }

        // Check if maximum number of terms for this academic year has been reached
        $currentTermCount = $this->setupRepository->countTermsByYear($academicYear);
        $maxTermsAllowed = (int)$existingYear['number_of_terms'];

        if ($currentTermCount >= $maxTermsAllowed) {
            throw new \RuntimeException("Cannot add more terms to academic year '{$academicYear}'. Maximum of {$maxTermsAllowed} term(s) allowed, currently has {$currentTermCount} term(s).");
        }

        // Check if term already exists for this academic year
        $existingTerm = $this->setupRepository->getByYearAndTerm($academicYear, $term);
        if ($existingTerm !== null) {
            throw new \RuntimeException("Term '{$term}' already exists for academic year '{$academicYear}'");
        }

        try {
            $termData = $this->setupRepository->create($academicYear, $term, $startDate, $endDate, $status, $addedBy);

            return [
                'success' => true,
                'message' => "Academic term '{$term}' added successfully to '{$academicYear}'",
                'data' => $termData
            ];
        } catch (PDOException $e) {
            throw new \RuntimeException('Failed to add academic term: ' . $e->getMessage(), 0, $e);
        }
    }
}
