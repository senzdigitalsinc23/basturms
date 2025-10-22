<?php

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\StudentDTO;
use App\DTOs\StudentContactDTO;
use App\DTOs\GuardianDTO;
use App\DTOs\EmergencyContactDTO;
use App\DTOs\AdmissionDTO;
use PDO;
use PDOException;

class StudentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createStudent(StudentDTO $student): bool
    {
        $sql = "INSERT INTO students (student_no, first_name, last_name, other_name, dob, gender, nhis_no, created_by) 
                VALUES (:student_no, :first_name, :last_name, :other_name, :dob, :gender, :nhis_no, :created_by)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($student->toArray());
    }

    public function createStudentContact(StudentContactDTO $contact): bool
    {
        $sql = "INSERT INTO student_contact (student_no, email, phone, country_id, city, hometown, residence, house_no, gps_no) 
                VALUES (:student_no, :email, :phone, :country_id, :city, :hometown, :residence, :house_no, :gps_no)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($contact->toArray());
    }

    public function createGuardian(GuardianDTO $guardian): bool
    {
        $sql = "INSERT INTO guardian_info (guardian_id, guardian_name, guardian_phone, guardian_email, guardian_relationship) 
                VALUES (:guardian_id, :guardian_name, :guardian_phone, :guardian_email, :guardian_relationship)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($guardian->toArray());
    }

    public function createEmergencyContact(EmergencyContactDTO $emergency): bool
    {
        $sql = "INSERT INTO emergency_contact (emergency_id, emergency_name, emergency_phone, emergency_email, emergency_relationship) 
                VALUES (:emergency_id, :emergency_name, :emergency_phone, :emergency_email, :emergency_relationship)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($emergency->toArray());
    }

    public function createAdmission(AdmissionDTO $admission): bool
    {
        $sql = "INSERT INTO admission_details (student_no, admission_no, admission_status, class_assigned, enrollment_date) 
                VALUES (:student_no, :admission_no, :admission_status, :class_assigned, :enrollment_date)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($admission->toArray());
    }

    public function createUser(array $userData): bool
    {
        $sql = "INSERT INTO users (user_id, email, username, password, role_id, status) 
                VALUES (:user_id, :email, :username, :password, :role_id, :status)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($userData);
    }

    public function findStudentByNo(string $studentNo): ?array
    {
        $sql = "SELECT s.*, sc.email, sc.phone, sc.country_id, sc.city, sc.hometown, sc.residence, sc.house_no, sc.gps_no,
                       ad.admission_no, ad.admission_status, ad.class_assigned, ad.enrollment_date,
                       c.class_name
                FROM students s
                LEFT JOIN student_contact sc ON s.student_no = sc.student_no
                LEFT JOIN admission_details ad ON s.student_no = ad.student_no
                LEFT JOIN classes c ON ad.class_assigned = c.class_id
                WHERE s.student_no = :student_no";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getGuardians(string $studentNo): array
    {
        $sql = "SELECT guardian_id, guardian_name, guardian_phone, guardian_email, guardian_relationship
                FROM guardian_info WHERE guardian_id = :student_no ORDER BY guardian_relationship";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmergencyContact(string $studentNo): ?array
    {
        $sql = "SELECT emergency_id, emergency_name, emergency_phone, emergency_email, emergency_relationship
                FROM emergency_contact WHERE emergency_id = :student_no LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getStudents(int $limit, int $offset, ?string $search = null, ?string $status = null): array
    {
        $sql = "SELECT s.student_no, s.first_name, s.last_name, s.other_name, sc.phone, sc.email, 
                       ad.class_assigned, ad.id, ad.admission_status, c.class_name
                FROM students s
                LEFT JOIN student_contact sc ON s.student_no = sc.student_no
                LEFT JOIN admission_details ad ON s.student_no = ad.student_no
                LEFT JOIN classes c ON ad.class_assigned = c.class_id";
        
        $params = [];
        $whereConditions = [];

        if ($search) {
            $whereConditions[] = "(s.first_name LIKE :search_first 
                                OR s.last_name LIKE :search_last 
                                OR s.student_no LIKE :search_student_no 
                                OR s.other_name LIKE :search_other 
                                OR ad.class_assigned LIKE :search_class)";
                                
            $params[':search_first'] = "%{$search}%";
            $params[':search_last'] = "%{$search}%";
            $params[':search_student_no'] = "%{$search}%";
            $params[':search_other'] = "%{$search}%";
            $params[':search_class'] = "%{$search}%";
        }

        if ($status) {
            $whereConditions[] = "ad.admission_status = :status";
            $params[':status'] = $status;
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $sql .= " ORDER BY s.student_no ASC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;        

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countStudents(?string $search = null, ?string $status = null): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM students s
                LEFT JOIN admission_details ad ON s.student_no = ad.student_no";
        
        $params = [];
        $whereConditions = [];

        if ($search) {
            $whereConditions[] = "(s.first_name LIKE :search_first 
                                OR s.last_name LIKE :search_last 
                                OR s.student_no LIKE :search_student_no 
                                OR s.other_name LIKE :search_other 
                                OR ad.class_assigned LIKE :search_class)";
            $params[':search_first'] = "%{$search}%";
            $params[':search_last'] = "%{$search}%";
            $params[':search_student_no'] = "%{$search}%";
            $params[':search_other'] = "%{$search}%";
            $params[':search_class'] = "%{$search}%";
        }

        if ($status) {
            $whereConditions[] = "ad.admission_status = :status";
            $params[':status'] = $status;
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function updateStudentStatus(string $studentNo, string $status): bool
    {
        $sql = "UPDATE admission_details SET admission_status = :status WHERE student_no = :student_no";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['status' => $status, 'student_no' => $studentNo]);
    }

    public function studentExists(string $studentNo): bool
    {
        $sql = "SELECT COUNT(*) FROM students WHERE student_no = :student_no";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function generateStudentNo(string $region = "WR", string $district = "TK001", string $school = "LBA", string $admissionDate = ''): string
    {
        $year = date("y", strtotime($admissionDate));
        $prefix = "{$region}-{$district}-{$school}{$year}";

        $sql = "SELECT student_no FROM students WHERE student_no LIKE :prefix ORDER BY student_no DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => "%{$prefix}%"]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $lastNumber = 0;
        if ($row) {
            $lastNumber = (int) substr($row['student_no'], -3);
        }

        $nextNo = $lastNumber + 1;
        return $prefix . str_pad($nextNo, 3, '0', STR_PAD_LEFT);
    }

    public function getAllStudents(): array
    {
        $sql = "SELECT s.student_no, s.first_name, s.last_name, s.other_name, sc.phone, sc.email, 
                       ad.admission_no, ad.admission_status, c.class_name
                FROM students s
                LEFT JOIN student_contact sc ON s.student_no = sc.student_no
                LEFT JOIN admission_details ad ON s.student_no = ad.student_no
                LEFT JOIN classes c ON ad.class_assigned = c.class_id
                ORDER BY s.student_no ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentsWithRelations(int $limit, int $offset, ?string $search = null, ?string $status = null): array
    {
        $sql = "SELECT s.student_no, s.first_name, s.last_name, s.other_name, s.dob, s.gender, s.nhis_no, s.created_by,
                       sc.email, sc.phone, sc.country_id, sc.city, sc.hometown, sc.residence, sc.house_no, sc.gps_no,
                       ad.admission_no, ad.admission_status, ad.class_assigned, ad.enrollment_date, ad.id as admission_id,
                       c.class_name
                FROM students s
                LEFT JOIN student_contact sc ON s.student_no = sc.student_no
                LEFT JOIN admission_details ad ON s.student_no = ad.student_no
                LEFT JOIN classes c ON ad.class_assigned = c.class_id";
        
        $params = [];
        $whereConditions = [];

        if ($search) {
            $whereConditions[] = "(s.first_name LIKE :search_first 
                                OR s.last_name LIKE :search_last 
                                OR s.student_no LIKE :search_student_no 
                                OR s.other_name LIKE :search_other 
                                OR ad.class_assigned LIKE :search_class)";
                                
            $params[':search_first'] = "%{$search}%";
            $params[':search_last'] = "%{$search}%";
            $params[':search_student_no'] = "%{$search}%";
            $params[':search_other'] = "%{$search}%";
            $params[':search_class'] = "%{$search}%";
        }

        if ($status) {
            $whereConditions[] = "ad.admission_status = :status";
            $params[':status'] = $status;
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $sql .= " ORDER BY s.student_no ASC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;        

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch guardian and emergency contact data for each student
        foreach ($students as &$student) {
            $student['guardians'] = $this->getGuardians($student['student_no']);
            $student['emergency_contact'] = $this->getEmergencyContact($student['student_no']);
        }

        return $students;
    }

    /**
     * Get student payment history
     */
    public function getStudentPayments(string $studentNo): array
    {
        $sql = "SELECT sp.*, s.first_name, s.last_name 
                FROM payment_history sp
                LEFT JOIN students s ON sp.student_no = s.student_no
                WHERE sp.student_no = :student_no
                ORDER BY sp.payment_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get student attendance history
     */
    public function getStudentAttendance(string $studentNo, int $limit = 30): array
    {
        $sql = "SELECT a.*, s.first_name, s.last_name 
                FROM attendance_history a
                LEFT JOIN students s ON a.student_no = s.student_no
                WHERE a.student_no = :student_no
                ORDER BY a.att_date DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':student_no', $studentNo, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get student bill items
     */
    public function getStudentBillItems(string $studentNo): array
    {
        $sql = "SELECT sbi.*, s.first_name, s.last_name 
                FROM student_bill_items sbi
                LEFT JOIN students s ON sbi.student_no = s.student_no
                WHERE sbi.student_no = :student_no
                ORDER BY sbi.due_date ASC, sbi.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get student clubs
     */
    public function getStudentClubs(string $studentNo): array
    {
        $sql = "SELECT sc.*, s.first_name, s.last_name 
                FROM student_clubs sc
                LEFT JOIN students s ON sc.student_no = s.student_no
                WHERE sc.student_no = :student_no
                ORDER BY sc.join_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get student sports teams
     */
    public function getStudentSportsTeams(string $studentNo): array
    {
        $sql = "SELECT sst.*, s.first_name, s.last_name 
                FROM student_sports_teams sst
                LEFT JOIN students s ON sst.student_no = s.student_no
                WHERE sst.student_no = :student_no
                ORDER BY sst.join_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get student uploaded documents
     */
    public function getStudentDocuments(string $studentNo): array
    {
        $sql = "SELECT sd.*, s.first_name, s.last_name 
                FROM student_documents sd
                LEFT JOIN students s ON sd.student_no = s.student_no
                WHERE sd.student_no = :student_no AND sd.status = 'active'
                ORDER BY sd.uploaded_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
