<?php

namespace App\DTOs;

class StaffAcademicHistoryDTO
{
    public function __construct(
        public string $staff_id,
        public string $school_name,
        public string $program_offered,
        public string $qualification,
        public string $year_completed
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            staff_id: $data['staff_id'] ?? '',
            school_name: $data['school_name'] ?? '',
            program_offered: $data['program_offered'] ?? '',
            qualification: $data['qualification'] ?? '',
            year_completed: $data['year_completed'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'staff_id' => $this->staff_id,
            'school_name' => $this->school_name,
            'program_offered' => $this->program_offered,
            'qualification' => $this->qualification,
            'year_completed' => $this->year_completed,
        ];
    }
}
