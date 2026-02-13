<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class TeacherSubjectRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function assign(string $staffId, int $subjectId, ?int $classId, ?string $academicYear, string $assignedBy): array
    {
        $sql = "
            INSERT INTO staff_subjects (staff_id, subject_id, class_id, academic_year, assigned_by, assigned_on)
            VALUES (:staff_id, :subject_id, :class_id, :academic_year, :assigned_by, NOW())
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':staff_id' => $staffId,
            ':subject_id' => $subjectId,
            ':class_id' => $classId,
            ':academic_year' => $academicYear,
            ':assigned_by' => $assignedBy,
        ]);

        return [
            'id' => $this->db->lastInsertId(),
            'staff_id' => $staffId,
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'academic_year' => $academicYear,
        ];
    }

    public function getTeacherSubjects(string $staffId, ?string $academicYear = null): array
    {
        $sql = "
            SELECT ss.id, ss.staff_id, ss.subject_id, ss.class_id, ss.academic_year,
                   s.subject_id AS subject_code, s.subject_name,
                   c.class_id AS class_code, c.class_name,
                   ss.assigned_by, ss.assigned_on
            FROM staff_subjects ss
            JOIN subjects s ON ss.subject_id = s.id
            LEFT JOIN classes c ON ss.class_id = c.id
            WHERE ss.staff_id = :staff_id
        ";

        if ($academicYear) {
            $sql .= " AND ss.academic_year = :academic_year";
        }

        $sql .= " ORDER BY s.subject_name, c.class_name";

        $stmt = $this->db->prepare($sql);
        $params = [':staff_id' => $staffId];
        if ($academicYear) {
            $params[':academic_year'] = $academicYear;
        }
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubjectTeachers(int $subjectId, ?int $classId = null, ?string $academicYear = null): array
    {
        $sql = "
            SELECT ss.id, ss.staff_id, ss.subject_id, ss.class_id, ss.academic_year,
                   s.subject_id AS subject_code, s.subject_name,
                   c.class_id AS class_code, c.class_name,
                   ss.assigned_by, ss.assigned_on
            FROM staff_subjects ss
            JOIN subjects s ON ss.subject_id = s.id
            LEFT JOIN classes c ON ss.class_id = c.id
            WHERE ss.subject_id = :subject_id
        ";

        $params = [':subject_id' => $subjectId];

        if ($classId) {
            $sql .= " AND ss.class_id = :class_id";
            $params[':class_id'] = $classId;
        }

        if ($academicYear) {
            $sql .= " AND ss.academic_year = :academic_year";
            $params[':academic_year'] = $academicYear;
        }

        $sql .= " ORDER BY ss.assigned_on DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exists(string $staffId, int $subjectId, ?int $classId = null, ?string $academicYear = null): bool
    {
        $sql = "SELECT id FROM staff_subjects WHERE staff_id = :staff_id AND subject_id = :subject_id";
        $params = [':staff_id' => $staffId, ':subject_id' => $subjectId];

        if ($classId !== null) {
            $sql .= " AND class_id = :class_id";
            $params[':class_id'] = $classId;
        } else {
            $sql .= " AND class_id IS NULL";
        }

        if ($academicYear !== null) {
            $sql .= " AND academic_year = :academic_year";
            $params[':academic_year'] = $academicYear;
        } else {
            $sql .= " AND academic_year IS NULL";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function remove(int $id): bool
    {
        $sql = "DELETE FROM staff_subjects WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function getAll(): array
    {
        $sql = "
            SELECT ss.id, ss.staff_id, ss.subject_id, ss.class_id, ss.academic_year,
                   s.subject_id AS subject_code, s.subject_name,
                   c.class_id AS class_code, c.class_name,
                   ss.assigned_by, ss.assigned_on
            FROM staff_subjects ss
            JOIN subjects s ON ss.subject_id = s.id
            LEFT JOIN classes c ON ss.class_id = c.id
            ORDER BY ss.staff_id, s.subject_name, c.class_name
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
