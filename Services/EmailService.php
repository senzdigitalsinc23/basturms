<?php
namespace Services;

use App\Core\Config;
use App\Core\NotificationService;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class EmailService implements NotificationService
{
    protected PHPMailer $mailer;

    public function __construct()
    {
        // Load config PHP files
        Config::load(dirname(__DIR__) . '/config');

        // Get DB config from your config helper
        $host = Config::get('mail.host');
        $port   = Config::get('mail.port');
        $user = Config::get('mail.user');
        $pass = Config::get('mail.pass');
        $encryption = Config::get('mail.encryption');
        $from = Config::get('mail.from');
        $name = Config::get('mail.name');

        //echo json_encode(Config::get('mail.pass'));exit;
        $this->mailer = new PHPMailer();

        // SMTP configuration
        $this->mailer->isSMTP();
        $this->mailer->Host = $host;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $user;
        $this->mailer->Password = $pass;
        $this->mailer->SMTPSecure = $encryption ?? 'tls';
        $this->mailer->SMTPDebug = 0;
        $this->mailer->Port = $port ?? 587;

        $this->mailer->setFrom($from, $name ?? 'MyApp');
    }

    public function send(string $to, string $message, string $subject = 'Notification'): bool
    {
        try {
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
            return false;
        }
    }
}
