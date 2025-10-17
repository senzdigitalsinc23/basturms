<?php

namespace App\Exceptions;

use Exception;

class AuthException extends Exception
{
    public function __construct(string $message = "Authentication error", int $code = 401, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function invalidCredentials(): self
    {
        return new self("Invalid email or password", 401);
    }

    public static function emailAlreadyExists(string $email): self
    {
        return new self("Email {$email} is already registered", 409);
    }

    public static function registrationFailed(): self
    {
        return new self("User registration failed", 500);
    }

    public static function accountInactive(): self
    {
        return new self("Account is inactive", 403);
    }

    public static function userNotFound(): self
    {
        return new self("User not found", 404);
    }

    public static function invalidCurrentPassword(): self
    {
        return new self("Current password is incorrect", 400);
    }

    public static function passwordUpdateFailed(): self
    {
        return new self("Failed to update password", 500);
    }

    public static function tokenExpired(): self
    {
        return new self("Token has expired", 401);
    }

    public static function invalidToken(): self
    {
        return new self("Invalid token", 401);
    }

    public static function unauthorized(): self
    {
        return new self("Unauthorized access", 401);
    }
}
