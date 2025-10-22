<?php

namespace App\Services;

use App\DTOs\StudentDTO;
use App\DTOs\StudentContactDTO;
use App\DTOs\GuardianDTO;
use App\DTOs\EmergencyContactDTO;
use App\DTOs\AdmissionDTO;
use App\Repositories\StudentRepository;
use App\Core\Database;
use PDOException;

class StudentService
{
    private StudentRepository $studentRepository;
    private Database $database;

    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
        $this->database = Database::getInstance();
    }

    public function createStudent(array $data): array
    {
        try {
            $db = $this->database->getConnection();
            $db->beginTransaction();

            // Create DTOs
            $studentDTO = StudentDTO::fromArray($data);
            $contactDTO = StudentContactDTO::fromArray($data);
            $fatherDTO = GuardianDTO::fromArray([
                'guardian_id' => $data['student_no'],
                'guardian_name' => $data['father_name'],
                'guardian_phone' => $data['father_phone'],
                'guardian_email' => $data['father_email'] ?? null,
                'guardian_relationship' => 'father'
            ]);
            $motherDTO = GuardianDTO::fromArray([
                'guardian_id' => $data['student_no'],
                'guardian_name' => $data['mother_name'],
                'guardian_phone' => $data['mother_phone'],
                'guardian_email' => $data['mother_email'] ?? null,
                'guardian_relationship' => 'mother'
            ]);
            $emergencyDTO = EmergencyContactDTO::fromArray([
                'emergency_id' => $data['student_no'],
                'emergency_name' => $data['emergency_name'],
                'emergency_phone' => $data['emergency_phone'],
                'emergency_email' => $data['emergency_email'] ?? null,
                'emergency_relationship' => $data['emergency_relationship']
            ]);
            $admissionDTO = AdmissionDTO::fromArray($data);

            // Create user data
            $userData = [
                'user_id' => $data['student_no'],
                'email' => $data['email'] ?: explode('-', $data['student_no'])[2],
                'username' => $data['email'] ?: explode('-', $data['student_no'])[2],
                'password' => password_hash(
                    ucfirst($data['first_name'][0]) . ucfirst($data['last_name']) . '123',
                    PASSWORD_BCRYPT
                ),
                'role_id' => '20',
                'status' => 'inactive'
            ];

            // Insert all records
            $this->studentRepository->createStudent($studentDTO);
            $this->studentRepository->createStudentContact($contactDTO);
            $this->studentRepository->createGuardian($fatherDTO);
            $this->studentRepository->createGuardian($motherDTO);
            $this->studentRepository->createEmergencyContact($emergencyDTO);
            $this->studentRepository->createAdmission($admissionDTO);
            $this->studentRepository->createUser($userData);

            $db->commit();

            return [
                'success' => true,
                'message' => 'Student successfully created',
                'data' => $studentDTO->toArray()
            ];

        } catch (PDOException $e) {
            $db->rollBack();
            throw new \Exception("Database error: " . $e->getMessage());
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function getStudents(int $page = 1, int $limit = 7, ?string $search = null, ?string $status = null): array
    {
        $offset = ($page - 1) * $limit;
        
        $students = $this->studentRepository->getStudentsWithRelations($limit, $offset, $search, $status);
        $total = $this->studentRepository->countStudents($search, $status);
        $pages = ceil($total / $limit);

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

    public function updateStudentStatus(string $studentNo, string $status): array
    {
        if (!$this->studentRepository->studentExists($studentNo)) {
            return [
                'success' => false,
                'message' => 'Student not found'
            ];
        }

        $success = $this->studentRepository->updateStudentStatus($studentNo, $status);
        
        if ($success) {
            return [
                'success' => true,
                'message' => 'Student status updated successfully'
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to update student status'
        ];
    }

    public function generateStudentNo(string $region = "WR", string $district = "TK001", string $school = "LBA", string $admissionDate = ''): string
    {
        return $this->studentRepository->generateStudentNo($region, $district, $school, $admissionDate);
    }

    public function getStudentWithRelations(string $studentNo): ?array
    {
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
        return $result;
    }

    public function exportStudents(): array
    {
        return $this->studentRepository->getAllStudents();
    }

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
                if ($this->studentRepository->studentExists($studentData['student_no'])) {
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

    public function previewImport(array $studentsData): array
    {
        return [
            'total' => count($studentsData),
            'preview' => array_slice($studentsData, 0, 5), // Show first 5 rows
            'headers' => array_keys($studentsData[0] ?? [])
        ];
    }
}
