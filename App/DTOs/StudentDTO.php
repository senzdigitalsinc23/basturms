<?php

namespace App\DTOs;

class StudentDTO
{
    public function __construct(
        public readonly string $studentNo,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $otherName,
        public readonly string $dob,
        public readonly string $gender,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly string $countryId,
        public readonly ?string $city,
        public readonly string $hometown,
        public readonly string $residence,
        public readonly string $houseNo,
        public readonly string $gpsNo,
        public readonly string $admissionNo,
        public readonly string $status,
        public readonly string $classId,
        public readonly string $enrollmentDate,
        public readonly string $nhisNo,
        public readonly int $createdBy
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            studentNo: $data['student_no'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            otherName: $data['other_name'] ?? null,
            dob: $data['dob'],
            gender: $data['gender'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            countryId: $data['country_id'],
            city: $data['city'] ?? null,
            hometown: $data['hometown'],
            residence: $data['residence'],
            houseNo: $data['house_no'],
            gpsNo: $data['gps_no'],
            admissionNo: $data['admission_no'],
            status: $data['status'],
            classId: $data['class_id'],
            enrollmentDate: $data['enrollment_date'],
            nhisNo: $data['nhis_no'],
            createdBy: $data['created_by']
        );
    }

    public function toArray(): array
    {
        return [
            'student_no' => $this->studentNo,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'other_name' => $this->otherName,
            'dob' => $this->dob,
            'gender' => $this->gender,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_id' => $this->countryId,
            'city' => $this->city,
            'hometown' => $this->hometown,
            'residence' => $this->residence,
            'house_no' => $this->houseNo,
            'gps_no' => $this->gpsNo,
            'admission_no' => $this->admissionNo,
            'status' => $this->status,
            'class_id' => $this->classId,
            'enrollment_date' => $this->enrollmentDate,
            'nhis_no' => $this->nhisNo,
            'created_by' => $this->createdBy
        ];
    }
}
