<?php

namespace App\Services;

/**
 * Service for data validation.
 */
class ValidationService
{
    /**
     * Convert value to integer or return default.
     *
     * @param mixed $value The value to convert.
     * @param int $default The default value if conversion fails.
     * @return int The converted integer.
     */
    private function toIntOrDefault($value, int $default): int
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_numeric($value)) {
            return max(0, (int)$value);
        }
        return $default;
    }

    /**
     * Convert value to string or return null if empty.
     *
     * @param mixed $value The value to convert.
     * @return string|null The converted string or null.
     */
    private function toStringOrNull($value): ?string
    {
        if ($value === null) return null;
        $trimmed = is_string($value) ? trim($value) : (string)$value;
        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Generic validation based on rules.
     *
     * @param array $data The data to validate.
     * @param array $rules The rules to apply (e.g., ['field' => 'required|email']).
     * @return array The validation result ['success' => bool, 'errors' => array, 'data' => array].
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $rulesArray = explode('|', $fieldRules);
            
            foreach ($rulesArray as $rule) {
                // Parse rule with parameters (e.g., min:2, max:100)
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;
                
                // Required validation
                if ($ruleName === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                    break; // Skip other validations if required fails
                }
                
                // Skip other validations if value is null/empty and field is not required
                if (($value === null || $value === '') && !in_array('required', $rulesArray)) {
                    continue;
                }
                
                // Nullable validation - skip if value is empty
                if ($ruleName === 'nullable' && ($value === null || $value === '')) {
                    break; // Skip all other validations
                }
                
                // Email validation
                if ($ruleName === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Valid email is required';
                }
                
                // String validation
                if ($ruleName === 'string' && $value && !is_string($value)) {
                    $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a string';
                }
                
                // Min length validation
                if ($ruleName === 'min' && $ruleParam && $value) {
                    if (is_string($value) && strlen($value) < (int)$ruleParam) {
                        $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be at least ' . $ruleParam . ' characters';
                    }
                }
                
                // Max length validation
                if ($ruleName === 'max' && $ruleParam && $value) {
                    if (is_string($value) && strlen($value) > (int)$ruleParam) {
                        $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must not exceed ' . $ruleParam . ' characters';
                    }
                }
                
                // Date validation
                if ($ruleName === 'date' && $value) {
                    $timestamp = strtotime($value);
                    if ($timestamp === false) {
                        $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid date';
                    }
                }
            }
            
            $validated[$field] = $value;
        }

        if (!empty($errors)) {
            throw new \App\Exceptions\ValidationException($errors, 'Validation failed');
        }

        return $validated;
    }

    /**
     * Validate student search parameters.
     *
     * @param array $data The search data.
     * @return array The validation result.
     */
    public function validateStudentSearch(array $data): array
    {
        // Defaults for pagination and optional filters
        $page  = $this->toIntOrDefault($data['page'] ?? null, 1);
        $limit = $this->toIntOrDefault($data['limit'] ?? null, 20);
        if ($limit <= 0) $limit = 20;
        if ($page <= 0) $page = 1;

        $search = $this->toStringOrNull($data['search'] ?? null);
        $status = $this->toStringOrNull($data['status'] ?? null);

        // Basic constraints
        $errors = [];
        if ($limit > 200) {
            $errors['limit'][] = 'Limit cannot exceed 200.';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => [
                'page' => $page,
                'limit' => $limit,
                'search' => $search,
                'status' => $status,
            ],
        ];
    }

    /**
     * Validate student creation data.
     *
     * @param array $data The student data.
     * @return array The validation result.
     */
    public function validateStudentData(array $data): array
    {
        // Expecting nested structure from frontend
        $studentInfo = $data['student_info'] ?? [];
        $contact = $data['contact_address'] ?? [];
        $admission = $data['admission_info'] ?? [];
        $guardians = $data['guardians'] ?? [];
        $emergency = $data['emergency_contact'] ?? [];

        $errors = [];

        // Required fields
        if (empty($studentInfo['first_name'])) $errors['student_info.first_name'][] = 'This field is required.';
        if (empty($studentInfo['last_name'])) $errors['student_info.last_name'][] = 'This field is required.';
        if (empty($studentInfo['gender'])) $errors['student_info.gender'][] = 'This field is required.';
        if (empty($studentInfo['dob'])) $errors['student_info.dob'][] = 'This field is required.';
        if (!isset($studentInfo['created_by']) || $studentInfo['created_by'] === '') $errors['student_info.created_by'][] = 'This field is required.';

        if (empty($contact['country_id'])) $errors['contact_address.country_id'][] = 'This field is required.';
        if (empty($contact['hometown'])) $errors['contact_address.hometown'][] = 'This field is required.';
        if (empty($contact['residence'])) $errors['contact_address.residence'][] = 'This field is required.';
        if (empty($contact['house_no'])) $errors['contact_address.house_no'][] = 'This field is required.';
        if (empty($contact['gps_no'])) $errors['contact_address.gps_no'][] = 'This field is required.';

        // Email validation if provided
        if (!empty($contact['email']) && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_address.email'][] = 'Invalid email format.';
        }

        // Admission minimal checks
        if (empty($admission['admission_status'])) $errors['admission_info.admission_status'][] = 'This field is required.';
        if (empty($admission['class_assigned'])) $errors['admission_info.class_assigned'][] = 'This field is required.';

        // Return the validated data preserving the nested structure
        $validatedNested = [
            'student_info' => [
                'first_name' => $studentInfo['first_name'] ?? null,
                'last_name' => $studentInfo['last_name'] ?? null,
                'other_name' => $studentInfo['other_name'] ?? null,
                'gender' => $studentInfo['gender'] ?? null,
                'dob' => $studentInfo['dob'] ?? null,
                'nhis_no' => $studentInfo['nhis_no'] ?? '',
                'created_by' => (string)($studentInfo['created_by'] ?? ''),
            ],
            'contact_address' => [
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'country_id' => $contact['country_id'] ?? null,
                'city' => $contact['city'] ?? null,
                'hometown' => $contact['hometown'] ?? null,
                'residence' => $contact['residence'] ?? null,
                'house_no' => $contact['house_no'] ?? null,
                'gps_no' => $contact['gps_no'] ?? null,
            ],
            'admission_info' => [
                'admission_no' => $admission['admission_no'] ?? '',
                'admission_status' => $admission['admission_status'] ?? 'Active',
                'class_assigned' => $admission['class_assigned'] ?? null,
                'class_name' => $admission['class_name'] ?? null,
                'enrollment_date' => $admission['enrollment_date'] ?? '0000-00-00',
            ],
            'guardians' => is_array($guardians) ? $guardians : [],
            'emergency_contact' => is_array($emergency) ? $emergency : [],
        ];

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $validatedNested,
        ];
    }

    /**
     * Validate student status update data.
     *
     * @param array $data The update data.
     * @return array The validation result.
     */
    public function validateStudentStatusUpdate(array $data): array
    {
        $errors = [];
        $status = $this->toStringOrNull($data['status'] ?? null);
        $reason = $this->toStringOrNull($data['reason'] ?? null);
        $archivedBy = $this->toStringOrNull($data['archived_by'] ?? ($data['user_id'] ?? null));

        if ($status === null) {
            $errors['status'][] = 'Status is required.';
        }

        $studentNos = [];

        // Support array of student numbers for bulk actions
        if (!empty($data['student_nos']) && is_array($data['student_nos'])) {
            foreach ($data['student_nos'] as $studentNo) {
                $trimmed = $this->toStringOrNull($studentNo);
                if ($trimmed !== null) {
                    $studentNos[] = $trimmed;
                }
            }
        }

        // Fallback to single student_no or legacy id fields
        $singleId = $data['student_no'] ?? $data['id'] ?? null;
        if (empty($studentNos) && $singleId !== null && $singleId !== '') {
            $studentNos[] = trim((string) $singleId);
        }

        $studentNos = array_values(array_unique(array_filter($studentNos)));

        if (empty($studentNos)) {
            $errors['student_no'][] = 'At least one student number is required.';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => [
                'status' => $status,
                'student_nos' => $studentNos,
                'reason' => $reason,
                'archived_by' => $archivedBy,
            ],
        ];
    }

    /**
     * Validate student update data.
     *
     * @param array $data The update data.
     * @return array The validation result.
     */
    public function validateStudentUpdate(array $data): array
    {
        // Expecting nested structure from frontend (same as create)
        $studentInfo = $data['student_info'] ?? [];
        $contact = $data['contact_address'] ?? [];
        $admission = $data['admission_info'] ?? [];
        $guardians = $data['guardians'] ?? [];
        $emergency = $data['emergency_contact'] ?? [];

        $errors = [];

        // Student number is required for update
        if (empty($data['student_no']) && empty($studentInfo['student_no'])) {
            $errors['student_no'][] = 'Student number is required for update.';
        }

        $studentNo = $data['student_no'] ?? $studentInfo['student_no'] ?? '';

        // Required fields
        if (empty($studentInfo['first_name'])) $errors['student_info.first_name'][] = 'This field is required.';
        if (empty($studentInfo['last_name'])) $errors['student_info.last_name'][] = 'This field is required.';
        if (empty($studentInfo['gender'])) $errors['student_info.gender'][] = 'This field is required.';
        if (empty($studentInfo['dob'])) $errors['student_info.dob'][] = 'This field is required.';

        if (empty($contact['country_id'])) $errors['contact_address.country_id'][] = 'This field is required.';
        if (empty($contact['hometown'])) $errors['contact_address.hometown'][] = 'This field is required.';
        if (empty($contact['residence'])) $errors['contact_address.residence'][] = 'This field is required.';
        if (empty($contact['house_no'])) $errors['contact_address.house_no'][] = 'This field is required.';
        if (empty($contact['gps_no'])) $errors['contact_address.gps_no'][] = 'This field is required.';

        // Email validation if provided
        if (!empty($contact['email']) && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_address.email'][] = 'Invalid email format.';
        }

        // Admission minimal checks
        if (empty($admission['admission_status'])) $errors['admission_info.admission_status'][] = 'This field is required.';
        if (empty($admission['class_assigned'])) $errors['admission_info.class_assigned'][] = 'This field is required.';

        // Return the validated data preserving the nested structure
        $validatedNested = [
            'student_no' => $studentNo,
            'student_info' => [
                'student_no' => $studentNo,
                'first_name' => $studentInfo['first_name'] ?? null,
                'last_name' => $studentInfo['last_name'] ?? null,
                'other_name' => $studentInfo['other_name'] ?? null,
                'gender' => $studentInfo['gender'] ?? null,
                'dob' => $studentInfo['dob'] ?? null,
                'nhis_no' => $studentInfo['nhis_no'] ?? '',
            ],
            'contact_address' => [
                'email' => $contact['email'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'country_id' => $contact['country_id'] ?? null,
                'city' => $contact['city'] ?? null,
                'hometown' => $contact['hometown'] ?? null,
                'residence' => $contact['residence'] ?? null,
                'house_no' => $contact['house_no'] ?? null,
                'gps_no' => $contact['gps_no'] ?? null,
            ],
            'admission_info' => [
                'admission_no' => $admission['admission_no'] ?? '',
                'admission_status' => $admission['admission_status'] ?? 'Active',
                'class_assigned' => $admission['class_assigned'] ?? null,
                'class_name' => $admission['class_name'] ?? null,
                'enrollment_date' => $admission['enrollment_date'] ?? '0000-00-00',
            ],
            'guardians' => is_array($guardians) ? $guardians : [],
            'emergency_contact' => is_array($emergency) ? $emergency : [],
        ];

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $validatedNested,
        ];
    }
}
