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
        
        // Gmail requires SSL on port 465 or TLS on port 587
        if ($port == 465) {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        } else {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
        }
        
        $this->mailer->Port = $port ?? 587;
        
        // Add SMTPOptions to handle SSL certificate verification issues
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Disable debug output and set short timeout for faster fallback
        $this->mailer->SMTPDebug = 0; // Disable debug to allow fallback
        $this->mailer->Timeout = 5; // 5 second timeout
        $this->mailer->SMTPKeepAlive = false;

        $this->mailer->setFrom($from, $name ?? 'MyApp');
    }

    public function send(string $to, string $message, string $subject = 'Notification'): bool
    {
        try {
            $this->mailer->clearAddresses(); // Clear any previous addresses
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;

            $result = $this->mailer->send();
            
            if (!$result) {
                // If send() returns false, throw exception to trigger fallback
                throw new \Exception("SMTP send failed: " . $this->mailer->ErrorInfo);
            }
            
            return true;
        } catch (\Exception $e) {
            // SMTP failed, try fallback to PHP mail() for local development
            error_log("SMTP failed, attempting fallback to PHP mail(): " . $e->getMessage());
            
            try {
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: " . ($this->mailer->From ?? 'noreply@localhost') . "\r\n";
                
                $result = mail($to, $subject, $message, $headers);
                
                if ($result) {
                    error_log("Email sent successfully via PHP mail() to: {$to}");
                    return true;
                } else {
                    throw new \Exception("Both SMTP and PHP mail() failed");
                }
            } catch (\Exception $fallbackError) {
                // Both methods failed
                throw new \Exception("Email send failed: SMTP error: " . $e->getMessage() . " | PHP mail() also failed");
            }
        }
    }
}
