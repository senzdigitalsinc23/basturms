<?php

namespace App\Repositories;

use App\Core\Database;
use App\Core\Cache;
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
    private Cache $cache;
    private const CACHE_TTL = 3600; // 1 hour
    private const SHORT_CACHE_TTL = 300; // 5 minutes for counts

    /**
     * @param PDO|null $db Optional database connection (defaults to singleton)
     * @param Cache|null $cache Optional cache instance (defaults to new instance)
     */
    public function __construct(?PDO $db = null, ?Cache $cache = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
        $this->cache = $cache ?? new Cache();
    }

    public function createStudent(StudentDTO $student): bool
    {
        $sql = "INSERT INTO students (student_no, first_name, last_name, other_name, dob, gender, nhis_no, created_by) 
                VALUES (:student_no, :first_name, :last_name, :other_name, :dob, :gender, :nhis_no, :created_by)";
        $stmt = $this->db->prepare($sql);
        $params = [
            'student_no' => $student->toArray()['student_no'] ?? '',
            'first_name' => $student->toArray()['first_name'] ?? '',
            'last_name' => $student->toArray()['last_name'] ?? '',
            'other_name' => $student->toArray()['other_name'] ?? null,
            'dob' => $student->toArray()['dob'] ?? '',
            'gender' => $student->toArray()['gender'] ?? '',
            'nhis_no' => $student->toArray()['nhis_no'] ?? '',
            'created_by' => $student->toArray()['created_by'] ?? 0,
        ];
        $success = $stmt->execute($params);
        
        if ($success) {
            // Clear list caches when new student is created
            $this->clearStudentListCaches();
        }
        
        return $success;
    }

    public function createStudentContact(StudentContactDTO $contact): bool
    {
        $sql = "INSERT INTO student_contact (student_no, email, phone, country_id, city, hometown, residence, house_no, gps_no) 
                VALUES (:student_no, :email, :phone, :country_id, :city, :hometown, :residence, :house_no, :gps_no)";
        $stmt = $this->db->prepare($sql);
        $data = $contact->toArray();
        $params = [
            'student_no' => $data['student_no'] ?? '',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'country_id' => $data['country_id'] ?? '',
            'city' => $data['city'] ?? null,
            'hometown' => $data['hometown'] ?? '',
            'residence' => $data['residence'] ?? '',
            'house_no' => $data['house_no'] ?? '',
            'gps_no' => $data['gps_no'] ?? '',
        ];
        return $stmt->execute($params);
    }

    public function createGuardian(GuardianDTO $guardian): bool
    {
        $sql = "INSERT INTO guardian_info (guardian_id, guardian_name, guardian_phone, guardian_email, guardian_relationship) 
                VALUES (:guardian_id, :guardian_name, :guardian_phone, :guardian_email, :guardian_relationship)";
        $stmt = $this->db->prepare($sql);
        $data = $guardian->toArray();
        $params = [
            'guardian_id' => $data['guardian_id'] ?? '',
            'guardian_name' => $data['guardian_name'] ?? '',
            'guardian_phone' => $data['guardian_phone'] ?? '',
            'guardian_email' => $data['guardian_email'] ?? null,
            'guardian_relationship' => $data['guardian_relationship'] ?? '',
        ];
        return $stmt->execute($params);
    }

    public function createEmergencyContact(EmergencyContactDTO $emergency): bool
    {
        $sql = "INSERT INTO emergency_contact (emergency_id, emergency_name, emergency_phone, emergency_email, emergency_relationship) 
                VALUES (:emergency_id, :emergency_name, :emergency_phone, :emergency_email, :emergency_relationship)";
        $stmt = $this->db->prepare($sql);
        $data = $emergency->toArray();
        $params = [
            'emergency_id' => $data['emergency_id'] ?? '',
            'emergency_name' => $data['emergency_name'] ?? '',
            'emergency_phone' => $data['emergency_phone'] ?? '',
            'emergency_email' => $data['emergency_email'] ?? null,
            'emergency_relationship' => $data['emergency_relationship'] ?? '',
        ];
        return $stmt->execute($params);
    }

    public function createAdmission(AdmissionDTO $admission): bool
    {
        $sql = "INSERT INTO admission_details (student_no, admission_no, admission_status, class_assigned, enrollment_date) 
                VALUES (:student_no, :admission_no, :admission_status, :class_assigned, :enrollment_date)";
        $stmt = $this->db->prepare($sql);
        $data = $admission->toArray();
        $params = [
            'student_no' => $data['student_no'] ?? '',
            'admission_no' => $data['admission_no'] ?? '',
            'admission_status' => $data['admission_status'] ?? 'Active',
            'class_assigned' => $data['class_assigned'] ?? '',
            'enrollment_date' => $data['enrollment_date'] ?? '0000-00-00',
        ];
        return $stmt->execute($params);
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
        $cacheKey = "student:{$studentNo}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
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
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        
        if ($result !== null) {
            $this->cache->set($cacheKey, $result, self::CACHE_TTL);
        }
        
        return $result;
    }

    public function getGuardians(string $studentNo): array
    {
        $cacheKey = "student:{$studentNo}:guardians";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $sql = "SELECT guardian_id, guardian_name, guardian_phone, guardian_email, guardian_relationship
                FROM guardian_info WHERE guardian_id = :student_no ORDER BY guardian_relationship";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->cache->set($cacheKey, $result, self::CACHE_TTL);
        
        return $result;
    }

    public function getEmergencyContact(string $studentNo): ?array
    {
        $cacheKey = "student:{$studentNo}:emergency";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $sql = "SELECT emergency_id, emergency_name, emergency_phone, emergency_email, emergency_relationship
                FROM emergency_contact WHERE emergency_id = :student_no LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_no' => $studentNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $result = $row ?: null;
        
        $this->cache->set($cacheKey, $result, self::CACHE_TTL);
        
        return $result;
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
                                OR ad.class_assigned LIKE :search_class
                                OR c.class_name LIKE :search_class_name)";
                                
            $params[':search_first'] = "%{$search}%";
            $params[':search_last'] = "%{$search}%";
            $params[':search_student_no'] = "%{$search}%";
            $params[':search_other'] = "%{$search}%";
            $params[':search_class'] = "%{$search}%";
            $params[':search_class_name'] = "%{$search}%";
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
        $cacheKey = "students:count:" . md5(serialize(['search' => $search, 'status' => $status]));
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return (int) $cached;
        }
        
        $sql = "SELECT COUNT(*) as total 
                FROM students s
                LEFT JOIN admission_details ad ON s.student_no = ad.student_no
                LEFT JOIN classes c ON ad.class_assigned = c.class_id";
        
        $params = [];
        $whereConditions = [];

        if ($search) {
            $whereConditions[] = "(s.first_name LIKE :search_first 
                                OR s.last_name LIKE :search_last 
                                OR s.student_no LIKE :search_student_no 
                                OR s.other_name LIKE :search_other 
                                OR ad.class_assigned LIKE :search_class
                                OR c.class_name LIKE :search_class_name)";
            $params[':search_first'] = "%{$search}%";
            $params[':search_last'] = "%{$search}%";
            $params[':search_student_no'] = "%{$search}%";
            $params[':search_other'] = "%{$search}%";
            $params[':search_class'] = "%{$search}%";
            $params[':search_class_name'] = "%{$search}%";
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
        $result = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Cache counts for shorter duration
        $this->cache->set($cacheKey, $result, self::SHORT_CACHE_TTL);
        
        return $result;
    }

    public function updateStudentStatus(string $studentNo, string $status): bool
    {
        return $this->updateStudentsStatus([$studentNo], $status) > 0;
    }

    /**
     * Update multiple students' admission status in a single query.
     *
     * @param array $studentNos
     * @param string $status
     * @return int number of affected rows
     */
    public function updateStudentsStatus(array $studentNos, string $status): int
    {
        $studentNos = array_values(array_filter(array_map('trim', $studentNos)));
        if (empty($studentNos)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($studentNos), '?'));
        $sql = "UPDATE admission_details SET admission_status = ? WHERE student_no IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$status], $studentNos));
        $affected = $stmt->rowCount();

        if ($affected > 0) {
            foreach ($studentNos as $studentNo) {
                $this->cache->forget("student:{$studentNo}");
                $this->cache->forget("student:{$studentNo}:relations");
            }
            $this->clearStudentListCaches();
        }

        return $affected;
    }

    /**
     * Fetch current admission statuses for a list of students.
     *
     * @param array $studentNos
     * @return array<string,string>
     */
    public function getAdmissionStatuses(array $studentNos): array
    {
        $studentNos = array_values(array_filter(array_map('trim', $studentNos)));
        if (empty($studentNos)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($studentNos), '?'));
        $sql = "SELECT student_no, admission_status FROM admission_details WHERE student_no IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($studentNos);

        $statuses = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $statuses[$row['student_no']] = $row['admission_status'];
        }

        return $statuses;
    }

    /**
     * Mark students as archived and log archive records.
     *
     * @param array $studentNos
     * @param string|null $archivedBy
     * @param string|null $reason
     * @return void
     */
    public function archiveStudents(array $studentNos, $archivedBy = null, ?string $reason = null): void
    {
        $studentNos = array_values(array_filter(array_map('trim', $studentNos)));
        if (empty($studentNos)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($studentNos), '?'));

        // Update students table
        $sqlUpdate = "UPDATE students SET is_archived = 1 WHERE student_no IN ({$placeholders})";
        $stmtUpdate = $this->db->prepare($sqlUpdate);
        $stmtUpdate->execute($studentNos);

        // Insert archive log entries
        $insertSql = "INSERT INTO students_archive (student_no, archived_by, reason, archived_at)
                      VALUES (:student_no, :archived_by, :reason, NOW())";
        $stmtInsert = $this->db->prepare($insertSql);

        foreach ($studentNos as $studentNo) {
            $stmtInsert->execute([
                'student_no' => $studentNo,
                'archived_by' => $archivedBy,
                'reason' => $reason
            ]);
        }
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

    /**
     * Generate admission number in format ADM250001
     * Where ADM is prefix, 25 is 2-digit year, 0001 is sequential number
     * 
     * @param string $enrollmentDate The enrollment date (Y-m-d format)
     * @return string Generated admission number
     */
    public function generateAdmissionNo(string $enrollmentDate = ''): string
    {
        // Use current date if not provided
        if (empty($enrollmentDate)) {
            $enrollmentDate = date('Y-m-d');
        }

        // Extract 2-digit year from enrollment date
        $year = date('y', strtotime($enrollmentDate));
        $prefix = "ADM{$year}";

        // Find the last admission number for this year
        $sql = "SELECT admission_no FROM admission_details 
                WHERE admission_no LIKE :prefix 
                ORDER BY admission_no DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => "{$prefix}%"]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $lastNumber = 0;
        if ($row && !empty($row['admission_no'])) {
            // Extract the number part (last 4 digits)
            $admissionNo = $row['admission_no'];
            // Remove the prefix (ADM + 2 digits year = 5 characters)
            $numberPart = substr($admissionNo, 5);
            $lastNumber = (int) $numberPart;
        }

        // Increment and format with leading zeros (4 digits)
        $nextNo = $lastNumber + 1;
        return $prefix . str_pad($nextNo, 4, '0', STR_PAD_LEFT);
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
                                OR ad.class_assigned LIKE :search_class
                                OR c.class_name LIKE :search_class_name)";
                                
            $params[':search_first'] = "%{$search}%";
            $params[':search_last'] = "%{$search}%";
            $params[':search_student_no'] = "%{$search}%";
            $params[':search_other'] = "%{$search}%";
            $params[':search_class'] = "%{$search}%";
            $params[':search_class_name'] = "%{$search}%";
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

        if (empty($students)) {
            return [];
        }

        // Extract student IDs for batch fetching
        $studentNos = array_column($students, 'student_no');
        
        // Batch fetch relations
        $guardiansMap = $this->getGuardiansByStudentNos($studentNos);
        $emergencyContactsMap = $this->getEmergencyContactsByStudentNos($studentNos);

        // Map data back to students
        foreach ($students as &$student) {
            $studentNo = $student['student_no'];
            $student['guardians'] = $guardiansMap[$studentNo] ?? [];
            $student['emergency_contact'] = $emergencyContactsMap[$studentNo] ?? null;
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
    
    /**
     * Clear all student list-related caches
     */
    private function clearStudentListCaches(): void
    {
        // Note: For pattern-based cache clearing, we'd need to track cache keys
        // For now, we rely on TTL expiration. In production, consider using Redis or Memcached
        // with pattern deletion support, or maintain a cache key registry.
    }
    
    /**
     * Clear cache for a specific student
     */
    public function clearStudentCache(string $studentNo): void
    {
        $this->cache->forget("student:{$studentNo}");
        $this->cache->forget("student:{$studentNo}:relations");
        $this->cache->forget("student:{$studentNo}:guardians");
        $this->cache->forget("student:{$studentNo}:emergency");
    }

    /**
     * Update student basic information
     */
    public function updateStudent(StudentDTO $student): bool
    {
        $sql = "UPDATE students 
                SET first_name = :first_name, last_name = :last_name, other_name = :other_name, 
                    dob = :dob, gender = :gender, nhis_no = :nhis_no
                WHERE student_no = :student_no";
        $stmt = $this->db->prepare($sql);
        $data = $student->toArray();
        $params = [
            'student_no' => $data['student_no'] ?? '',
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'other_name' => $data['other_name'] ?? null,
            'dob' => $data['dob'] ?? '',
            'gender' => $data['gender'] ?? '',
            'nhis_no' => $data['nhis_no'] ?? '',
        ];
        $success = $stmt->execute($params);
        
        if ($success) {
            $this->clearStudentCache($data['student_no']);
        }
        
        return $success;
    }

    /**
     * Update student contact information
     */
    public function updateStudentContact(StudentContactDTO $contact): bool
    {
        $sql = "UPDATE student_contact 
                SET email = :email, phone = :phone, country_id = :country_id, city = :city, 
                    hometown = :hometown, residence = :residence, house_no = :house_no, gps_no = :gps_no
                WHERE student_no = :student_no";
        $stmt = $this->db->prepare($sql);
        $data = $contact->toArray();
        $params = [
            'student_no' => $data['student_no'] ?? '',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'country_id' => $data['country_id'] ?? '',
            'city' => $data['city'] ?? null,
            'hometown' => $data['hometown'] ?? '',
            'residence' => $data['residence'] ?? '',
            'house_no' => $data['house_no'] ?? '',
            'gps_no' => $data['gps_no'] ?? '',
        ];
        $success = $stmt->execute($params);
        
        if ($success) {
            $this->clearStudentCache($data['student_no']);
        }
        
        return $success;
    }

    /**
     * Update admission details
     */
    public function updateAdmission(AdmissionDTO $admission): bool
    {
        $sql = "UPDATE admission_details 
                SET admission_no = :admission_no, admission_status = :admission_status, 
                    class_assigned = :class_assigned, enrollment_date = :enrollment_date
                WHERE student_no = :student_no";
        $stmt = $this->db->prepare($sql);
        $data = $admission->toArray();
        $params = [
            'student_no' => $data['student_no'] ?? '',
            'admission_no' => $data['admission_no'] ?? '',
            'admission_status' => $data['admission_status'] ?? 'Active',
            'class_assigned' => $data['class_assigned'] ?? '',
            'enrollment_date' => $data['enrollment_date'] ?? '0000-00-00',
        ];
        $success = $stmt->execute($params);
        
        if ($success) {
            $this->clearStudentCache($data['student_no']);
        }
        
        return $success;
    }

    /**
     * Delete all guardians for a student
     */
    public function deleteGuardians(string $studentNo): bool
    {
        $sql = "DELETE FROM guardian_info WHERE guardian_id = :student_no";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute(['student_no' => $studentNo]);
        
        if ($success) {
            $this->cache->forget("student:{$studentNo}:guardians");
        }
        
        return $success;
    }

    /**
     * Delete emergency contact for a student
     */
    public function deleteEmergencyContact(string $studentNo): bool
    {
        $sql = "DELETE FROM emergency_contact WHERE emergency_id = :student_no";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute(['student_no' => $studentNo]);
        
        if ($success) {
            $this->cache->forget("student:{$studentNo}:emergency");
        }
        
        return $success;
    }

    /**
     * Delete user account for a student
     */
    public function deleteUser(string $studentNo): bool
    {
        $sql = "DELETE FROM users WHERE user_id = :student_no";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['student_no' => $studentNo]);
    }

    /**
     * Delete admission details for a student
     */
    public function deleteAdmission(string $studentNo): bool
    {
        $sql = "DELETE FROM admission_details WHERE student_no = :student_no";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute(['student_no' => $studentNo]);

        if ($success) {
            $this->cache->forget("student:{$studentNo}:admission");
        }

        return $success;
    }

    /**
     * Delete student contact information
     */
    public function deleteStudentContact(string $studentNo): bool
    {
        $sql = "DELETE FROM student_contact WHERE student_no = :student_no";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute(['student_no' => $studentNo]);

        if ($success) {
            $this->cache->forget("student:{$studentNo}:contact");
        }

        return $success;
    }

    /**
     * Delete main student record
     */
    public function deleteStudent(string $studentNo): bool
    {
        $sql = "DELETE FROM students WHERE student_no = :student_no";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['student_no' => $studentNo]);
    }
    private function getGuardiansByStudentNos(array $studentNos): array
    {
        if (empty($studentNos)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($studentNos), '?'));
        $sql = "SELECT guardian_id, guardian_name, guardian_phone, guardian_email, guardian_relationship 
                FROM guardian_info 
                WHERE guardian_id IN ($placeholders) 
                ORDER BY guardian_relationship";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute($studentNos);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($rows as $row) {
            $result[$row['guardian_id']][] = $row;
        }
        
        return $result;
    }

    private function getEmergencyContactsByStudentNos(array $studentNos): array
    {
        if (empty($studentNos)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($studentNos), '?'));
        $sql = "SELECT emergency_id, emergency_name, emergency_phone, emergency_email, emergency_relationship 
                FROM emergency_contact 
                WHERE emergency_id IN ($placeholders)";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute($studentNos);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($rows as $row) {
            $result[$row['emergency_id']] = $row;
        }
        
        return $result;
    }

    /**
     * Get students for a specific class.
     * 
     * @param string $classId The class ID to filter by
     * @param string|null $status Optional admission status filter
     * @return array List of students in the class
     */
    public function getClassStudents(string $classId, ?string $status = null): array
    {
        $sql = "SELECT s.student_no, s.first_name, s.last_name, s.other_name, s.gender, s.dob,
                       sc.phone, sc.email, 
                       ad.admission_no, ad.enrollment_date, ad.admission_status,
                       c.class_name
                FROM students s
                LEFT JOIN student_contact sc ON s.student_no = sc.student_no
                INNER JOIN admission_details ad ON s.student_no = ad.student_no
                LEFT JOIN classes c ON ad.class_assigned = c.class_id
                WHERE ad.class_assigned = :class_id";
        
        $params = [':class_id' => $classId];
        
        if ($status) {
            $sql .= " AND ad.admission_status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY s.last_name ASC, s.first_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
