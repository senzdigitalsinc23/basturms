<?php

namespace App\Services;

use App\Repositories\CalendarEventRepository;

/**
 * Service for calendar events.
 */
class CalendarEventService
{
    private CalendarEventRepository $repository;

    public function __construct(CalendarEventRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createEvent(string $title, int $categoryId, string $date): array
    {
        return $this->repository->create($title, $categoryId, $date);
    }

    public function updateEvent(int $id, string $title, int $categoryId, string $date): bool
    {
        return $this->repository->update($id, $title, $categoryId, $date);
    }

    public function deleteEvent(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getAllEvents(): array
    {
        return $this->repository->getAll();
    }

    public function getEventById(int $id): ?array
    {
        return $this->repository->getById($id);
    }
    
    public function getCategories(): array
    {
        return $this->repository->getCategories();
    }
}
