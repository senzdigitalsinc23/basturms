<?php

namespace App\Repositories;

use App\Core\Database;
use App\Core\Session;
use PDO;

class ClassSubjectRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function assignSubjectToClass(int $classId, int $subjectId, string $addedBy): array
    {
        $sql = "
            INSERT INTO class_subjects (class_id, subject_id, academic_year, term, assigned_by, assigned_on)
            VALUES (:class_id, :subject_id, :academic_year, :term, :assigned_by, NOW())
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':subject_id' => $subjectId,
            ':academic_year' => Session::get('user')['academic_year'],
            ':term' => Session::get('user')['term'],
            ':assigned_by' => $addedBy,
        ]);

        return [
            'id' => $this->db->lastInsertId(),
            'class_id' => $classId,
            'subject_id' => $subjectId,
        ];
    }

    public function bulkAssignSubjectsToClass(int $classId, array $subjectIds, string $assignedBy): array
    {
        if (empty($subjectIds)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($subjectIds as $index => $subjectId) {
            $placeholders[] = '(?, ?, ?, NOW())';
            $params[] = $classId;
            $params[] = $subjectId;
            $params[] = $assignedBy;
        }

        $sql = "
            INSERT INTO class_subjects (class_id, subject_id, assigned_by, assigned_on)
            VALUES " . implode(', ', $placeholders) . "
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'rows_affected' => $stmt->rowCount(),
            'class_id' => $classId,
            'subject_ids' => $subjectIds,
        ];
    }

    public function bulkAssign(array $assignments, string $assignedBy): array
    {
        if (empty($assignments)) {
            return ['rows_affected' => 0];
        }

        $placeholders = [];
        $params = [];

        foreach ($assignments as $assignment) {
            $placeholders[] = '(?, ?, ?, ?, ?, NOW())';
            $params[] = $assignment['class_id'];
            $params[] = $assignment['subject_id'];
            $params[] = Session::get('user')['academic_year'];
            $params[] = Session::get('user')['term'];
            $params[] = $assignedBy;
            
        }

        $sql = "
            INSERT INTO class_subjects (class_id, subject_id, academic_year, term, assigned_by, assigned_on)
            VALUES " . implode(', ', $placeholders) . "
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'rows_affected' => $stmt->rowCount()
        ];
    }

    public function getSubjectsByClass(int $classId): array
    {
        if ($classId == 0) {
            $sql = "
                SELECT cs.id, cs.class_id, cs.subject_id, cs.academic_year, cs.term, s.subject_code AS code, s.subject_name, s.status
                FROM class_subjects cs
                JOIN subjects s ON cs.subject_id = s.id
                ORDER BY s.subject_name ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }else {
            $sql = "
                SELECT cs.id, cs.class_id, cs.subject_id, cs.academic_year, cs.term, s.subject_code AS code, s.subject_name, s.status
                FROM class_subjects cs
                JOIN subjects s ON cs.subject_id = s.id
                WHERE cs.class_id = :class_id
                ORDER BY s.subject_name ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':class_id' => $classId]);
        }

        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClassesBySubject(int $subjectId): array
    {
        $sql = "
            SELECT cs.id, cs.class_id, cs.subject_id, c.class_id AS code, c.class_name, cs.added_by, cs.added_on
            FROM class_subjects cs
            JOIN classes c ON cs.class_id = c.id
            WHERE cs.subject_id = :subject_id
            ORDER BY c.class_name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':subject_id' => $subjectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exists(string $classId, string $subjectId): bool
    {
        $sql = "SELECT id FROM class_subjects WHERE class_id = :class_id AND subject_id = :subject_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':class_id' => $classId, ':subject_id' => $subjectId]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function removeSubjectFromClass(int $classId, int $subjectId): bool
    {
        $sql = "DELETE FROM class_subjects WHERE class_id = :class_id AND subject_id = :subject_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':class_id' => $classId, ':subject_id' => $subjectId]);
    }

    public function bulkRemoveSubjectsFromClass(int $classId, array $subjectIds): array
    {
        if (empty($subjectIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($subjectIds), '?'));
        $params = array_merge([$classId], $subjectIds);

        $sql = "DELETE FROM class_subjects WHERE class_id = ? AND subject_id IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'rows_affected' => $stmt->rowCount(),
            'class_id' => $classId,
            'subject_ids' => $subjectIds,
        ];
    }

    public function getAll(): array
    {
        $sql = "
            SELECT cs.id, cs.class_id, cs.subject_id, c.class_name, s.subject_name, cs.added_by, cs.added_on
            FROM class_subjects cs
            JOIN classes c ON cs.class_id = c.id
            JOIN subjects s ON cs.subject_id = s.id
            ORDER BY c.class_name ASC, s.subject_name ASC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
