<?php

namespace App\Repositories;

use App\Core\Database;
use App\DTOs\StaffDTO;
use App\DTOs\StaffAddressDTO;
use App\DTOs\StaffAcademicHistoryDTO;
use App\DTOs\StaffAppointmentDTO;
use PDO;

class StaffRepository
{
    private PDO $pdo;

    public function __construct(?PDO $db = null)
    {
        $this->pdo = $db ?? Database::getInstance()->getConnection();
    }

    /**
     * Create a new staff member
     */
    public function createStaff(StaffDTO $staff): bool
    {
        $sql = "INSERT INTO staff (
            staff_id, first_name, last_name, other_name, email, phone,
            id_type, id_no, snnit_no, date_of_joining, status, added_on, added_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $staff->staff_id,
            $staff->first_name,
            $staff->last_name,
            $staff->other_name,
            $staff->email,
            $staff->phone,
            $staff->id_type,
            $staff->id_no,
            $staff->snnit_no,
            $staff->date_of_joining,
            $staff->status,
            $staff->added_by
        ]);
    }

    /**
     * Create staff address
     */
    public function createStaffAddress(StaffAddressDTO $address): bool
    {
        $sql = "INSERT INTO staff_address (
            staff_id, country, city, hometown, residence, house_no, gps_no, added_on
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $address->staff_id,
            $address->country,
            $address->city,
            $address->hometown,
            $address->residence,
            $address->house_no,
            $address->gps_no
        ]);
    }

    /**
     * Create staff academic history
     */
    public function createStaffAcademicHistory(StaffAcademicHistoryDTO $history): bool
    {
        $sql = "INSERT INTO staff_academic_history (
            staff_id, school_name, program_offered, qualification, year_completed
        ) VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $history->staff_id,
            $history->school_name,
            $history->program_offered,
            $history->qualification,
            $history->year_completed
        ]);
    }

    /**
     * Get staff academic history
     */
    public function getStaffAcademicHistory(string $staffId): array
    {
        $sql = "SELECT id, school_name, program_offered, qualification, year_completed 
                FROM staff_academic_history 
                WHERE staff_id = ? 
                ORDER BY year_completed DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create staff appointment history
     */
    public function createStaffAppointment(StaffAppointmentDTO $appointment): bool
    {
        $sql = "INSERT INTO staff_appointment_history (
            staff_id, appointment_date, appointment_status, class_teacher_for, created_by, created_on
        ) VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $appointment->staff_id,
            $appointment->appointment_date,
            $appointment->appointment_status,
            $appointment->class_teacher_for,
            $appointment->created_by
        ]);
    }

    /**
     * Assign class to staff
     */
    public function assignClassToStaff(string $staffId, string $classId, string $assignedBy): bool
    {
        $sql = "INSERT INTO staff_class (staff_id, classes_assigned, assigned_by) 
                VALUES (?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId, $classId, $assignedBy]);
    }

    /**
     * Assign subject to staff
     */
    public function assignSubjectToStaff(string $staffId, string $subjectId, string $classId, string $assignedBy): bool
    {
        $sql = "INSERT INTO staff_subjects (staff_id, subject_id, class_id, assigned_by, assigned_on) 
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId, $subjectId, $classId, $assignedBy]);
    }

    /**
     * Assign role to staff
     */
    public function assignRoleToStaff(string $staffId, int $roleId): bool
    {
        $sql = "INSERT INTO staff_roles (staff_id, role_id) VALUES (?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId, $roleId]);
    }

    /**
     * Get staff appointment details
     */
    public function getStaffAppointment(string $staffId): ?array
    {
        $sql = "SELECT * FROM staff_appointment_history 
                WHERE staff_id = ? 
                ORDER BY created_on DESC 
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get staff assigned classes
     */
    public function getStaffClasses(string $staffId): array
    {
        $sql = "SELECT sc.id, sc.classes_assigned as class_id, c.class_name 
                FROM staff_class sc
                LEFT JOIN classes c ON sc.classes_assigned = c.class_id
                WHERE sc.staff_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get staff assigned subjects
     */
    public function getStaffSubjects(string $staffId): array
    {
        $sql = "SELECT ss.id, ss.subject_id, subj.subject_name, ss.class_id, c.class_name
                FROM staff_subjects ss
                LEFT JOIN subjects subj ON ss.subject_id = subj.subject_code
                LEFT JOIN classes c ON ss.class_id = c.class_id
                WHERE ss.staff_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get staff roles
     */
    public function getStaffRoles(string $staffId): array
    {
        $sql = "SELECT sr.id, sr.role_id, r.name as role_name
                FROM staff_roles sr
                LEFT JOIN roles r ON sr.role_id = r.role_id
                WHERE sr.staff_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if staff email already exists
     */
    public function emailExists(string $email, ?string $excludeStaffId = null): bool
    {
        if ($excludeStaffId) {
            $sql = "SELECT COUNT(*) FROM staff WHERE email = ? AND staff_id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email, $excludeStaffId]);
        } else {
            $sql = "SELECT COUNT(*) FROM staff WHERE email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
        }
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if ID number already exists
     */
    public function idNumberExists(string $idNo, ?string $excludeStaffId = null): bool
    {
        if ($excludeStaffId) {
            $sql = "SELECT COUNT(*) FROM staff WHERE id_no = ? AND staff_id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idNo, $excludeStaffId]);
        } else {
            $sql = "SELECT COUNT(*) FROM staff WHERE id_no = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idNo]);
        }
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Generate unique staff ID
     * Format: LBAST{year}{sequence} e.g., LBAST26001
     */
    public function generateStaffId(): string
    {
        $year = date('y'); // Get last 2 digits of year (e.g., 26 for 2026)
        $schoolInitials = 'LBA'; // School initials
        $prefix = $schoolInitials . 'ST' . $year; // e.g., LBAST26
        
        // Get the last staff ID for this year
        $sql = "SELECT staff_id FROM staff 
                WHERE staff_id LIKE ? 
                ORDER BY staff_id DESC LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["{$prefix}%"]);
        $lastId = $stmt->fetchColumn();
        
        if ($lastId) {
            // Extract the sequence number and increment
            // Remove prefix to get sequence (e.g., LBAST26001 -> 001)
            $sequenceStr = substr($lastId, strlen($prefix));
            $sequence = (int)$sequenceStr + 1;
        } else {
            $sequence = 1;
        }
        
        // Format: LBAST26001 (school initials + ST + year + 3-digit sequence)
        return sprintf('%s%03d', $prefix, $sequence);
    }

    /**
     * Get staff by ID
     */
    public function getStaffById(string $staffId, $is_archived = ''): ?array
    {
        $sql = "SELECT DISTINCT s.*, 
                       sa.country, sa.city, sa.hometown, sa.residence, 
                       sa.house_no, sa.gps_no,
                       u.profile_picture_id
                FROM staff s
                LEFT JOIN staff_address sa ON s.staff_id = sa.staff_id
                LEFT JOIN users u ON s.staff_id = u.user_id
                WHERE s.staff_id = ?";

                if ($is_archived ===  '') {
                    $sql .= " AND s.is_archived = 0";
                }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get all staff with pagination
     */
    public function getAllStaff(int $limit = 10, int $offset = 0, string $status = 'active'): array
    {
        $sql = "SELECT DISTINCT s.*, 
                       sa.country, sa.city, sa.hometown, sa.residence, 
                       sa.house_no, sa.gps_no,
                       u.profile_picture_id
                FROM staff s
                LEFT JOIN staff_address sa ON s.staff_id = sa.staff_id
                LEFT JOIN users u ON s.staff_id = u.user_id ";
        
        $params = [];
        
        if ($status !== 'all') {
            $sql .= " WHERE s.is_archived = 0 AND s.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY s.added_on DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count staff members
     */
    public function countStaff(string $status = 'active'): int
    {
        $sql = "SELECT COUNT(*) FROM staff WHERE is_archived = 0";
        $params = [];
        
        if ($status !== 'all') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Update staff record
     */
    public function updateStaff(StaffDTO $staff): bool
    {
        $sql = "UPDATE staff SET 
                first_name = ?, 
                last_name = ?, 
                other_name = ?, 
                email = ?, 
                phone = ?,
                id_type = ?, 
                id_no = ?, 
                snnit_no = ?, 
                date_of_joining = ?, 
                status = ?,
                updated_at = NOW()
                WHERE staff_id = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $staff->first_name,
            $staff->last_name,
            $staff->other_name,
            $staff->email,
            $staff->phone,
            $staff->id_type,
            $staff->id_no,
            $staff->snnit_no,
            $staff->date_of_joining,
            $staff->status,
            $staff->staff_id
        ]);
    }

    /**
     * Update staff address
     */
    public function updateStaffAddress(StaffAddressDTO $address): bool
    {
        // Check if address exists
        $checkSql = "SELECT COUNT(*) FROM staff_address WHERE staff_id = ?";
        $checkStmt = $this->pdo->prepare($checkSql);
        $checkStmt->execute([$address->staff_id]);
        $exists = $checkStmt->fetchColumn() > 0;

        if ($exists) {
            // Update existing address
            $sql = "UPDATE staff_address SET 
                    country = ?, 
                    city = ?, 
                    hometown = ?, 
                    residence = ?, 
                    house_no = ?, 
                    gps_no = ?,
                    updated_at = NOW()
                    WHERE staff_id = ?";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $address->country,
                $address->city,
                $address->hometown,
                $address->residence,
                $address->house_no,
                $address->gps_no,
                $address->staff_id
            ]);
        } else {
            // Create new address
            return $this->createStaffAddress($address);
        }
    }

    /**
     * Delete staff academic history
     */
    public function deleteStaffAcademicHistory(string $staffId): bool
    {
        $sql = "DELETE FROM staff_academic_history WHERE staff_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }

    /**
     * Delete staff appointment history
     */
    public function deleteStaffAppointment(string $staffId): bool
    {
        $sql = "DELETE FROM staff_appointment_history WHERE staff_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }

    /**
     * Delete staff class assignments
     */
    public function deleteStaffClasses(string $staffId): bool
    {
        $sql = "DELETE FROM staff_class WHERE staff_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }

    /**
     * Delete staff subject assignments
     */
    public function deleteStaffSubjects(string $staffId): bool
    {
        $sql = "DELETE FROM staff_subjects WHERE staff_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }

    /**
     * Delete staff role assignments
     */
    public function deleteStaffRoles(string $staffId): bool
    {
        $sql = "DELETE FROM staff_roles WHERE staff_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }

    /**
     * Update staff status
     */
    public function updateStaffStatus(string $staffId, string $status): bool
    {
        $sql = "UPDATE staff SET status = ?, updated_at = NOW() WHERE staff_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $staffId]);
    }

    /**
     * Update user account status
     */
    public function updateUserStatus(string $userId, string $status): bool
    {
        try {
            $sql = "UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$status, $userId]);
        } catch (\Exception $e) {
            // User account may not exist
            return true;
        }
    }

    /**
     * Log status change
     */
    public function logStatusChange(string $staffId, string $oldStatus, string $newStatus, ?string $reason, string $changedBy): bool
    {
        try {
            $sql = "INSERT INTO staff_status_log (staff_id, old_status, new_status, reason, changed_by, changed_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$staffId, $oldStatus, $newStatus, $reason, $changedBy]);
        } catch (\Exception $e) {
            // Table may not exist, continue without logging
            return true;
        }
    }

    /**
     * Archive staff (soft delete with complete data backup)
     */
    public function archiveStaff(string $staffId, ?string $reason, string $archivedBy): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            // Get complete staff data before archiving
            $staffData = $this->getCompleteStaffData($staffId);
            
            if (!$staffData) {
                throw new \Exception("Staff not found");
            }
            
            // Insert into staff_archive table
            $archiveSql = "INSERT INTO staff_archive (staff_id, archive_reason, archived_by, staff_data) 
                          VALUES (?, ?, ?, ?)";
            $archiveStmt = $this->pdo->prepare($archiveSql);
            $archiveStmt->execute([
                $staffId, 
                $reason, 
                $archivedBy, 
                json_encode($staffData)
            ]);
            
            // Update staff table
            $updateSql = "UPDATE staff SET 
                    is_archived = 1, 
                    archived_at = NOW(), 
                    archived_by = ?, 
                    archive_reason = ?,
                    status = 'deleted'
                    WHERE staff_id = ?";
            
            $updateStmt = $this->pdo->prepare($updateSql);
            $updateStmt->execute([$archivedBy, $reason, $staffId]);
            
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get complete staff data for archiving
     */
    private function getCompleteStaffData(string $staffId): ?array
    {
        // Get staff basic info with address
        $staff = $this->getStaffById($staffId);
        if (!$staff) {
            return null;
        }
        
        // Get academic history
        $staff['academic_history'] = $this->getStaffAcademicHistory($staffId);
        
        // Get appointment
        $staff['appointment'] = $this->getStaffAppointment($staffId);
        
        // Get classes
        $staff['classes'] = $this->getStaffClasses($staffId);
        
        // Get subjects
        $staff['subjects'] = $this->getStaffSubjects($staffId);
        
        // Get roles
        $staff['roles'] = $this->getStaffRoles($staffId);
        
        return $staff;
    }

    /**
     * Permanently delete staff (hard delete)
     * CASCADE DELETE will remove all related records
     */
    public function permanentlyDeleteStaff(string $staffId): bool
    {
        // Delete user account first (if foreign key doesn't exist)
        try {
            $sql = "DELETE FROM users WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$staffId]);
        } catch (\Exception $e) {
            // Continue even if user deletion fails
        }

        // Delete staff record (CASCADE will handle related tables)
        $sql = "DELETE FROM staff WHERE staff_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }

    /**
     * Check if phone number already exists
     */
    public function phoneExists(string $phone, ?string $excludeStaffId = null): bool
    {
        if ($excludeStaffId) {
            $sql = "SELECT COUNT(*) FROM staff WHERE phone = ? AND staff_id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$phone, $excludeStaffId]);
        } else {
            $sql = "SELECT COUNT(*) FROM staff WHERE phone = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$phone]);
        }
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if SSNIT number already exists
     */
    public function ssnitNoExists(string $ssnitNo, ?string $excludeStaffId = null): bool
    {
        // Skip check if SSNIT number is empty
        if (empty($ssnitNo)) {
            return false;
        }
        
        if ($excludeStaffId) {
            $sql = "SELECT COUNT(*) FROM staff WHERE snnit_no = ? AND staff_id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$ssnitNo, $excludeStaffId]);
        } else {
            $sql = "SELECT COUNT(*) FROM staff WHERE snnit_no = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$ssnitNo]);
        }
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if username already exists in users table
     */
    public function usernameExists(string $username, ?string $excludeUserId = null): bool
    {
        if ($excludeUserId) {
            $sql = "SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$username, $excludeUserId]);
        } else {
            $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$username]);
        }
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if email already exists in users table
     */
    public function userEmailExists(string $email, ?string $excludeUserId = null): bool
    {
        if ($excludeUserId) {
            $sql = "SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email, $excludeUserId]);
        } else {
            $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
        }
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get staff by role
     */
    public function getStaffByRole(int $roleId, int $limit = 10, int $offset = 0, string $status = 'active'): array
    {
        $sql = "SELECT DISTINCT s.*, 
                       sa.country, sa.city, sa.hometown, sa.residence, 
                       sa.house_no, sa.gps_no
                FROM staff s
                LEFT JOIN staff_address sa ON s.staff_id = sa.staff_id
                INNER JOIN staff_roles sr ON s.staff_id = sr.staff_id
                WHERE sr.role_id = ? AND s.is_archived = 0";
        
        $params = [$roleId];
        
        if ($status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY s.added_on DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count staff by role
     */
    public function countStaffByRole(int $roleId, string $status = 'active'): int
    {
        $sql = "SELECT COUNT(DISTINCT s.staff_id) 
                FROM staff s
                INNER JOIN staff_roles sr ON s.staff_id = sr.staff_id
                WHERE sr.role_id = ? AND s.is_archived = 0";
        
        $params = [$roleId];
        
        if ($status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get staff by class
     */
    public function getStaffByClass(string $classId, int $limit = 10, int $offset = 0, string $status = 'active'): array
    {
        $sql = "SELECT DISTINCT s.*, 
                       sa.country, sa.city, sa.hometown, sa.residence, 
                       sa.house_no, sa.gps_no
                FROM staff s
                LEFT JOIN staff_address sa ON s.staff_id = sa.staff_id
                INNER JOIN staff_class sc ON s.staff_id = sc.staff_id
                WHERE sc.classes_assigned = ? AND s.is_archived = 0";
        
        $params = [$classId];
        
        if ($status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY s.added_on DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count staff by class
     */
    public function countStaffByClass(string $classId, string $status = 'active'): int
    {
        $sql = "SELECT COUNT(DISTINCT s.staff_id) 
                FROM staff s
                INNER JOIN staff_class sc ON s.staff_id = sc.staff_id
                WHERE sc.classes_assigned = ? AND s.is_archived = 0";
        
        $params = [$classId];
        
        if ($status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get staff by subject
     */
    public function getStaffBySubject(string $subjectId, int $limit = 10, int $offset = 0, string $status = 'active'): array
    {
        $sql = "SELECT DISTINCT s.*, 
                       sa.country, sa.city, sa.hometown, sa.residence, 
                       sa.house_no, sa.gps_no
                FROM staff s
                LEFT JOIN staff_address sa ON s.staff_id = sa.staff_id
                INNER JOIN staff_subjects ss ON s.staff_id = ss.staff_id
                WHERE ss.subject_id = ? AND s.is_archived = 0";
        
        $params = [$subjectId];
        
        if ($status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY s.added_on DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count staff by subject
     */
    public function countStaffBySubject(string $subjectId, string $status = 'active'): int
    {
        $sql = "SELECT COUNT(DISTINCT s.staff_id) 
                FROM staff s
                INNER JOIN staff_subjects ss ON s.staff_id = ss.staff_id
                WHERE ss.subject_id = ? AND s.is_archived = 0";
        
        $params = [$subjectId];
        
        if ($status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Search staff by name or email
     */
    public function searchStaff(string $searchTerm, int $limit = 10, int $offset = 0, string $status = 'active'): array
    {
        $searchPattern = "%{$searchTerm}%";
        
        $sql = "SELECT DISTINCT s.*, 
                       sa.country, sa.city, sa.hometown, sa.residence, 
                       sa.house_no, sa.gps_no
                FROM staff s
                LEFT JOIN staff_address sa ON s.staff_id = sa.staff_id
                WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.other_name LIKE ? 
                       OR s.email LIKE ? OR s.staff_id LIKE ?)
                AND s.is_archived = 0";
        
        $params = [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern];
        
        if ($status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY s.added_on DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count search results
     */
    public function countSearchResults(string $searchTerm, string $status = 'active'): int
    {
        $searchPattern = "%{$searchTerm}%";
        
        $sql = "SELECT COUNT(DISTINCT s.staff_id) 
                FROM staff s
                WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.other_name LIKE ? 
                       OR s.email LIKE ? OR s.staff_id LIKE ?)
                AND s.is_archived = 0";
        
        $params = [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern];
        
        if ($status !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Deactivate staff user account
     */
    public function deactivateUserAccount(string $staffId): bool
    {
        $sql = "UPDATE users SET status = 'inactive' WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }

    /**
     * Activate staff user account
     */
    public function activateUserAccount(string $staffId): bool
    {
        $sql = "UPDATE users SET status = 'active' WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }

    /**
     * Assign classes to staff
     */
    public function assignClasses(string $staffId, array $classIds, string $assignedBy): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            $sql = "INSERT INTO staff_class (staff_id, classes_assigned, assigned_by) 
                    VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($classIds as $classId) {
                $stmt->execute([$staffId, $classId, $assignedBy]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Assign subjects to staff for specific classes
     */
    public function assignSubjects(string $staffId, array $assignments, string $assignedBy): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            $sql = "INSERT INTO staff_subjects (staff_id, subject_id, class_id, assigned_by) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($assignments as $assignment) {
                $stmt->execute([
                    $staffId, 
                    $assignment['subject_id'], 
                    $assignment['class_id'], 
                    $assignedBy
                ]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get staff class assignments
     */
    public function getStaffClassAssignments(string $staffId): array
    {
        $sql = "SELECT sc.id, sc.classes_assigned as class_id, c.class_name, 
                       sc.assigned_by, u.username as assigned_by_name
                FROM staff_class sc
                LEFT JOIN classes c ON sc.classes_assigned = c.class_id
                LEFT JOIN users u ON sc.assigned_by = u.user_id
                WHERE sc.staff_id = ?
                ORDER BY c.class_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get staff subject assignments
     */
    public function getStaffSubjectAssignments(string $staffId): array
    {
        $sql = "SELECT ss.id, ss.subject_id, s.subject_name, ss.class_id, c.class_name,
                       ss.assigned_by, u.username as assigned_by_name, ss.assigned_on
                FROM staff_subjects ss
                LEFT JOIN subjects s ON ss.subject_id = s.subject_id
                LEFT JOIN classes c ON ss.class_id = c.class_id
                LEFT JOIN users u ON ss.assigned_by = u.user_id
                WHERE ss.staff_id = ?
                ORDER BY c.class_name, s.subject_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Remove class assignment
     */
    public function removeClassAssignment(string $staffId, string $classId): bool
    {
        $sql = "DELETE FROM staff_class WHERE staff_id = ? AND classes_assigned = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId, $classId]);
    }

    /**
     * Remove subject assignment
     */
    public function removeSubjectAssignment(string $staffId, string $subjectId, string $classId): bool
    {
        $sql = "DELETE FROM staff_subjects WHERE staff_id = ? AND subject_id = ? AND class_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId, $subjectId, $classId]);
    }

    /**
     * Remove all subject assignments for a staff member in a specific class
     */
    public function removeSubjectAssignmentsByClass(string $staffId, string $classId): bool
    {
        $sql = "DELETE FROM staff_subjects WHERE staff_id = ? AND class_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId, $classId]);
    }

    /**
     * Check if class is already assigned to staff
     */
    public function isClassAssigned(string $staffId, string $classId): bool
    {
        $sql = "SELECT COUNT(*) FROM staff_class WHERE staff_id = ? AND classes_assigned = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId, $classId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Check if subject is already assigned to staff for a class
     */
    public function isSubjectAssigned(string $staffId, string $subjectId, string $classId): bool
    {
        $sql = "SELECT COUNT(*) FROM staff_subjects WHERE staff_id = ? AND subject_id = ? AND class_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$staffId, $subjectId, $classId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Remove all class assignments for staff
     */
    public function removeAllClassAssignments(string $staffId): bool
    {
        $sql = "DELETE FROM staff_class WHERE staff_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }

    /**
     * Remove all subject assignments for staff
     */
    public function removeAllSubjectAssignments(string $staffId): bool
    {
        $sql = "DELETE FROM staff_subjects WHERE staff_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$staffId]);
    }
}
