<?php
namespace App\Jobs;

use App\Core\Job;
use App\Core\ShouldQueue;

class SendWelcomeEmailJob implements Job, ShouldQueue
{
    protected string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function handle(): void
    {
        // Send email logic (or log for demo)
        file_put_contents(__DIR__ . '/../../storage/logs/emails.log',
            "Queued email sent to {$this->email}\n", FILE_APPEND);
    }
}
