<?php
// App\Middleware\SecureHeaders.php
namespace App\Middleware;

class SecureHeaders
{
    public static function send(): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');
        // Referrer policy
        header('Referrer-Policy: no-referrer-when-downgrade');
        // XSS protection (for legacy browsers)
        header('X-XSS-Protection: 1; mode=block');
        // Content Security Policy (adjust as needed)
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; object-src 'none';");
        // Strict Transport Security (only if using HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
}
