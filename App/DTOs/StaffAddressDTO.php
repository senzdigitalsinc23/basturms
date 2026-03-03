<?php

namespace App\DTOs;

class StaffAddressDTO
{
    public function __construct(
        public string $staff_id,
        public string $country,
        public string $city,
        public string $hometown,
        public string $residence,
        public string $house_no,
        public string $gps_no
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            staff_id: $data['staff_id'] ?? '',
            country: $data['country'] ?? '',
            city: $data['city'] ?? '',
            hometown: $data['hometown'] ?? '',
            residence: $data['residence'] ?? '',
            house_no: $data['house_no'] ?? '',
            gps_no: $data['gps_no'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'staff_id' => $this->staff_id,
            'country' => $this->country,
            'city' => $this->city,
            'hometown' => $this->hometown,
            'residence' => $this->residence,
            'house_no' => $this->house_no,
            'gps_no' => $this->gps_no,
        ];
    }
}
