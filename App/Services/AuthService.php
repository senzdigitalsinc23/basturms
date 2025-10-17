<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\DTOs\LoginRequestDTO;
use App\DTOs\RegisterRequestDTO;
use App\Repositories\UserRepository;
use App\Core\Session;
use App\Exceptions\AuthException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    private UserRepository $userRepository;
    private string $jwtSecret;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this';
    }

    public function register(RegisterRequestDTO $registerData): array
    {
        // Check if email already exists
        if ($this->userRepository->emailExists($registerData->email)) {
            throw AuthException::emailAlreadyExists($registerData->email);
        }

        // Hash password
        $hashedPassword = password_hash($registerData->password, PASSWORD_DEFAULT);

        // Create user data
        $userData = [
            'user_id' => uniqid('user_'),
            'username' => $registerData->name,
            'email' => $registerData->email,
            'password' => $hashedPassword,
            'role_id' => $registerData->roleId ?? 1,
            'status' => 'active'
        ];

        $user = $this->userRepository->create($userData);

        if (!$user) {
            throw AuthException::registrationFailed();
        }

        return [
            'success' => true,
            'message' => 'User registered successfully',
            'user' => $user->toArrayWithoutPassword()
        ];
    }

    public function login(LoginRequestDTO $loginData): array
    {
        $user = $this->userRepository->findByEmail($loginData->email);

        if (!$user) {
            throw AuthException::invalidCredentials();
        }

        if (!password_verify($loginData->password, $user->password)) {
            throw AuthException::invalidCredentials();
        }

        if ($user->status !== 'active') {
            throw AuthException::accountInactive();
        }

        // Generate JWT token
        $token = $this->generateJWT($user);

        // Set session
        Session::set('user', $user->toArrayWithoutPassword());
        Session::set('user_id', $user->id);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user->toArrayWithoutPassword(),
            'token' => $token,
        ];
    }

    public function getCurrentUser(): ?UserDTO
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }

        return $this->userRepository->findById($userId);
    }

    public function logout(): array
    {
        Session::destroy();
        
        return [
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => '/web/login'
        ];
    }

    public function refreshToken(UserDTO $user): string
    {
        return $this->generateJWT($user);
    }

    public function validateToken(string $token): ?UserDTO
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            if (!isset($decoded->sub)) {
                return null;
            }

            return $this->userRepository->findById($decoded->sub);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateJWT(UserDTO $user): string
    {
        $payload = [
            'iss' => $_ENV['APP_URL'] ?? 'your-app',
            'sub' => $user->id,
            'user_id' => $user->userId,
            'email' => $user->email,
            'role_id' => $user->roleId,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw AuthException::userNotFound();
        }

        if (!password_verify($currentPassword, $user->password)) {
            throw AuthException::invalidCurrentPassword();
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $success = $this->userRepository->updatePassword($userId, $hashedNewPassword);

        if (!$success) {
            throw AuthException::passwordUpdateFailed();
        }

        return [
            'success' => true,
            'message' => 'Password updated successfully'
        ];
    }
}
