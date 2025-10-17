<?php

namespace App\DTOs;

class GuardianDTO
{
    public function __construct(
        public readonly string $guardianId,
        public readonly string $guardianName,
        public readonly string $guardianPhone,
        public readonly ?string $guardianEmail,
        public readonly string $guardianRelationship
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            guardianId: $data['guardian_id'],
            guardianName: $data['guardian_name'],
            guardianPhone: $data['guardian_phone'],
            guardianEmail: $data['guardian_email'] ?? null,
            guardianRelationship: $data['guardian_relationship']
        );
    }

    public function toArray(): array
    {
        return [
            'guardian_id' => $this->guardianId,
            'guardian_name' => $this->guardianName,
            'guardian_phone' => $this->guardianPhone,
            'guardian_email' => $this->guardianEmail,
            'guardian_relationship' => $this->guardianRelationship
        ];
    }
}
