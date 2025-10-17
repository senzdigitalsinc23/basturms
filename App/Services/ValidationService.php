<?php

namespace App\Services;

class ValidationService
{
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

    private function toStringOrNull($value): ?string
    {
        if ($value === null) return null;
        $trimmed = is_string($value) ? trim($value) : (string)$value;
        return $trimmed === '' ? null : $trimmed;
    }

    public function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $fieldRules) as $rule) {
                if ($rule === 'required' && (is_null($value) || $value === '')) {
                    $errors[$field][] = 'This field is required.';
                }
                if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Invalid email format.';
                }
                // Add more rules as needed (min, max, string, int, etc.)
            }
            $validated[$field] = $value;
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $validated,
        ];
    }

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

    public function validateStudentData(array $data): array
    {
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            // Add more rules for other required student fields as needed
        ];
        return $this->validate($data, $rules);
    }

    public function validateStudentStatusUpdate(array $data): array
    {
        $rules = [
            'id' => 'required',
            'status' => 'required',
        ];
        return $this->validate($data, $rules);
    }
}
