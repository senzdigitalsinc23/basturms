<?php

namespace App\DTOs;

class AdmissionDTO
{
    public function __construct(
        public readonly string $studentNo,
        public readonly string $admissionNo,
        public readonly string $admissionStatus,
        public readonly string $classAssigned,
        public readonly string $enrollmentDate
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            studentNo: $data['student_no'],
            admissionNo: $data['admission_no'],
            admissionStatus: $data['admission_status'],
            classAssigned: $data['class_assigned'],
            enrollmentDate: $data['enrollment_date']
        );
    }

    public function toArray(): array
    {
        return [
            'student_no' => $this->studentNo,
            'admission_no' => $this->admissionNo,
            'admission_status' => $this->admissionStatus,
            'class_assigned' => $this->classAssigned,
            'enrollment_date' => $this->enrollmentDate
        ];
    }
}
