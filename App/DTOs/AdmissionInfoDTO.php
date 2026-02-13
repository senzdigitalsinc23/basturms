<?php

namespace App\DTOs;

class AdmissionInfoDTO
{
    public function __construct(
        public readonly string $admissionNo,
        public readonly string $status,
        public readonly string $classId,
        public readonly string $enrollmentDate,
        public readonly string $nhisNo
    ) {}

    public static function fromArray(array $admission, array $studentInfo = []): self
    {
        return new self(
            admissionNo: $admission['admission_no'] ?? '',
            status: $admission['admission_status'] ?? 'Active',
            classId: $admission['class_assigned'] ?? '',
            enrollmentDate: $admission['enrollment_date'] ?? '0000-00-00',
            nhisNo: $studentInfo['nhis_no'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'admissionNo' => $this->admissionNo,
            'status' => $this->status,
            'classId' => $this->classId,
            'enrollmentDate' => $this->enrollmentDate,
            'nhisNo' => $this->nhisNo,
        ];
    }
}


