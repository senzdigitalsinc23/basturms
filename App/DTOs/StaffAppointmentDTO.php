<?php

namespace App\DTOs;

class StaffAppointmentDTO
{
    public function __construct(
        public string $staff_id,
        public string $appointment_date,
        public string $appointment_status,
        public ?string $class_teacher_for,
        public string $created_by
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            staff_id: $data['staff_id'] ?? '',
            appointment_date: $data['appointment_date'] ?? date('Y-m-d'),
            appointment_status: $data['appointment_status'] ?? 'appointed',
            class_teacher_for: $data['class_teacher_for'] ?? null,
            created_by: $data['created_by'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'staff_id' => $this->staff_id,
            'appointment_date' => $this->appointment_date,
            'appointment_status' => $this->appointment_status,
            'class_teacher_for' => $this->class_teacher_for,
            'created_by' => $this->created_by,
        ];
    }
}
