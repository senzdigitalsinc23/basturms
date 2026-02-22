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

/**
 * Service for handling user authentication and registration.
 */
class AuthService
{
    private UserRepository $userRepository;
    private AcademicSetupService $academicSetupService;
    private string $jwtSecret;

    /**
     * @param UserRepository $userRepository
     * @param AcademicSetupService $academicSetupService
     * @throws \RuntimeException If JWT_SECRET is not configured
     */
    public function __construct(UserRepository $userRepository, AcademicSetupService $academicSetupService)
    {
        $this->userRepository = $userRepository;
        $this->academicSetupService = $academicSetupService;
        
        // SECURITY: Never use default JWT secrets - enforce configuration
        if (empty($_ENV['JWT_SECRET'])) {
            throw new \RuntimeException(
                'JWT_SECRET environment variable is not configured. ' .
                'Please set a strong secret key in your .env file.'
            );
        }
        
        $this->jwtSecret = $_ENV['JWT_SECRET'];
    }

    /**
     * Register a new user.
     *
     * @param RegisterRequestDTO $registerData The registration data.
     * @return array The result of the registration.
     * @throws AuthException If registration fails or email exists.
     */
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

    /**
     * Login a user.
     *
     * @param LoginRequestDTO $loginData The login data.
     * @return array The result of the login including token and academic info.
     * @throws AuthException If credentials are invalid or account is inactive.
     */
    public function login(LoginRequestDTO $loginData): array
    {
        $user = $this->userRepository->findByEmail($loginData->email);

        if (!$user) {
            throw AuthException::invalidCredentials();
        }

        // Check if account is locked
        $lockoutStatus = $this->checkAccountLockout($user->email);
        if ($lockoutStatus['locked']) {
            throw AuthException::accountLocked($lockoutStatus['minutes_remaining']);
        }

        

        // Verify password
        if (empty($user->password) || !password_verify($loginData->password, $user->password)) {
            // Record failed attempt and potentially lock account
            $this->recordFailedAttempt($user->email);
            throw AuthException::invalidCredentials();
        }

        if ($user->status !== 'active') {
            throw AuthException::accountInactive();
        }

        // Reset failed attempts on successful login
        $this->resetFailedAttempts($user->email);

        // Generate JWT token
        $token = $this->generateJWT($user);

        // Fetch active academic year and term
        $academicYear = null;
        $term = null;
        $academicId = null;
        try {
            $activeAcademicData = $this->academicSetupService->getActiveAcademicYear();
            if ($activeAcademicData['success'] && isset($activeAcademicData['data'])) {
                $academicYear = $activeAcademicData['data']['academic_year'] ?? null;
                $term = $activeAcademicData['data']['term'] ?? null;
                $academicId = $activeAcademicData['data']['academic_id'] ?? null;
            }
        } catch (\Exception $e) {
            // If no active academic year exists, continue with null values
            // This ensures login doesn't fail due to academic setup issues
        }

        // Prepare user data with academic year/term
        $userData = $user->toArrayWithoutPassword();
        $userData['academic_year'] = $academicYear;
        $userData['term'] = $term;
        $userData['academic_id'] = $academicId;

        // Set session
        Session::set('user', $userData);
        Session::set('user_id', $user->id);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $userData,
            'token' => $token,
            'academic_year' => $academicYear,
            'term' => $term,
        ];
    }

    /**
     * Get the currently logged-in user.
     *
     * @return UserDTO|null The user DTO or null if not logged in.
     */
    public function getCurrentUser(): ?UserDTO
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }

        return $this->userRepository->findById($userId);
    }

    /**
     * Logout the current user.
     *
     * @return array The result of the logout.
     */
    public function logout(): array
    {
        Session::destroy();
        
        return [
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => '/web/login'
        ];
    }

    /**
     * Refresh the JWT token for a user.
     *
     * @param UserDTO $user The user to refresh the token for.
     * @return string The new token.
     */
    public function refreshToken(UserDTO $user): string
    {
        return $this->generateJWT($user);
    }

    /**
     * Validate a JWT token and return the user.
     *
     * @param string $token The JWT token.
     * @return UserDTO|null The user DTO or null if invalid.
     * @throws AuthException If the token is invalid or expired.
     */
    public function validateToken(string $token): ?UserDTO
    {
        try {
            // Check if token has been blacklisted (logged out)
            try {
                $pdo = \App\Core\Database::getInstance()->getConnection();
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM blacklisted_tokens WHERE token = ?');
                $stmt->execute([$token]);
                $count = (int)$stmt->fetchColumn();
                if ($count > 0) {
                    return null; // token is invalid
                }
            } catch (\Throwable $e) {
                // if blacklist check fails, proceed to decode token (fail-open to avoid locking out users unexpectedly)
            }

            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));

            if (!isset($decoded->sub)) {
                throw AuthException::invalidToken();
            }

            return $this->userRepository->findById($decoded->sub);
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw AuthException::tokenExpired();
        } catch (\Exception $e) {
            throw AuthException::invalidToken();
        }
    }

    /**
     * Generate a JWT token for a user.
     *
     * @param UserDTO $user The user to generate the token for.
     * @return string The encoded JWT string.
     */
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

    /**
     * Change a user's password.
     *
     * @param int $userId The ID of the user.
     * @param string $currentPassword The current password.
     * @param string $newPassword The new password.
     * @return array The result of the password change.
     * @throws AuthException If user not found, current password invalid, or update fails.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            throw AuthException::userNotFound();
        }

        if (empty($user->password) || !password_verify($currentPassword, $user->password)) {
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

    /**
     * Reset a user's password without requiring current password.
     * Useful for password reset scenarios or admin-initiated password changes.
     *
     * @param int $userId The ID of the user.
     * @param string $newPassword The new password.
     * @param string $confirmPassword The password confirmation.
     * @return array The result of the password reset.
     * @throws AuthException If user not found, passwords don't match, or validation fails.
     */
    public function resetPassword(string $userId, string $newPassword, string $confirmPassword): array
    {
        // Validate user exists
        $user = $this->userRepository->findByUserId($userId);
        
        if (!$user) {
            throw AuthException::userNotFound();
        }

        // Validate passwords match
        if ($newPassword !== $confirmPassword) {
            throw new AuthException('New password and confirmation password do not match');
        }

        // Validate password strength
        if (strlen($newPassword) < 8) {
            throw new AuthException('Password must be at least 8 characters long');
        }

        // Check for at least one letter and one number
        if (!preg_match('/[a-zA-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            throw new AuthException('Password must contain at least one letter and one number');
        }

        // Hash and update password
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $success = $this->userRepository->updatePassword($userId, $hashedNewPassword);

        if (!$success) {
            throw AuthException::passwordUpdateFailed();
        }

        return [
            'success' => true,
            'message' => 'Password reset successfully'
        ];
    }

    /**
     * Check if an account is currently locked out.
     *
     * @param string $email The user's email.
     * @return array Status with 'locked' boolean and 'minutes_remaining' if locked.
     */
    public function checkAccountLockout(string $email): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return ['locked' => false, 'minutes_remaining' => 0];
        }

        // Check if locked by admin (permanent until unlocked)
        if ($user->locked_by_admin ?? false) {
            return ['locked' => true, 'minutes_remaining' => null];
        }

        // Check if temporary lockout is still active
        if (!empty($user->locked_until)) {
            $lockedUntil = new \DateTime($user->locked_until);
            $now = new \DateTime();
            
            if ($now < $lockedUntil) {
                $diff = $now->diff($lockedUntil);
                $minutesRemaining = ($diff->h * 60) + $diff->i + 1;
                return ['locked' => true, 'minutes_remaining' => $minutesRemaining];
            }
        }

        return ['locked' => false, 'minutes_remaining' => 0];
    }

    /**
     * Record a failed login attempt and lock account if threshold reached.
     *
     * @param string $email The user's email.
     * @return void
     */
    public function recordFailedAttempt(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return;
        }

        $attempts = ($user->failed_login_attempts ?? 0) + 1;
        $maxAttempts = 5; // Could be moved to config
        $lockoutDuration = 30; // minutes

        if ($attempts >= $maxAttempts) {
            // Lock the account
            $this->lockAccount($email, $lockoutDuration);
        } else {
            // Just increment the counter
            $this->userRepository->updateFailedAttempts($user->id, $attempts);
        }
    }

    /**
     * Reset failed login attempts counter.
     *
     * @param string $email The user's email.
     * @return void
     */
    public function resetFailedAttempts(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return;
        }

        $this->userRepository->resetFailedAttempts($user->id);
    }

    /**
     * Lock an account for a specified duration.
     *
     * @param string $email The user's email.
     * @param int $durationMinutes Duration in minutes.
     * @return void
     */
    public function lockAccount(string $email, int $durationMinutes): void
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return;
        }

        $lockedUntil = new \DateTime();
        $lockedUntil->modify("+{$durationMinutes} minutes");

        $this->userRepository->lockAccount($user->id, $lockedUntil->format('Y-m-d H:i:s'));
    }

    /**
     * Unlock a user account (admin function).
     *
     * @param string $email The user's email.
     * @return array Result of the unlock operation.
     */
    public function unlockAccount(string $email): array
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            throw AuthException::userNotFound();
        }

        $this->userRepository->unlockAccount($user->id);

        return [
            'success' => true,
            'message' => 'Account unlocked successfully'
        ];
    }

    /**
     * Handle forgot password request.
     * Generates a new random password and sends it to the user's email.
     *
     * @param string $email The user's email address.
     * @return array The result of the forgot password operation.
     * @throws AuthException If email is invalid or sending fails.
     */
    public function forgotPassword(string $email): array
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AuthException('Invalid email format');
        }

        // Look up user by email
        $user = $this->userRepository->findByEmail($email);
        
        // If user doesn't exist, return error message
        if (!$user) {
            throw new AuthException('User account with this email does not exist. Please contact administrator for assistance.');
        }

        // Generate a secure random password
        $newPassword = \App\Utils\PasswordGenerator::generate(12);
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password in database (use userId, not id)
        $success = $this->userRepository->updatePassword($user->userId, $hashedPassword);
        
        if (!$success) {
            error_log("Failed to update password for user: {$email}");
            throw new AuthException('Failed to process password reset');
        }

        // Send email with new password
        try {
            $emailService = new \Services\EmailService();
            $subject = 'Password Reset Request';
            $message = $this->buildPasswordResetEmail($user->username, $newPassword);
            
            $emailSent = $emailService->send($email, $message, $subject);
            
            if (!$emailSent) {
                throw new AuthException('Email service returned false - check SMTP configuration');
            }
        } catch (\Exception $e) {
            // SECURITY: Never log passwords - even in development
            error_log("Password reset email failed for user: {$email}");
            error_log("Email send error: " . $e->getMessage());
            
            // In production, throw the exception to prevent silent failures
            if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
                throw new AuthException('Failed to send password reset email. Please contact support.');
            }
            
            // In development, allow continuation but warn
            error_log("WARNING: Password reset completed but email not sent. User must contact admin.");
        }

        return [
            'success' => true,
            'message' => 'Password reset successful. A new password has been sent to your email.'
        ];
    }

    /**
     * Build HTML email content for password reset.
     *
     * @param string $username The user's username.
     * @param string $newPassword The new temporary password.
     * @return string HTML email content.
     */
    private function buildPasswordResetEmail(string $username, string $newPassword): string
    {
        return \App\Templates\Email\PasswordReset::generate([
            'username' => $username,
            'new_password' => $newPassword
        ]);
    }
}
