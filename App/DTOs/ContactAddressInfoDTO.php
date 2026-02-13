<?php

namespace App\DTOs;

class ContactAddressInfoDTO
{
    public function __construct(
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $countryId,
        public readonly ?string $city,
        public readonly string $hometown,
        public readonly string $residence,
        public readonly string $houseNo,
        public readonly string $gpsNo
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            countryId: $data['country_id'] ?? '',
            city: $data['city'] ?? null,
            hometown: $data['hometown'] ?? '',
            residence: $data['residence'] ?? '',
            houseNo: $data['house_no'] ?? '',
            gpsNo: $data['gps_no'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,
            'countryId' => $this->countryId,
            'city' => $this->city,
            'hometown' => $this->hometown,
            'residence' => $this->residence,
            'houseNo' => $this->houseNo,
            'gpsNo' => $this->gpsNo,
        ];
    }
}


