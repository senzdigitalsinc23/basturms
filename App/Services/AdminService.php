<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Repositories\UserRepository;
use App\Exceptions\AdminException;

class AdminService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAllUsers(): array
    {
        $users = $this->userRepository->getAllUsers();
        
        return [
            'success' => true,
            'message' => 'Users retrieved successfully',
            'users' => array_map(fn($user) => $user->toArrayWithoutPassword(), $users)
        ];
    }

    public function getUserById(int $id): array
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            throw AdminException::userNotFound($id);
        }

        return [
            'success' => true,
            'message' => 'User retrieved successfully',
            'user' => $user->toArrayWithoutPassword()
        ];
    }

    public function updateUser(int $id, array $data): array
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            throw AdminException::userNotFound($id);
        }

        // Check if email is being changed and if it already exists
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if ($this->userRepository->emailExists($data['email'], $id)) {
                throw AdminException::emailAlreadyExists($data['email']);
            }
        }

        $success = $this->userRepository->updateUser($id, $data);

        if (!$success) {
            throw AdminException::userUpdateFailed();
        }

        return [
            'success' => true,
            'message' => 'User updated successfully'
        ];
    }

    public function deleteUser(int $id): array
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            throw AdminException::userNotFound($id);
        }

        $success = $this->userRepository->deleteUser($id);

        if (!$success) {
            throw AdminException::userDeleteFailed();
        }

        return [
            'success' => true,
            'message' => 'User deleted successfully'
        ];
    }

    public function getUsersStats(): array
    {
        $users = $this->userRepository->getAllUsers();
        
        $stats = [
            'total' => count($users),
            'active' => 0,
            'inactive' => 0,
            'by_role' => []
        ];

        foreach ($users as $user) {
            if ($user->status === 'active') {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }

            $roleId = $user->roleId ?? 'unknown';
            if (!isset($stats['by_role'][$roleId])) {
                $stats['by_role'][$roleId] = 0;
            }
            $stats['by_role'][$roleId]++;
        }

        return [
            'success' => true,
            'message' => 'User statistics retrieved successfully',
            'stats' => $stats
        ];
    }
}
