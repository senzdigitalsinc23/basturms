<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Repositories\UserRepository;
use App\Exceptions\AdminException;

/**
 * Service for administrative tasks related to user management.
 */
class AdminService
{
    private UserRepository $userRepository;

    /**
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Get all users in the system.
     *
     * @return array The list of all users without passwords.
     */
    public function getAllUsers(): array
    {
        $users = $this->userRepository->getAllUsers();
        
        return [
            'success' => true,
            'message' => 'Users retrieved successfully',
            'users' => array_map(fn($user) => $user->toArrayWithoutPassword(), $users)
        ];
    }

    /**
     * Get a user by their ID.
     *
     * @param int $id The user ID.
     * @return array The user record without password.
     * @throws AdminException If user not found.
     */
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

    /**
     * Update an existing user's information.
     *
     * @param int $id The user ID.
     * @param array $data The updated user data.
     * @return array The result of the update.
     * @throws AdminException If user not found, email exists, or update fails.
     */
    public function updateUser(int $id, array $data): array
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            throw AdminException::userNotFound($id);
        }

        // Check if email is being changed and if it already exists
        if (isset($data['email']) && (string)$data['email'] !== $user->email) {
            if ($this->userRepository->emailExists((string)$data['email'], $id)) {
                throw AdminException::emailAlreadyExists((string)$data['email']);
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

    /**
     * Delete a user by their ID.
     *
     * @param int $id The user ID.
     * @return array The result of the deletion.
     * @throws AdminException If user not found or deletion fails.
     */
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

    /**
     * Get statistics about users in the system.
     *
     * @return array The user statistics.
     */
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

            $roleId = (string)($user->roleId ?? 'unknown');
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
