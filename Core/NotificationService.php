<?php
namespace App\Core;

interface NotificationService
{
    /**
     * Send a message
     *
     * @param string $to
     * @param string $message
     */
    public function send(string $to, string $message): bool;
}
