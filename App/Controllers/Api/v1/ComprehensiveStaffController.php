<?php

namespace App\Controllers\Api\v1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use PDO;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Comprehensive Staff Management",
    description: "Full staff management with complete personal, employment, and professional details"
)]
class ComprehensiveStaffController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    #[OA\Post(
        path: "/api/v1/staff/comprehensive/create",
        summary: "Create comprehensive staff record",
        description: "Create a new staff member with complete details including personal info, contact, employment, etc.",
        tags: ["Comprehensive Staff Management"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password", "personal_info", "contact_info", "employment_info"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "password", type: "string"),
                    new OA\Property(property: "role", type: "string", enum: ["staff", "incharge", "accountant", "admin"]),
                    new OA\Property(
                        property: "personal_info",
                        type: "object",
                        properties: [
                            new OA\Property(property: "title", type: "string"),
                            new OA\Property(property: "first_name", type: "string"),
                            new OA\Property(property: "middle_name", type: "string"),
                            new OA\Property(property: "last_name", type: "string"),
                            new OA\Property(property: "date_of_birth", type: "string", format: "date"),
                            new OA\Property(property: "gender", type: "string"),
                            new OA\Property(property: "marital_status", type: "string"),
                            new OA\Property(property: "nationality", type: "string"),
                            new OA\Property(property: "national_id_type", type: "string"),
                            new OA\Property(property: "national_id_number", type: "string"),
                            new OA\Property(property: "ssnit_number", type: "string"),
                        ]
                    ),
                    new OA\Property(
                        property: "contact_info",
                        type: "object",
                        properties: [
                            new OA\Property(property: "primary_phone", type: "string"),
                            new OA\Property(property: "secondary_phone", type: "string"),
                            new OA\Property(property: "personal_email", type: "string"),
                            new OA\Property(property: "residential_address", type: "string"),
                            new OA\Property(property: "residential_city", type: "string"),
                            new OA\Property(property: "residential_region", type: "string"),
                        ]
                    ),
                    new OA\Property(
                        property: "employment_info",
                        type: "object",
                        properties: [
                            new OA\Property(property: "employee_number", type: "string"),
                            new OA\Property(property: "staff_category", type: "string"),
                            new OA\Property(property: "employment_type", type: "string"),
                            new OA\Property(property: "date_of_first_appointment", type: "string", format: "date"),
                            new OA\Property(property: "department_id", type: "string"),
                            new OA\Property(property: "unit_id", type: "string"),
                            new OA\Property(property: "position_title", type: "string"),
                            new OA\Property(property: "job_grade", type: "string"),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Staff created successfully"),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function createComprehensive(Request $request, Response $response): Response
    {
        try {
            $data = $request->getPost();
            
            // Validate required fields
            if (empty($data['email']) || empty($data['password']) || 
                empty($data['personal_info']) || empty($data['contact_info']) || 
                empty($data['employment_info'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Missing required fields'
                ], 400);
            }

            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM validation_staff WHERE email = :email");
            $stmt->execute(['email' => $data['email']]);
            if ($stmt->fetch()) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Email already exists'
                ], 400);
            }

            $this->db->beginTransaction();

            try {
                // 1. Create main staff record
                $personalInfo = $data['personal_info'];
                $fullName = trim(($personalInfo['first_name'] ?? '') . ' ' . 
                               ($personalInfo['middle_name'] ?? '') . ' ' . 
                               ($personalInfo['last_name'] ?? ''));

                $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
                $role = $data['role'] ?? 'staff';

                $stmt = $this->db->prepare("
                    INSERT INTO validation_staff (name, email, password, role, unit_id)
                    VALUES (:name, :email, :password, :role, :unit_id)
                ");
                $stmt->execute([
                    'name' => $fullName,
                    'email' => $data['email'],
                    'password' => $hashedPassword,
                    'role' => $role,
                    'unit_id' => $data['employment_info']['unit_id'] ?? null
                ]);
                
                $staffId = (int)$this->db->lastInsertId();

                // 2. Insert personal information
                $this->insertPersonalInfo($staffId, $personalInfo);

                // 3. Insert contact information
                $this->insertContactInfo($staffId, $data['contact_info']);

                // 4. Insert employment information
                $this->insertEmploymentInfo($staffId, $data['employment_info']);

                // 5. Insert emergency contacts if provided
                if (!empty($data['emergency_contacts'])) {
                    foreach ($data['emergency_contacts'] as $contact) {
                        $this->insertEmergencyContact($staffId, $contact);
                    }
                }

                // 6. Insert qualifications if provided
                if (!empty($data['qualifications'])) {
                    foreach ($data['qualifications'] as $qualification) {
                        $this->insertQualification($staffId, $qualification);
                    }
                }

                // 7. Insert bank information if provided
                if (!empty($data['bank_info'])) {
                    $this->insertBankInfo($staffId, $data['bank_info']);
                }

                // 8. Insert dependents if provided
                if (!empty($data['dependents'])) {
                    foreach ($data['dependents'] as $dependent) {
                        $this->insertDependent($staffId, $dependent);
                    }
                }

                $this->db->commit();

                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Staff created successfully',
                    'staff_id' => $staffId
                ], 201);

            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to create staff: ' . $e->getMessage()
            ], 500);
        }
    }

    private function insertPersonalInfo(int $staffId, array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO staff_personal_info (
                staff_id, title, first_name, middle_name, last_name, maiden_name,
                date_of_birth, gender, marital_status, nationality, national_id_type,
                national_id_number, ssnit_number, tin_number
            ) VALUES (
                :staff_id, :title, :first_name, :middle_name, :last_name, :maiden_name,
                :date_of_birth, :gender, :marital_status, :nationality, :national_id_type,
                :national_id_number, :ssnit_number, :tin_number
            )
        ");

        $stmt->execute([
            'staff_id' => $staffId,
            'title' => $data['title'] ?? 'Mr',
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'maiden_name' => $data['maiden_name'] ?? null,
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'marital_status' => $data['marital_status'] ?? 'Single',
            'nationality' => $data['nationality'] ?? 'Ghanaian',
            'national_id_type' => $data['national_id_type'] ?? 'Ghana Card',
            'national_id_number' => $data['national_id_number'] ?? null,
            'ssnit_number' => $data['ssnit_number'] ?? null,
            'tin_number' => $data['tin_number'] ?? null
        ]);
    }

    private function insertContactInfo(int $staffId, array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO staff_contact_info (
                staff_id, primary_phone, secondary_phone, personal_email, work_email,
                residential_address, residential_city, residential_region, residential_gps_address,
                postal_address, hometown, home_region
            ) VALUES (
                :staff_id, :primary_phone, :secondary_phone, :personal_email, :work_email,
                :residential_address, :residential_city, :residential_region, :residential_gps_address,
                :postal_address, :hometown, :home_region
            )
        ");

        $stmt->execute([
            'staff_id' => $staffId,
            'primary_phone' => $data['primary_phone'],
            'secondary_phone' => $data['secondary_phone'] ?? null,
            'personal_email' => $data['personal_email'] ?? null,
            'work_email' => $data['work_email'] ?? null,
            'residential_address' => $data['residential_address'],
            'residential_city' => $data['residential_city'] ?? null,
            'residential_region' => $data['residential_region'] ?? null,
            'residential_gps_address' => $data['residential_gps_address'] ?? null,
            'postal_address' => $data['postal_address'] ?? null,
            'hometown' => $data['hometown'] ?? null,
            'home_region' => $data['home_region'] ?? null
        ]);
    }

    private function insertEmploymentInfo(int $staffId, array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO staff_employment_info (
                staff_id, employee_number, staff_category, employment_type, employment_status,
                date_of_first_appointment, date_of_current_appointment, confirmation_date,
                department_id, unit_id, position_title, job_grade, salary_grade, step_level,
                reports_to, work_location
            ) VALUES (
                :staff_id, :employee_number, :staff_category, :employment_type, :employment_status,
                :date_of_first_appointment, :date_of_current_appointment, :confirmation_date,
                :department_id, :unit_id, :position_title, :job_grade, :salary_grade, :step_level,
                :reports_to, :work_location
            )
        ");

        $stmt->execute([
            'staff_id' => $staffId,
            'employee_number' => $data['employee_number'] ?? null,
            'staff_category' => $data['staff_category'],
            'employment_type' => $data['employment_type'],
            'employment_status' => $data['employment_status'] ?? 'Active',
            'date_of_first_appointment' => $data['date_of_first_appointment'],
            'date_of_current_appointment' => $data['date_of_current_appointment'] ?? null,
            'confirmation_date' => $data['confirmation_date'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'unit_id' => $data['unit_id'] ?? null,
            'position_title' => $data['position_title'],
            'job_grade' => $data['job_grade'] ?? null,
            'salary_grade' => $data['salary_grade'] ?? null,
            'step_level' => $data['step_level'] ?? null,
            'reports_to' => $data['reports_to'] ?? null,
            'work_location' => $data['work_location'] ?? null
        ]);
    }

    private function insertEmergencyContact(int $staffId, array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO staff_emergency_contacts (
                staff_id, contact_name, relationship, phone_number, 
                alternative_phone, address, is_primary
            ) VALUES (
                :staff_id, :contact_name, :relationship, :phone_number,
                :alternative_phone, :address, :is_primary
            )
        ");

        $stmt->execute([
            'staff_id' => $staffId,
            'contact_name' => $data['contact_name'],
            'relationship' => $data['relationship'],
            'phone_number' => $data['phone_number'],
            'alternative_phone' => $data['alternative_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_primary' => $data['is_primary'] ?? false
        ]);
    }

    private function insertQualification(int $staffId, array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO staff_qualifications (
                staff_id, qualification_type, institution_name, qualification_name,
                field_of_study, grade_obtained, start_date, completion_date,
                certificate_number, is_highest_qualification
            ) VALUES (
                :staff_id, :qualification_type, :institution_name, :qualification_name,
                :field_of_study, :grade_obtained, :start_date, :completion_date,
                :certificate_number, :is_highest_qualification
            )
        ");

        $stmt->execute([
            'staff_id' => $staffId,
            'qualification_type' => $data['qualification_type'],
            'institution_name' => $data['institution_name'],
            'qualification_name' => $data['qualification_name'],
            'field_of_study' => $data['field_of_study'] ?? null,
            'grade_obtained' => $data['grade_obtained'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'completion_date' => $data['completion_date'] ?? null,
            'certificate_number' => $data['certificate_number'] ?? null,
            'is_highest_qualification' => $data['is_highest_qualification'] ?? false
        ]);
    }

    private function insertBankInfo(int $staffId, array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO staff_bank_info (
                staff_id, bank_name, branch_name, account_number,
                account_name, account_type, is_primary
            ) VALUES (
                :staff_id, :bank_name, :branch_name, :account_number,
                :account_name, :account_type, :is_primary
            )
        ");

        $stmt->execute([
            'staff_id' => $staffId,
            'bank_name' => $data['bank_name'],
            'branch_name' => $data['branch_name'] ?? null,
            'account_number' => $data['account_number'],
            'account_name' => $data['account_name'],
            'account_type' => $data['account_type'] ?? 'Savings',
            'is_primary' => $data['is_primary'] ?? true
        ]);
    }

    private function insertDependent(int $staffId, array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO staff_dependents (
                staff_id, full_name, relationship, date_of_birth,
                gender, is_beneficiary
            ) VALUES (
                :staff_id, :full_name, :relationship, :date_of_birth,
                :gender, :is_beneficiary
            )
        ");

        $stmt->execute([
            'staff_id' => $staffId,
            'full_name' => $data['full_name'],
            'relationship' => $data['relationship'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'is_beneficiary' => $data['is_beneficiary'] ?? false
        ]);
    }

    #[OA\Get(
        path: "/api/v1/staff/comprehensive/{id}",
        summary: "Get comprehensive staff details",
        description: "Retrieve complete staff information including all related data",
        tags: ["Comprehensive Staff Management"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Staff details retrieved successfully"),
            new OA\Response(response: 404, description: "Staff not found")
        ]
    )]
    public function getComprehensive(Request $request, Response $response, array $params): Response
    {
        try {
            $staffId = $params['id'] ?? null;
            
            if (!$staffId) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Staff ID is required'
                ], 400);
            }

            // Get main staff record
            $stmt = $this->db->prepare("
                SELECT s.*, u.name as unit_name
                FROM validation_staff s
                LEFT JOIN units u ON s.unit_id = u.id
                WHERE s.id = :id AND s.deleted_at IS NULL
            ");
            $stmt->execute(['id' => $staffId]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$staff) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'message' => 'Staff not found'
                ], 404);
            }

            // Get personal info
            $stmt = $this->db->prepare("SELECT * FROM staff_personal_info WHERE staff_id = :staff_id");
            $stmt->execute(['staff_id' => $staffId]);
            $staff['personal_info'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get contact info
            $stmt = $this->db->prepare("SELECT * FROM staff_contact_info WHERE staff_id = :staff_id");
            $stmt->execute(['staff_id' => $staffId]);
            $staff['contact_info'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get employment info
            $stmt = $this->db->prepare("
                SELECT e.*, d.name as department_name, u.name as unit_name
                FROM staff_employment_info e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN units u ON e.unit_id = u.id
                WHERE e.staff_id = :staff_id
            ");
            $stmt->execute(['staff_id' => $staffId]);
            $staff['employment_info'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get emergency contacts
            $stmt = $this->db->prepare("SELECT * FROM staff_emergency_contacts WHERE staff_id = :staff_id");
            $stmt->execute(['staff_id' => $staffId]);
            $staff['emergency_contacts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get qualifications
            $stmt = $this->db->prepare("SELECT * FROM staff_qualifications WHERE staff_id = :staff_id ORDER BY completion_date DESC");
            $stmt->execute(['staff_id' => $staffId]);
            $staff['qualifications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get bank info
            $stmt = $this->db->prepare("SELECT * FROM staff_bank_info WHERE staff_id = :staff_id");
            $stmt->execute(['staff_id' => $staffId]);
            $staff['bank_info'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get dependents
            $stmt = $this->db->prepare("SELECT * FROM staff_dependents WHERE staff_id = :staff_id");
            $stmt->execute(['staff_id' => $staffId]);
            $staff['dependents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->jsonResponse($response, [
                'success' => true,
                'staff' => $staff
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'message' => 'Failed to retrieve staff: ' . $e->getMessage()
            ], 500);
        }
    }

    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response
    {
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data));
        $response->setStatusCode($statusCode);
        return $response;
    }
}
