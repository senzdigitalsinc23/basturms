<?php

namespace App\DTOs;

class StaffDTO
{
    public function __construct(
        public string $staff_id,
        public string $first_name,
        public string $last_name,
        public ?string $other_name,
        public string $email,
        public string $phone,
        public string $id_type,
        public string $id_no,
        public ?string $snnit_no,
        public string $date_of_joining,
        public string $status,
        public string $added_by
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            staff_id: $data['staff_id'] ?? '',
            first_name: $data['first_name'] ?? '',
            last_name: $data['last_name'] ?? '',
            other_name: $data['other_name'] ?? null,
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? '',
            id_type: $data['id_type'] ?? '',
            id_no: $data['id_no'] ?? '',
            snnit_no: $data['snnit_no'] ?? null,
            date_of_joining: $data['date_of_joining'] ?? date('Y-m-d'),
            status: $data['status'] ?? 'active',
            added_by: $data['added_by'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'staff_id' => $this->staff_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'other_name' => $this->other_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'id_type' => $this->id_type,
            'id_no' => $this->id_no,
            'snnit_no' => $this->snnit_no,
            'date_of_joining' => $this->date_of_joining,
            'status' => $this->status,
            'added_by' => $this->added_by,
        ];
    }
}
