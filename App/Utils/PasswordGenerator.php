<?php

namespace App\Utils;

/**
 * Utility class for generating secure random passwords.
 */
class PasswordGenerator
{
    /**
     * Generate a secure random password.
     *
     * @param int $length Length of the password (default: 12)
     * @return string The generated password
     */
    public static function generate(int $length = 12): string
    {
        // Character sets
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        // Combine all character sets
        $allCharacters = $uppercase . $lowercase . $numbers . $special;
        
        // Ensure minimum length
        if ($length < 8) {
            $length = 8;
        }
        
        // Generate password ensuring at least one character from each set
        $password = '';
        
        // Add at least one character from each set
        $password .= $uppercase[self::getRandomIndex(strlen($uppercase))];
        $password .= $lowercase[self::getRandomIndex(strlen($lowercase))];
        $password .= $numbers[self::getRandomIndex(strlen($numbers))];
        $password .= $special[self::getRandomIndex(strlen($special))];
        
        // Fill the rest with random characters from all sets
        for ($i = 4; $i < $length; $i++) {
            $password .= $allCharacters[self::getRandomIndex(strlen($allCharacters))];
        }
        
        // Shuffle the password to randomize character positions
        $password = str_shuffle($password);
        
        return $password;
    }
    
    /**
     * Get a cryptographically secure random index.
     *
     * @param int $max Maximum value (exclusive)
     * @return int Random index
     */
    private static function getRandomIndex(int $max): int
    {
        try {
            return random_int(0, $max - 1);
        } catch (\Exception $e) {
            // Fallback to mt_rand if random_int fails
            return mt_rand(0, $max - 1);
        }
    }
}
