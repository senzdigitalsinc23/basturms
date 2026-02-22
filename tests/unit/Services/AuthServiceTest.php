<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuthService;
use App\Services\AcademicSetupService;
use App\Repositories\UserRepository;
use App\DTOs\LoginRequestDTO;
use App\DTOs\RegisterRequestDTO;
use App\DTOs\UserDTO;
use App\Exceptions\AuthException;

/**
 * Test authentication service functionality
 */
class AuthServiceTest extends TestCase
{
    private $mockUserRepo;
    private $mockAcademicService;
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set JWT_SECRET for testing
        $_ENV['JWT_SECRET'] = 'test_secret_key_for_unit_testing_only';

        $this->mockUserRepo = $this->createMock(UserRepository::class);
        $this->mockAcademicService = $this->createMock(AcademicSetupService::class);
        
        $this->authService = new AuthService($this->mockUserRepo, $this->mockAcademicService);
    }

    protected function tearDown(): void
    {
        unset($_ENV['JWT_SECRET']);
        parent::tearDown();
    }

    /**
     * Test JWT_SECRET is required
     */
    public function testConstructorRequiresJwtSecret(): void
    {
        unset($_ENV['JWT_SECRET']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JWT_SECRET environment variable is not configured');

        new AuthService($this->mockUserRepo, $this->mockAcademicService);
    }

    /**
     * Test successful user registration
     */
    public function testSuccessfulRegistration(): void
    {
        $registerData = RegisterRequestDTO::fromArray([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'role_id' => 2
        ]);

        $this->mockUserRepo->method('emailExists')->willReturn(false);
        
        $mockUser = $this->createMock(UserDTO::class);
        $mockUser->method('toArrayWithoutPassword')->willReturn([
            'id' => 1,
            'username' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->mockUserRepo->method('create')->willReturn($mockUser);

        $result = $this->authService->register($registerData);

        $this->assertTrue($result['success']);
        $this->assertEquals('User registered successfully', $result['message']);
        $this->assertArrayHasKey('user', $result);
    }

    /**
     * Test registration fails when email exists
     */
    public function testRegistrationFailsWhenEmailExists(): void
    {
        $registerData = RegisterRequestDTO::fromArray([
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'SecurePass123!'
        ]);

        $this->mockUserRepo->method('emailExists')->willReturn(true);

        $this->expectException(AuthException::class);

        $this->authService->register($registerData);
    }

    /**
     * Test successful login
     */
    public function testSuccessfulLogin(): void
    {
        $loginData = LoginRequestDTO::fromArray([
            'email' => 'test@example.com',
            'password' => 'SecurePass123!'
        ]);

        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'user_id' => 'user_123',
            'username' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('SecurePass123!', PASSWORD_DEFAULT),
            'status' => 'active',
            'role_id' => 2
        ]);

        $this->mockUserRepo->method('findByEmail')->willReturn($mockUser);
        $this->mockUserRepo->method('resetFailedAttempts')->willReturn(true);

        $this->mockAcademicService->method('getActiveAcademicYear')->willReturn([
            'success' => true,
            'data' => [
                'academic_year' => '2025/2026',
                'term' => 'First Term',
                'academic_id' => 1
            ]
        ]);

        $result = $this->authService->login($loginData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Login successful', $result['message']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('2025/2026', $result['academic_year']);
    }

    /**
     * Test login fails with invalid credentials
     */
    public function testLoginFailsWithInvalidCredentials(): void
    {
        $loginData = LoginRequestDTO::fromArray([
            'email' => 'test@example.com',
            'password' => 'WrongPassword'
        ]);

        $this->mockUserRepo->method('findByEmail')->willReturn(null);

        $this->expectException(AuthException::class);

        $this->authService->login($loginData);
    }

    /**
     * Test login fails with wrong password
     */
    public function testLoginFailsWithWrongPassword(): void
    {
        $loginData = LoginRequestDTO::fromArray([
            'email' => 'test@example.com',
            'password' => 'WrongPassword'
        ]);

        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'email' => 'test@example.com',
            'password' => password_hash('CorrectPassword', PASSWORD_DEFAULT),
            'status' => 'active'
        ]);

        $this->mockUserRepo->method('findByEmail')->willReturn($mockUser);

        $this->expectException(AuthException::class);

        $this->authService->login($loginData);
    }

    /**
     * Test login fails for inactive account
     */
    public function testLoginFailsForInactiveAccount(): void
    {
        $loginData = LoginRequestDTO::fromArray([
            'email' => 'test@example.com',
            'password' => 'SecurePass123!'
        ]);

        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'email' => 'test@example.com',
            'password' => password_hash('SecurePass123!', PASSWORD_DEFAULT),
            'status' => 'inactive'
        ]);

        $this->mockUserRepo->method('findByEmail')->willReturn($mockUser);

        $this->expectException(AuthException::class);

        $this->authService->login($loginData);
    }

    /**
     * Test password change with valid credentials
     */
    public function testSuccessfulPasswordChange(): void
    {
        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'user_id' => 'user_1',
            'username' => 'Test',
            'email' => 'test@example.com',
            'password' => password_hash('OldPassword', PASSWORD_DEFAULT),
            'status' => 'active'
        ]);

        $this->mockUserRepo->method('findById')->willReturn($mockUser);
        $this->mockUserRepo->method('updatePassword')->willReturn(true);

        $result = $this->authService->changePassword(1, 'OldPassword', 'NewPassword123!');

        $this->assertTrue($result['success']);
        $this->assertEquals('Password updated successfully', $result['message']);
    }

    /**
     * Test password change fails with wrong current password
     */
    public function testPasswordChangeFailsWithWrongCurrentPassword(): void
    {
        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'user_id' => 'user_1',
            'username' => 'Test',
            'email' => 'test@example.com',
            'password' => password_hash('CorrectPassword', PASSWORD_DEFAULT),
            'status' => 'active'
        ]);

        $this->mockUserRepo->method('findById')->willReturn($mockUser);

        $this->expectException(AuthException::class);

        $this->authService->changePassword(1, 'WrongPassword', 'NewPassword123!');
    }

    /**
     * Test account lockout after failed attempts
     */
    public function testAccountLockoutAfterFailedAttempts(): void
    {
        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'user_id' => 'user_1',
            'username' => 'Test',
            'email' => 'test@example.com',
            'password' => null,
            'status' => 'active',
            'failed_login_attempts' => 4
        ]);

        $this->mockUserRepo->method('findByEmail')->willReturn($mockUser);
        $this->mockUserRepo->expects($this->once())
            ->method('lockAccount');

        $this->authService->recordFailedAttempt('test@example.com');
    }

    /**
     * Test checking account lockout status
     */
    public function testCheckAccountLockoutStatus(): void
    {
        $lockedUntil = (new \DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');
        
        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'user_id' => 'user_1',
            'username' => 'Test',
            'email' => 'test@example.com',
            'password' => null,
            'status' => 'active',
            'locked_until' => $lockedUntil
        ]);

        $this->mockUserRepo->method('findByEmail')->willReturn($mockUser);

        $result = $this->authService->checkAccountLockout('test@example.com');

        $this->assertTrue($result['locked']);
        $this->assertGreaterThan(0, $result['minutes_remaining']);
    }

    /**
     * Test password reset validation
     */
    public function testPasswordResetValidation(): void
    {
        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'user_id' => 'user_123',
            'username' => 'Test',
            'email' => 'test@example.com',
            'password' => null,
            'status' => 'active'
        ]);

        $this->mockUserRepo->method('findByUserId')->willReturn($mockUser);

        // Test password mismatch
        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('do not match');

        $this->authService->resetPassword('user_123', 'NewPass123!', 'DifferentPass123!');
    }

    /**
     * Test password reset requires minimum length
     */
    public function testPasswordResetRequiresMinimumLength(): void
    {
        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'user_id' => 'user_123',
            'username' => 'Test',
            'email' => 'test@example.com',
            'password' => null,
            'status' => 'active'
        ]);

        $this->mockUserRepo->method('findByUserId')->willReturn($mockUser);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('at least 8 characters');

        $this->authService->resetPassword('user_123', 'Short1!', 'Short1!');
    }

    /**
     * Test password reset requires letter and number
     */
    public function testPasswordResetRequiresLetterAndNumber(): void
    {
        $mockUser = UserDTO::fromArray([
            'id' => 1,
            'user_id' => 'user_123',
            'username' => 'Test',
            'email' => 'test@example.com',
            'password' => null,
            'status' => 'active'
        ]);

        $this->mockUserRepo->method('findByUserId')->willReturn($mockUser);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('letter and one number');

        $this->authService->resetPassword('user_123', 'NoNumbers!', 'NoNumbers!');
    }
}
