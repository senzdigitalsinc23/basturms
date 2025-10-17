<?php

namespace App\DTOs;

class EmergencyContactDTO
{
    public function __construct(
        public readonly string $emergencyId,
        public readonly string $emergencyName,
        public readonly string $emergencyPhone,
        public readonly ?string $emergencyEmail,
        public readonly string $emergencyRelationship
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            emergencyId: $data['emergency_id'],
            emergencyName: $data['emergency_name'],
            emergencyPhone: $data['emergency_phone'],
            emergencyEmail: $data['emergency_email'] ?? null,
            emergencyRelationship: $data['emergency_relationship']
        );
    }

    public function toArray(): array
    {
        return [
            'emergency_id' => $this->emergencyId,
            'emergency_name' => $this->emergencyName,
            'emergency_phone' => $this->emergencyPhone,
            'emergency_email' => $this->emergencyEmail,
            'emergency_relationship' => $this->emergencyRelationship
        ];
    }
}
