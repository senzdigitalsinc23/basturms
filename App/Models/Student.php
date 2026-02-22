<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use Database\ORM\Model;

class Student extends Model
{

    
    protected static string $table = 'students';

    public int $id;
    public string $name;
    public string $email;
    public string $password;
    public ?string $created_at;
    public ?string $updated_at;
    public string $status;
    public ?string $is_super_admin;
    public ?int $role_id;

    /**
     * Hide password when converting to array.
     */
    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'student_id'    => $this->student_id,
            'first_name'  => $this->first_name,
            'surname_name'  => $this->surname_name,
            'other_name(s)'  => $this->other_name,
            'dob'   => $this->dob,
            'gender'  => $this->gender,
            'phone'  => $this->phone,
            'nationality'  => $this->nationality,
            'city'  => $this->city,
            'hometown'  => $this->hometown,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by'  => $this->created_by
        ];
    }

    public function relations()  {
        
    }

    public function __construct() {
        //$this->db = Database::getInstance()->getConnection();
    }

     public static function all($newTable = '')
    {
        $table = $newTable != '' ? $newTable : static::$table;
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT  adm.id, stu.student_no, stu.first_name,  stu.other_name, stu.last_name, cont.phone, cont.email, cl.class_name, adm.class_assigned, adm.admission_status
                FROM students stu
                LEFT JOIN student_contact cont
                ON stu.student_no = cont.student_no
                LEFT JOIN admission_details adm
                ON stu.student_no = adm.student_no
                LEFT JOIN classes cl 
                ON adm.class_assigned = cl.class_id 
                ORDER BY student_no ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }

    public static function paginate($limit = 10, $offset = 0, $orderBy = '', $order = 'ASC')
    {
        $db = Database::getInstance()->getConnection();

        $table = static::$table;
        
        // SECURITY: Whitelist allowed ORDER BY columns to prevent SQL injection
        $allowedOrderColumns = [
            'student_no', 'first_name', 'last_name', 'other_name',
            'phone', 'email', 'class_name', 'admission_status', 'class_assigned'
        ];
        
        // Validate and sanitize ORDER BY column
        if (!empty($orderBy) && !in_array($orderBy, $allowedOrderColumns, true)) {
            // Default to safe column if invalid column provided
            $orderBy = 'student_no';
        }
        
        // Validate ORDER direction
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'ASC';
        }
        
        $sql = "SELECT stu.student_no, stu.first_name, stu.last_name, stu.other_name, cont.phone, cont.email, adm.class_assigned, adm.id, adm.admission_status, cl.class_name
                FROM students stu
                LEFT JOIN student_contact cont
                ON stu.student_no = cont.student_no
                LEFT JOIN admission_details adm
                ON stu.student_no = adm.student_no
                LEFT JOIN classes cl 
                ON adm.class_assigned = cl.class_id 
                WHERE adm.admission_status = 'active'";       

        // Safe to use validated $orderBy and $order in query
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy} {$order}";
        }
        
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    public static function search($limit = 10, $offset = 0, $search = null, $status = 'active', $orderBy = '', $order = 'ASC') {
        $db = Database::getInstance()->getConnection();

        // SECURITY: Whitelist allowed ORDER BY columns to prevent SQL injection
        $allowedOrderColumns = [
            'student_no', 'first_name', 'last_name', 'other_name',
            'phone', 'email', 'class_name', 'admission_status', 'class_assigned'
        ];
        
        // Validate and sanitize ORDER BY column
        if (!empty($orderBy) && !in_array($orderBy, $allowedOrderColumns, true)) {
            $orderBy = 'student_no'; // Default to safe column
        }
        
        // Validate ORDER direction
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'ASC';
        }

        $sql = "SELECT stu.student_no, stu.first_name, stu.last_name, stu.other_name, cont.phone, cont.email, adm.id, adm.class_assigned, adm.admission_status, cl.class_name
                FROM students stu
                LEFT JOIN student_contact cont
                ON stu.student_no = cont.student_no
                LEFT JOIN admission_details adm
                ON stu.student_no = adm.student_no
                LEFT JOIN classes cl 
                ON adm.class_assigned = cl.class_id";
                                
        $params = [];

        if ($search) {
            $sql .= " WHERE (first_name LIKE :first_name 
                    OR last_name LIKE :last_name 
                    OR stu.student_no LIKE :student_no 
                    OR other_name LIKE :other_name 
                    OR adm.class_assigned LIKE :class_assigned) 
                    AND adm.admission_status = :status";

            $params[':first_name'] = "%{$search}%";
            $params[':last_name'] = "%{$search}%";
            $params[':other_name'] = "%{$search}%";
            $params[':student_no'] = "%{$search}%";
            $params[':class_assigned'] = "%{$search}%";

        }else if ($status) {
            $sql .= " WHERE adm.admission_status = :status";
        }

        // Safe to use validated $orderBy and $order in query
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy} {$order}";
        }
        
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, \PDO::PARAM_STR);

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function delete ($id, $admission_status = 'inactive')
    {
        
        $db = Database::getInstance()->getConnection();

        //echo json_encode(['success' => true, 'message' => 'Student deleted successfully ' . $id]);exit;

        try {
            // soft delete
            $stmt = $db->prepare("UPDATE `admission_details` adm SET adm.`admission_status` = :admission_status WHERE adm.`id` = :id");

            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->bindValue(':admission_status', $admission_status);
            $stmt->execute();

            return response()->json(['success' => true, 'message' => 'Student freezed successfully']);
        } catch (\Exception $e) {
            http_response_code(500);
            return response()->json(['success' => false, 'message' => 'Error deleting student: ' . $e]);
        }
    }

    public static function countStudents($status = 'active', $search = '')
    {
        $db = Database::getInstance()->getConnection();


        $sql = "SELECT COUNT(*) as total FROM students stu
                LEFT JOIN admission_details adm
                ON stu.student_no = adm.student_no";

        /* if ($search != '') { */
            $sql .= " WHERE (first_name LIKE :first_name 
                    OR last_name LIKE :last_name 
                    OR stu.student_no LIKE :student_no 
                    OR other_name LIKE :other_name 
                    OR adm.class_assigned LIKE :class_assigned) 
                    AND adm.admission_status = :status";

            $params[':first_name'] = "%{$search}%";
            $params[':last_name'] = "%{$search}%";
            $params[':other_name'] = "%{$search}%";
            $params[':student_no'] = "%{$search}%";
            $params[':class_assigned'] = "%{$search}%";

        /* }else if ($status) {
            $sql .= " WHERE adm.admission_status = :status";
        } */


        $stmt = $db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        $stmt->bindValue('status', $status);
        
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
    }


    public static function generateStudentNo($region = "WR", $district = "TK001", $school = "LBA", $admissionDate = '')
    {
        $db = Database::getInstance()->getConnection();
        $year = date("y", strtotime($admissionDate)); // 25 for 2025
        $prefix = "{$region}-{$district}-{$school}{$year}";

        //

        // Check if prefix exists
        $stmt = $db->prepare("SELECT student_no FROM students  WHERE student_no LIKE :prefix ORDER BY student_no DESC LIMIT 1");
        $stmt->execute([':prefix' => "%{$prefix}%"]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $last_number = 0;
        $nextNo = 1;

      if ($row) {
            $last_number = (int) substr($row['student_no'], -3);
            $nextNo =  $last_number + 1;
        } else {
            $nextNo = 1;
        }

        // Pad with leading zeros
        $studentNo = $prefix . str_pad($nextNo, 3, '0', STR_PAD_LEFT);

        return $studentNo;
    }
    

}
