<?php

namespace App\DTOs;

/**
 * Data Transfer Object for user profile updates
 * 
 * Handles validation and sanitization of profile update data
 */
class ProfileUpdateDTO
{
    public ?string $username = null;
    public ?string $full_name = null;
    public ?string $email = null;
    public ?string $phone = null;

    /**
     * Create DTO from request data
     *
     * @param array $data Request data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        
        $dto->username = isset($data['username']) ? trim($data['username']) : null;
        $dto->full_name = isset($data['full_name']) ? trim($data['full_name']) : null;
        $dto->email = isset($data['email']) ? trim(strtolower($data['email'])) : null;
        $dto->phone = isset($data['phone']) ? trim($data['phone']) : null;

        return $dto;
    }

    /**
     * Validate the profile update data
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validate(): array
    {
        $errors = [];

        // At least one field must be provided
        if ($this->username === null && $this->full_name === null && $this->email === null && $this->phone === null) {
            $errors['general'] = 'At least one field must be provided';
            return $errors;
        }

        // Username validation
        if ($this->username !== null) {
            if (empty($this->username)) {
                $errors['username'] = 'Username cannot be empty';
            } elseif (strlen($this->username) < 3) {
                $errors['username'] = 'Username must be at least 3 characters long';
            } elseif (strlen($this->username) > 20) {
                $errors['username'] = 'Username cannot exceed 20 characters';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $this->username)) {
                $errors['username'] = 'Username can only contain letters, numbers, underscores, and hyphens';
            }
        }

        // Full name validation
        if ($this->full_name !== null) {
            if (empty($this->full_name)) {
                $errors['full_name'] = 'Full name cannot be empty';
            } elseif (strlen($this->full_name) < 2) {
                $errors['full_name'] = 'Full name must be at least 2 characters long';
            } elseif (strlen($this->full_name) > 100) {
                $errors['full_name'] = 'Full name cannot exceed 100 characters';
            }
        }

        // Email validation
        if ($this->email !== null) {
            if (empty($this->email)) {
                $errors['email'] = 'Email cannot be empty';
            } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please provide a valid email address';
            } elseif (strlen($this->email) > 100) {
                $errors['email'] = 'Email cannot exceed 100 characters';
            }
        }

        // Phone validation
        if ($this->phone !== null) {
            if (empty($this->phone)) {
                $errors['phone'] = 'Phone number cannot be empty';
            } elseif (!preg_match('/^[0-9+\-\s()]+$/', $this->phone)) {
                $errors['phone'] = 'Phone number can only contain numbers, +, -, spaces, and parentheses';
            } elseif (strlen($this->phone) < 10) {
                $errors['phone'] = 'Phone number must be at least 10 characters long';
            } elseif (strlen($this->phone) > 20) {
                $errors['phone'] = 'Phone number cannot exceed 20 characters';
            }
        }

        return $errors;
    }

    /**
     * Check if any profile fields are being updated
     *
     * @return bool
     */
    public function hasUpdates(): bool
    {
        return $this->username !== null || $this->full_name !== null || $this->email !== null || $this->phone !== null;
    }

    /**
     * Get profile update data
     *
     * @return array
     */
    public function getProfileData(): array
    {
        $data = [];

        if ($this->username !== null) {
            $data['username'] = $this->username;
        }

        if ($this->full_name !== null) {
            $data['full_name'] = $this->full_name;
        }

        if ($this->email !== null) {
            $data['email'] = $this->email;
        }

        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }

        return $data;
    }

    /**
     * Convert to array for logging (excludes sensitive data)
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'has_updates' => $this->hasUpdates(),
        ];
    }
}