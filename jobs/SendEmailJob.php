<?php

use App\Core\Queue;
use App\Core\Job;
use App\Services\EmailService;

class SendEmailJob implements Job
{
    protected string $to;
    protected string $message;
    protected EmailService $mailer;

    public function __construct(EmailService $mailer, string $to, string $message)
    {
        $this->mailer = $mailer;
        $this->to = $to;
        $this->message = $message;
    }

    public function handle(): void
    {
        $this->mailer->send($this->to, $this->message);
    }
}
