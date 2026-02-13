<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Repository for calendar events.
 */
class CalendarEventRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new event.
     *
     * @param string $title
     * @param int $categoryId
     * @param string $date
     * @return array
     */
    public function create(string $title, int $categoryId, string $date): array
    {
        $sql = "INSERT INTO calendar_events (event_title, event_category, event_date) VALUES (:title, :category, :date)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':category' => $categoryId,
            ':date' => $date
        ]);

        $id = (int)$this->db->lastInsertId();

        return $this->getById($id);
    }

    /**
     * Update an event.
     *
     * @param int $id
     * @param string $title
     * @param int $categoryId
     * @param string $date
     * @return bool
     */
    public function update(int $id, string $title, int $categoryId, string $date): bool
    {
        $sql = "UPDATE calendar_events SET event_title = :title, event_category = :category, event_date = :date WHERE event_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':category' => $categoryId,
            ':date' => $date
        ]);
    }

    /**
     * Delete an event.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM calendar_events WHERE event_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get all events with category details.
     *
     * @return array
     */
    public function getAll(): array
    {
        $sql = "
            SELECT e.*, c.event_type_name, c.event_type_name as category
            FROM calendar_events e
            JOIN calendar_event_categories c ON e.event_category = c.event_type_id
            ORDER BY e.event_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get event by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $sql = "
            SELECT e.*, c.event_type_name, c.event_type_name as category
            FROM calendar_events e
            JOIN calendar_event_categories c ON e.event_category = c.event_type_id
            WHERE e.event_id = :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get all categories.
     *
     * @return array
     */
    public function getCategories(): array
    {
        $sql = "SELECT * FROM calendar_event_categories";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
