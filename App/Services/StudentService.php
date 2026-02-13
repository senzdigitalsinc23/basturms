<?php

namespace App\Services;

use App\DTOs\StudentDTO;
use App\DTOs\StudentContactDTO;
use App\DTOs\GuardianDTO;
use App\DTOs\EmergencyContactDTO;
use App\DTOs\AdmissionDTO;
use App\Repositories\StudentRepository;
use App\Core\Database;
use App\Core\Cache;
use PDOException;
use App\DTOs\ContactAddressInfoDTO;
use App\DTOs\AdmissionInfoDTO;

/**
 * Service for managing student records, including creation, updates, status changes, and relations.
 */
class StudentService
{
    private StudentRepository $studentRepository;
    private Database $database;
    private Cache $cache;
    private const CACHE_TTL = 3600; // 1 hour
    private const SHORT_CACHE_TTL = 600; // 10 minutes for list queries

    /**
     * @param StudentRepository $studentRepository
     */
    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
        $this->database = Database::getInstance();
        $this->cache = new Cache();
    }

    /**
     * Create a new student record with all related information.
     *
     * @param array $data The student data (nested or flattened).
     * @return array The result of the creation.
     * @throws \Exception If validation fails or database error occurs.
     */
    public function createStudent(array $data): array
    {
        try {
            $db = $this->database->getConnection();
            $db->beginTransaction();

            // Support nested payload from validation
            if (isset($data['student_info'])) {
                $studentInfo = $data['student_info'] ?? [];
                $contact = $data['contact_address'] ?? [];
                $admission = $data['admission_info'] ?? [];

                // Determine or generate student number
                $studentNo = (string)($admission['admission_no'] ?? '');
                if ($studentNo === '') {
                    $studentNo = $this->studentRepository->generateStudentNo(admissionDate: (string)($admission['enrollment_date'] ?? date('Y-m-d')));
                }

                // Build arrays for DTOs
                $studentArray = [
                    'student_no' => $studentNo,
                    'first_name' => $studentInfo['first_name'] ?? '',
                    'last_name' => $studentInfo['last_name'] ?? '',
                    'other_name' => $studentInfo['other_name'] ?? null,
                    'dob' => $studentInfo['dob'] ?? date('Y-m-d'),
                    'gender' => $studentInfo['gender'] ?? '',
                    'nhis_no' => $studentInfo['nhis_no'] ?? '',
                    'created_by' => (int)($studentInfo['created_by'] ?? 0),
                ];
                $contactArray = [
                    'student_no' => $studentNo,
                    'email' => $contact['email'] ?? null,
                    'phone' => $contact['phone'] ?? null,
                    'country_id' => $contact['country_id'] ?? '',
                    'city' => $contact['city'] ?? null,
                    'hometown' => $contact['hometown'] ?? '',
                    'residence' => $contact['residence'] ?? '',
                    'house_no' => $contact['house_no'] ?? '',
                    'gps_no' => $contact['gps_no'] ?? '',
                ];

                // Auto-generate admission number if not provided
                $enrollmentDate = (string)($admission['enrollment_date'] ?? date('Y-m-d'));
                $admissionNo = (string)($admission['admission_no'] ?? '');
                if (empty($admissionNo)) {
                    $admissionNo = $this->studentRepository->generateAdmissionNo($enrollmentDate);
                }

                $admissionArray = [
                    'student_no' => $studentNo,
                    'admission_no' => $admissionNo,
                    'admission_status' => $admission['admission_status'] ?? 'Active',
                    'class_assigned' => $admission['class_assigned'] ?? '',
                    'enrollment_date' => $enrollmentDate,
                ];

                $studentDTO = StudentDTO::fromArray($studentArray);
                $contactDTO = StudentContactDTO::fromArray($contactArray);
                $admissionDTO = AdmissionDTO::fromArray($admissionArray);
                
                // Normalize guardians/emergency into flat arrays using generated studentNo
                $normalizedGuardians = [];
                if (!empty($data['guardians']) && is_array($data['guardians'])) {
                    foreach ($data['guardians'] as $g) {
                        $normalizedGuardians[] = [
                            'guardian_id' => $studentNo,
                            'guardian_name' => $g['guardian_name'] ?? '',
                            'guardian_phone' => $g['guardian_phone'] ?? '',
                            'guardian_email' => $g['guardian_email'] ?? null,
                            'guardian_relationship' => $g['guardian_relationship'] ?? '',
                        ];
                    }
                }

                $normalizedEmergency = null;
                if (!empty($data['emergency_contact']) && is_array($data['emergency_contact'])) {
                    $e = $data['emergency_contact'];
                    $normalizedEmergency = [
                        'emergency_id' => $studentNo,
                        'emergency_name' => $e['emergency_name'] ?? '',
                        'emergency_phone' => $e['emergency_phone'] ?? '',
                        'emergency_email' => $e['emergency_email'] ?? null,
                        'emergency_relationship' => $e['emergency_relationship'] ?? '',
                    ];
                }

                // Create user data
                $userData = [
                    'user_id' => $studentNo,
                    'email' => $contact['email'] ?? (explode('-', $studentNo)[2] ?? $studentNo),
                    'username' => $contact['email'] ?? (explode('-', $studentNo)[2] ?? $studentNo),
                    'password' => password_hash(
                        ucfirst(($studentInfo['first_name'] ?? 'S')[0]) . ucfirst($studentInfo['last_name'] ?? 'tudent') . '123',
                        PASSWORD_BCRYPT
                    ),
                    'role_id' => '20',
                    'status' => 'inactive'
                ];

                // Persist
                $this->studentRepository->createStudent($studentDTO);
                $this->studentRepository->createStudentContact($contactDTO);
                $this->studentRepository->createAdmission($admissionDTO);
                $this->studentRepository->createUser($userData);

                foreach ($normalizedGuardians as $guardian) {
                    $this->studentRepository->createGuardian(GuardianDTO::fromArray($guardian));
                }
                if ($normalizedEmergency) {
                    $this->studentRepository->createEmergencyContact(EmergencyContactDTO::fromArray($normalizedEmergency));
                }

                $db->commit();
                
                // Clear cache for the new student
                $this->studentRepository->clearStudentCache($studentNo);

                // Build response DTOs for nested structure
                $contactInfoDTO = ContactAddressInfoDTO::fromArray($contact);
                // Use the generated admission_no in the response
                $admissionForResponse = array_merge($admission, ['admission_no' => $admissionNo]);
                $admissionInfoDTO = AdmissionInfoDTO::fromArray($admissionForResponse, $studentInfo);

                return [
                    'success' => true,
                    'message' => 'Student successfully created',
                    'data' => [
                        'studentInfo' => [
                            'studentNo' => $studentNo,
                            'firstName' => $studentInfo['first_name'] ?? '',
                            'lastName' => $studentInfo['last_name'] ?? '',
                            'otherName' => $studentInfo['other_name'] ?? null,
                            'dob' => $studentInfo['dob'] ?? '',
                            'gender' => $studentInfo['gender'] ?? '',
                            'createdBy' => (int)($studentInfo['created_by'] ?? 0),
                        ],
                        'contactAddressInfo' => $contactInfoDTO->toArray(),
                        'admissionInfo' => $admissionInfoDTO->toArray(),
                    ]
                ];
            }

            // Legacy flattened payload support
            if (empty($data['student_no'])) {
                $enrollmentDate = (string)($data['enrollment_date'] ?? date('Y-m-d'));
                $data['student_no'] = $this->studentRepository->generateStudentNo(admissionDate: $enrollmentDate);
            }
            
            // Auto-generate admission number if not provided
            if (empty($data['admission_no'])) {
                $enrollmentDate = (string)($data['enrollment_date'] ?? date('Y-m-d'));
                $data['admission_no'] = $this->studentRepository->generateAdmissionNo($enrollmentDate);
            }
            
            $studentDTO = StudentDTO::fromArray($data);
            $contactDTO = StudentContactDTO::fromArray($data);
            $admissionDTO = AdmissionDTO::fromArray($data);

            // Create user data
            $userData = [
                'user_id' => $data['student_no'],
                'email' => $data['email'] ?: explode('-', (string)$data['student_no'])[2],
                'username' => $data['email'] ?: explode('-', (string)$data['student_no'])[2],
                'password' => password_hash(
                    ucfirst($data['first_name'][0]) . ucfirst($data['last_name']) . '123',
                    PASSWORD_BCRYPT
                ),
                'role_id' => '20',
                'status' => 'inactive'
            ];

            // Insert core records
            $this->studentRepository->createStudent($studentDTO);
            $this->studentRepository->createStudentContact($contactDTO);
            $this->studentRepository->createAdmission($admissionDTO);
            $this->studentRepository->createUser($userData);
            
            // Clear cache for the new student
            $this->studentRepository->clearStudentCache((string)$data['student_no']);

            // Insert guardians: support new nested array format; fallback to legacy fields
            if (!empty($data['guardians']) && is_array($data['guardians'])) {
                foreach ($data['guardians'] as $g) {
                    $guardianPayload = [
                        'guardian_id' => $data['student_no'],
                        'guardian_name' => $g['guardian_name'] ?? '',
                        'guardian_phone' => $g['guardian_phone'] ?? '',
                        'guardian_email' => $g['guardian_email'] ?? null,
                        'guardian_relationship' => $g['guardian_relationship'] ?? '',
                    ];
                    $this->studentRepository->createGuardian(GuardianDTO::fromArray($guardianPayload));
                }
            } else if (!empty($data['father_name']) || !empty($data['mother_name'])) {
                $fatherDTO = GuardianDTO::fromArray([
                    'guardian_id' => $data['student_no'],
                    'guardian_name' => $data['father_name'] ?? '',
                    'guardian_phone' => $data['father_phone'] ?? '',
                    'guardian_email' => $data['father_email'] ?? null,
                    'guardian_relationship' => 'father'
                ]);
                $motherDTO = GuardianDTO::fromArray([
                    'guardian_id' => $data['student_no'],
                    'guardian_name' => $data['mother_name'] ?? '',
                    'guardian_phone' => $data['mother_phone'] ?? '',
                    'guardian_email' => $data['mother_email'] ?? null,
                    'guardian_relationship' => 'mother'
                ]);
                $this->studentRepository->createGuardian($fatherDTO);
                $this->studentRepository->createGuardian($motherDTO);
            }

            // Insert emergency contact: support new nested format; fallback to legacy fields
            if (!empty($data['emergency_contact']) && is_array($data['emergency_contact'])) {
                $e = $data['emergency_contact'];
                $emergencyDTO = EmergencyContactDTO::fromArray([
                    'emergency_id' => $data['student_no'],
                    'emergency_name' => $e['emergency_name'] ?? '',
                    'emergency_phone' => $e['emergency_phone'] ?? '',
                    'emergency_email' => $e['emergency_email'] ?? null,
                    'emergency_relationship' => $e['emergency_relationship'] ?? '',
                ]);
                $this->studentRepository->createEmergencyContact($emergencyDTO);
            } else if (!empty($data['emergency_name'])) {
                $emergencyDTO = EmergencyContactDTO::fromArray([
                    'emergency_id' => $data['student_no'],
                    'emergency_name' => $data['emergency_name'],
                    'emergency_phone' => $data['emergency_phone'] ?? '',
                    'emergency_email' => $data['emergency_email'] ?? null,
                    'emergency_relationship' => $data['emergency_relationship'] ?? '',
                ]);
                $this->studentRepository->createEmergencyContact($emergencyDTO);
            }

            $db->commit();

            // Build response DTOs for legacy structure
            $contactInfoDTO = ContactAddressInfoDTO::fromArray($data);
            $admissionInfoDTO = AdmissionInfoDTO::fromArray([
                'admission_no' => $data['admission_no'] ?? '',
                'admission_status' => $data['status'] ?? ($data['admission_status'] ?? 'Active'),
                'class_assigned' => $data['class_id'] ?? ($data['class_assigned'] ?? ''),
                'enrollment_date' => $data['enrollment_date'] ?? '0000-00-00',
            ], ['nhis_no' => $data['nhis_no'] ?? '']);

            return [
                'success' => true,
                'message' => 'Student successfully created',
                'data' => [
                    'studentInfo' => [
                        'studentNo' => $data['student_no'],
                        'firstName' => $data['first_name'],
                        'lastName' => $data['last_name'],
                        'otherName' => $data['other_name'] ?? null,
                        'dob' => $data['dob'],
                        'gender' => $data['gender'],
                        'createdBy' => (int)$data['created_by'],
                    ],
                    'contactAddressInfo' => $contactInfoDTO->toArray(),
                    'admissionInfo' => $admissionInfoDTO->toArray(),
                ]
            ];

        } catch (PDOException $e) {
            $db->rollBack();
            throw new \Exception("Database error: " . $e->getMessage());
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Get a paginated list of students.
     *
     * @param int $page The page number.
     * @param int $limit The number of records per page.
     * @param string|null $search The search query.
     * @param string|null $status The admission status to filter by.
     * @return array The list of students and pagination info.
     */
    public function getStudents(int $page = 1, int $limit = 7, ?string $search = null, ?string $status = null): array
    {
        // Default to showing only admitted students when no status is supplied
        if ($status === null || $status === '') {
            $status = 'Admitted';
        }

        $offset = ($page - 1) * $limit;
        
        $students = $this->studentRepository->getStudentsWithRelations($limit, $offset, $search, (string)$status);
        $total = $this->studentRepository->countStudents($search, (string)$status);
        $pages = (int)ceil($total / $limit);

        return [
            'students' => $students,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => $pages,
                'offset' => $offset,
                'search' => $search,
                'status' => $status
            ]
        ];
    }

    /**
     * Update the status of a single student.
     *
     * @param string $studentNo The student number.
     * @param string $status The new status.
     * @param string|null $reason The reason for the status change.
     * @param mixed $archivedBy The user ID performing the archiving.
     * @return array The result of the operation.
     */
    public function updateStudentStatus(string $studentNo, string $status, ?string $reason = null, $archivedBy = null): array
    {
        $result = $this->updateStudentsStatus([$studentNo], $status, $reason, $archivedBy);
        if ($result['success']) {
            $result['message'] = 'Student status updated successfully';
        }
        return $result;
    }

    /**
     * Update the status of multiple students.
     *
     * @param array $studentNos The list of student numbers.
     * @param string $status The new status.
     * @param string|null $reason The reason for the status change.
     * @param mixed $archivedBy The user ID performing the archiving.
     * @return array The result of the operation.
     */
    public function updateStudentsStatus(array $studentNos, string $status, ?string $reason = null, $archivedBy = null): array
    {
        $studentNos = array_values(array_unique(array_filter(array_map('trim', $studentNos))));
        if (empty($studentNos)) {
            return [
                'success' => false,
                'message' => 'No student numbers supplied'
            ];
        }

        $currentStatuses = $this->studentRepository->getAdmissionStatuses($studentNos);
        $affected = $this->studentRepository->updateStudentsStatus($studentNos, $status);

        if ($affected > 0) {
            // Clear student-related caches for all affected students
            foreach ($studentNos as $studentNo) {
                $this->cache->forget("student:{$studentNo}");
                $this->cache->forget("student:{$studentNo}:relations");
                $this->studentRepository->clearStudentCache((string)$studentNo);
            }

            // Archive students leaving the Admitted state
            if (strcasecmp($status, 'Admitted') !== 0) {
                $toArchive = [];
                foreach ($studentNos as $studentNo) {
                    if (isset($currentStatuses[$studentNo]) && strcasecmp((string)$currentStatuses[$studentNo], 'Admitted') === 0) {
                        $toArchive[] = $studentNo;
                    }
                }

                if (!empty($toArchive)) {
                    $archiveReason = $reason ?: 'Status changed to ' . $status;
                    $this->studentRepository->archiveStudents($toArchive, $archivedBy, $archiveReason);
                }
            }

            return [
                'success' => true,
                'message' => 'Student statuses updated successfully',
                'affected' => $affected,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to update student statuses'
        ];
    }

    /**
     * Update an existing student's record and relations.
     *
     * @param array $data The updated student data.
     * @return array The result of the update.
     * @throws \Exception If student not found or validation fails.
     */
    public function updateStudent(array $data): array
    {
        try {
            $db = $this->database->getConnection();
            $db->beginTransaction();

            // Get student number from data
            $studentNo = (string)($data['student_no'] ?? '');
            if (empty($studentNo)) {
                throw new \Exception('Student number is required for update');
            }

            // Check if student exists
            if (!$this->studentRepository->studentExists($studentNo)) {
                throw new \Exception('Student not found');
            }

            // Support nested payload from validation
            if (isset($data['student_info'])) {
                $studentInfo = $data['student_info'] ?? [];
                $contact = $data['contact_address'] ?? [];
                $admission = $data['admission_info'] ?? [];

                // Build arrays for DTOs
                $studentArray = [
                    'student_no' => $studentNo,
                    'first_name' => $studentInfo['first_name'] ?? '',
                    'last_name' => $studentInfo['last_name'] ?? '',
                    'other_name' => $studentInfo['other_name'] ?? null,
                    'dob' => $studentInfo['dob'] ?? date('Y-m-d'),
                    'gender' => $studentInfo['gender'] ?? '',
                    'nhis_no' => $studentInfo['nhis_no'] ?? '',
                    'created_by' => (int)($studentInfo['created_by'] ?? 0),
                ];
                $contactArray = [
                    'student_no' => $studentNo,
                    'email' => $contact['email'] ?? null,
                    'phone' => $contact['phone'] ?? null,
                    'country_id' => $contact['country_id'] ?? '',
                    'city' => $contact['city'] ?? null,
                    'hometown' => $contact['hometown'] ?? '',
                    'residence' => $contact['residence'] ?? '',
                    'house_no' => $contact['house_no'] ?? '',
                    'gps_no' => $contact['gps_no'] ?? '',
                ];

                $admissionArray = [
                    'student_no' => $studentNo,
                    'admission_no' => $admission['admission_no'] ?? '',
                    'admission_status' => $admission['admission_status'] ?? 'Active',
                    'class_assigned' => $admission['class_assigned'] ?? '',
                    'enrollment_date' => $admission['enrollment_date'] ?? date('Y-m-d'),
                ];

                $studentDTO = StudentDTO::fromArray($studentArray);
                $contactDTO = StudentContactDTO::fromArray($contactArray);
                $admissionDTO = AdmissionDTO::fromArray($admissionArray);
                
                // Normalize guardians/emergency into flat arrays
                $normalizedGuardians = [];
                if (!empty($data['guardians']) && is_array($data['guardians'])) {
                    foreach ($data['guardians'] as $g) {
                        $normalizedGuardians[] = [
                            'guardian_id' => $studentNo,
                            'guardian_name' => $g['guardian_name'] ?? '',
                            'guardian_phone' => $g['guardian_phone'] ?? '',
                            'guardian_email' => $g['guardian_email'] ?? null,
                            'guardian_relationship' => $g['guardian_relationship'] ?? '',
                        ];
                    }
                }

                $normalizedEmergency = null;
                if (!empty($data['emergency_contact']) && is_array($data['emergency_contact'])) {
                    $e = $data['emergency_contact'];
                    $normalizedEmergency = [
                        'emergency_id' => $studentNo,
                        'emergency_name' => $e['emergency_name'] ?? '',
                        'emergency_phone' => $e['emergency_phone'] ?? '',
                        'emergency_email' => $e['emergency_email'] ?? null,
                        'emergency_relationship' => $e['emergency_relationship'] ?? '',
                    ];
                }

                // Update core records
                $this->studentRepository->updateStudent($studentDTO);
                $this->studentRepository->updateStudentContact($contactDTO);
                $this->studentRepository->updateAdmission($admissionDTO);

                // Delete existing guardians and emergency contact, then recreate
                $this->studentRepository->deleteGuardians($studentNo);
                $this->studentRepository->deleteEmergencyContact($studentNo);

                // Recreate guardians and emergency contact
                foreach ($normalizedGuardians as $guardian) {
                    $this->studentRepository->createGuardian(GuardianDTO::fromArray($guardian));
                }
                if ($normalizedEmergency) {
                    $this->studentRepository->createEmergencyContact(EmergencyContactDTO::fromArray($normalizedEmergency));
                }

                $db->commit();
                
                // Clear cache for the updated student
                $this->studentRepository->clearStudentCache($studentNo);

                // Build response DTOs for nested structure
                $contactInfoDTO = ContactAddressInfoDTO::fromArray($contact);
                $admissionForResponse = array_merge($admission, ['admission_no' => $admissionArray['admission_no']]);
                $admissionInfoDTO = AdmissionInfoDTO::fromArray($admissionForResponse, $studentInfo);

                return [
                    'success' => true,
                    'message' => 'Student successfully updated',
                    'data' => [
                        'studentInfo' => [
                            'studentNo' => $studentNo,
                            'firstName' => $studentInfo['first_name'] ?? '',
                            'lastName' => $studentInfo['last_name'] ?? '',
                            'otherName' => $studentInfo['other_name'] ?? null,
                            'dob' => $studentInfo['dob'] ?? '',
                            'gender' => $studentInfo['gender'] ?? '',
                        ],
                        'contactAddressInfo' => $contactInfoDTO->toArray(),
                        'admissionInfo' => $admissionInfoDTO->toArray(),
                    ]
                ];
            }

            // Legacy flattened payload support
            $studentDTO = StudentDTO::fromArray($data);
            $contactDTO = StudentContactDTO::fromArray($data);
            $admissionDTO = AdmissionDTO::fromArray($data);

            // Update core records
            $this->studentRepository->updateStudent($studentDTO);
            $this->studentRepository->updateStudentContact($contactDTO);
            $this->studentRepository->updateAdmission($admissionDTO);

            // Delete existing guardians and emergency contact
            $this->studentRepository->deleteGuardians($studentNo);
            $this->studentRepository->deleteEmergencyContact($studentNo);

            // Recreate guardians: support new nested array format; fallback to legacy fields
            if (!empty($data['guardians']) && is_array($data['guardians'])) {
                foreach ($data['guardians'] as $g) {
                    $guardianPayload = [
                        'guardian_id' => $studentNo,
                        'guardian_name' => $g['guardian_name'] ?? '',
                        'guardian_phone' => $g['guardian_phone'] ?? '',
                        'guardian_email' => $g['guardian_email'] ?? null,
                        'guardian_relationship' => $g['guardian_relationship'] ?? '',
                    ];
                    $this->studentRepository->createGuardian(GuardianDTO::fromArray($guardianPayload));
                }
            } else if (!empty($data['father_name']) || !empty($data['mother_name'])) {
                $fatherDTO = GuardianDTO::fromArray([
                    'guardian_id' => $studentNo,
                    'guardian_name' => $data['father_name'] ?? '',
                    'guardian_phone' => $data['father_phone'] ?? '',
                    'guardian_email' => $data['father_email'] ?? null,
                    'guardian_relationship' => 'father'
                ]);
                $motherDTO = GuardianDTO::fromArray([
                    'guardian_id' => $studentNo,
                    'guardian_name' => $data['mother_name'] ?? '',
                    'guardian_phone' => $data['mother_phone'] ?? '',
                    'guardian_email' => $data['mother_email'] ?? null,
                    'guardian_relationship' => 'mother'
                ]);
                $this->studentRepository->createGuardian($fatherDTO);
                $this->studentRepository->createGuardian($motherDTO);
            }

            // Recreate emergency contact: support new nested format; fallback to legacy fields
            if (!empty($data['emergency_contact']) && is_array($data['emergency_contact'])) {
                $e = $data['emergency_contact'];
                $emergencyDTO = EmergencyContactDTO::fromArray([
                    'emergency_id' => $studentNo,
                    'emergency_name' => $e['emergency_name'] ?? '',
                    'emergency_phone' => $e['emergency_phone'] ?? '',
                    'emergency_email' => $e['emergency_email'] ?? null,
                    'emergency_relationship' => $e['emergency_relationship'] ?? '',
                ]);
                $this->studentRepository->createEmergencyContact($emergencyDTO);
            } else if (!empty($data['emergency_name'])) {
                $emergencyDTO = EmergencyContactDTO::fromArray([
                    'emergency_id' => $studentNo,
                    'emergency_name' => $data['emergency_name'],
                    'emergency_phone' => $data['emergency_phone'] ?? '',
                    'emergency_email' => $data['emergency_email'] ?? null,
                    'emergency_relationship' => $data['emergency_relationship'] ?? '',
                ]);
                $this->studentRepository->createEmergencyContact($emergencyDTO);
            }

            $db->commit();

            // Clear cache for the updated student
            $this->studentRepository->clearStudentCache($studentNo);

            // Build response DTOs for legacy structure
            $contactInfoDTO = ContactAddressInfoDTO::fromArray($data);
            $admissionInfoDTO = AdmissionInfoDTO::fromArray([
                'admission_no' => $data['admission_no'] ?? '',
                'admission_status' => $data['status'] ?? ($data['admission_status'] ?? 'Active'),
                'class_assigned' => $data['class_id'] ?? ($data['class_assigned'] ?? ''),
                'enrollment_date' => $data['enrollment_date'] ?? '0000-00-00',
            ], ['nhis_no' => $data['nhis_no'] ?? '']);

            return [
                'success' => true,
                'message' => 'Student successfully updated',
                'data' => [
                    'studentInfo' => [
                        'studentNo' => $data['student_no'],
                        'firstName' => $data['first_name'],
                        'lastName' => $data['last_name'],
                        'otherName' => $data['other_name'] ?? null,
                        'dob' => $data['dob'],
                        'gender' => $data['gender'],
                    ],
                    'contactAddressInfo' => $contactInfoDTO->toArray(),
                    'admissionInfo' => $admissionInfoDTO->toArray(),
                ]
            ];

        } catch (PDOException $e) {
            $db->rollBack();
            throw new \Exception("Database error: " . $e->getMessage());
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Generate a unique student number.
     *
     * @param string $region The region code.
     * @param string $district The district code.
     * @param string $school The school code.
     * @param string $admissionDate The admission date.
     * @return string The generated student number.
     */
    public function generateStudentNo(string $region = "WR", string $district = "TK001", string $school = "LBA", string $admissionDate = ''): string
    {
        return $this->studentRepository->generateStudentNo($region, $district, $school, $admissionDate);
    }

    /**
     * Get a student's full record including all relations.
     *
     * @param string $studentNo The student number.
     * @return array|null The student record with relations or null if not found.
     */
    public function getStudentWithRelations(string $studentNo): ?array
    {
        $cacheKey = "student:{$studentNo}:relations";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            /** @var array $cached */
            return $cached;
        }
        
        $student = $this->studentRepository->findStudentByNo($studentNo);
        if (!$student) {
            return null;
        }
        $guardians = $this->studentRepository->getGuardians($studentNo);
        $emergency = $this->studentRepository->getEmergencyContact($studentNo);
        $payments = $this->studentRepository->getStudentPayments($studentNo);
        $attendance = $this->studentRepository->getStudentAttendance($studentNo);
        $billItems = $this->studentRepository->getStudentBillItems($studentNo);
        $clubs = $this->studentRepository->getStudentClubs($studentNo);
        $sportsTeams = $this->studentRepository->getStudentSportsTeams($studentNo);
        $documents = $this->studentRepository->getStudentDocuments($studentNo);

        // Now split into requested groups
        $result = [
            'student_info' => [
                'id' => $student['id'] ?? null,
                'student_no' => $student['student_no'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'other_name' => $student['other_name'] ?? '',
                'gender' => $student['gender'],
                'dob' => $student['dob'],
                'nhis_no' => $student['nhis_no'],
                'created_at' => $student['created_at'] ?? null,
                'updated_at' => $student['updated_at'] ?? null,
                'created_by' => $student['created_by'] ?? null,
            ],
            'contact_address' => [
                'email' => $student['email'] ?? '',
                'phone' => $student['phone'] ?? '',
                'country_id' => $student['country_id'] ?? '',
                'city' => $student['city'] ?? '',
                'hometown' => $student['hometown'] ?? '',
                'residence' => $student['residence'] ?? '',
                'house_no' => $student['house_no'] ?? '',
                'gps_no' => $student['gps_no'] ?? '',
            ],
            'admission_info' => [
                'admission_no' => $student['admission_no'] ?? '',
                'admission_status' => $student['admission_status'] ?? '',
                'class_assigned' => $student['class_assigned'] ?? '',
                'class_name' => $student['class_name'] ?? '',
                'enrollment_date' => $student['enrollment_date'] ?? '',
            ],
            'guardians' => $guardians,
            'emergency_contact' => $emergency,
            'payment_history' => $payments,
            'attendance_history' => $attendance,
            'bill_items' => $billItems,
            'clubs' => $clubs,
            'sports_teams' => $sportsTeams,
            'uploaded_documents' => $documents,
        ];
        
        $this->cache->set($cacheKey, $result, self::CACHE_TTL);
        
        return $result;
    }

    /**
     * Export all students.
     *
     * @return array The list of all students.
     */
    public function exportStudents(): array
    {
        return $this->studentRepository->getAllStudents();
    }

    /**
     * Import multiple students from an array of data.
     *
     * @param array $studentsData The list of student data to import.
     * @return array The results of the import.
     */
    public function importStudents(array $studentsData): array
    {
        $results = [
            'total' => count($studentsData),
            'imported' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($studentsData as $index => $studentData) {
            try {
                if ($this->studentRepository->studentExists((string)$studentData['student_no'])) {
                    $results['skipped']++;
                    continue;
                }

                $this->createStudent($studentData);
                $results['imported']++;

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'row' => $index + 1,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Preview an import of student data.
     *
     * @param array $studentsData The data to preview.
     * @return array The preview information.
     */
    public function previewImport(array $studentsData): array
    {
        return [
            'total' => count($studentsData),
            'preview' => array_slice($studentsData, 0, 5), // Show first 5 rows
            'headers' => array_keys($studentsData[0] ?? [])
        ];
    }

    /**
     * Permanently delete a student and all related data.
     *
     * @param string $studentNo The student number.
     * @return array The result of the deletion.
     */
    public function deleteStudent(string $studentNo): array
    {
        try {
            $db = $this->database->getConnection();
            $db->beginTransaction();

            // Delete in reverse order of creation to handle foreign key constraints

            // 1. Delete emergency contact
            $this->studentRepository->deleteEmergencyContact($studentNo);

            // 2. Delete guardians
            $this->studentRepository->deleteGuardians($studentNo);

            // 3. Delete user account
            $this->studentRepository->deleteUser($studentNo);

            // 4. Delete admission details
            $this->studentRepository->deleteAdmission($studentNo);

            // 5. Delete student contact
            $this->studentRepository->deleteStudentContact($studentNo);

            // 6. Delete main student record
            $this->studentRepository->deleteStudent($studentNo);

            $db->commit();

            // Clear cache
            $this->studentRepository->clearStudentCache($studentNo);

            return [
                'success' => true,
                'message' => 'Student and all related data deleted successfully'
            ];

        } catch (PDOException $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Error deleting student: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get students for a specific class.
     * 
     * @param string $classId The class ID to filter by
     * @param string|null $status Optional admission status filter
     * @return array The result with students list
     */
    public function getClassStudents(string $classId, ?string $status = null): array
    {
        try {
            $students = $this->studentRepository->getClassStudents($classId, $status);
            
            return [
                'success' => true,
                'data' => $students,
                'count' => count($students)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching class students: ' . $e->getMessage()
            ];
        }
    }
}
