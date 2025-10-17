<?php

namespace App\Services;

use App\Core\Validator;
use App\Core\Database;

class AuthValidationService
{
    public function validateLoginData(array $data): array
    {
        $rules = [
            'email' => 'bail|required|email|max:255',
            'password' => 'bail|required|string|min:6|max:255'
        ];

        $messages = [
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters'
        ];

        $db = Database::getInstance()->getConnection();
        $validator = new Validator($data, $rules, $db, $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()
            ];
        }

        return [
            'success' => true,
            'data' => [
                'email' => $data['email'],
                'password' => $data['password']
            ]
        ];
    }

    public function validateRegisterData(array $data): array
    {
        $rules = [
            'name' => 'bail|required|string|min:2|max:100',
            'email' => 'bail|required|email|max:255|unique:users,email',
            'password' => 'bail|required|string|min:6|max:255',
            'password_confirmation' => 'bail|required|string|same:password',
            'role_id' => 'bail|nullable|integer|exists:roles,id'
        ];

        $messages = [
            'name.required' => 'Name is required',
            'name.min' => 'Name must be at least 2 characters',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters',
            'password_confirmation.required' => 'Password confirmation is required',
            'password_confirmation.same' => 'Password confirmation does not match',
            'role_id.exists' => 'Selected role does not exist'
        ];

        $db = Database::getInstance()->getConnection();
        $validator = new Validator($data, $rules, $db, $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()
            ];
        }

        return [
            'success' => true,
            'data' => [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $data['role_id'] ?? null
            ]
        ];
    }

    public function validatePasswordChangeData(array $data): array
    {
        $rules = [
            'current_password' => 'bail|required|string',
            'new_password' => 'bail|required|string|min:6|max:255',
            'new_password_confirmation' => 'bail|required|string|same:new_password'
        ];

        $messages = [
            'current_password.required' => 'Current password is required',
            'new_password.required' => 'New password is required',
            'new_password.min' => 'New password must be at least 6 characters',
            'new_password_confirmation.required' => 'Password confirmation is required',
            'new_password_confirmation.same' => 'Password confirmation does not match'
        ];

        $db = Database::getInstance()->getConnection();
        $validator = new Validator($data, $rules, $db, $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()
            ];
        }

        return [
            'success' => true,
            'data' => [
                'current_password' => $data['current_password'],
                'new_password' => $data['new_password']
            ]
        ];
    }
}
