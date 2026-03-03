<?php

namespace App\Services;

use App\Core\Database;
use Services\EmailService;
use PDO;

class NotificationService
{
    private Database $database;
    private LoggingService $logger;
    private EmailService $emailService;

    public function __construct(LoggingService $logger, EmailService $emailService)
    {
        $this->database = Database::getInstance();
        $this->logger = $logger;
        $this->emailService = $emailService;
    }

    /**
     * Send staff login credentials via email
     */
    public function sendCredentialsByEmail(string $staffId, string $email, string $username, string $password, array $staffInfo = []): bool
    {
        try {
            $staffName = ($staffInfo['first_name'] ?? '') . ' ' . ($staffInfo['last_name'] ?? '');
            
            $subject = "Your Login Credentials - Basturms School Management System";
            
            // Create HTML email body
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                    .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                    .credentials { background-color: #fff; padding: 15px; margin: 20px 0; border-left: 4px solid #4CAF50; }
                    .credential-item { margin: 10px 0; }
                    .credential-label { font-weight: bold; color: #555; }
                    .credential-value { color: #000; font-family: monospace; background: #f0f0f0; padding: 5px 10px; display: inline-block; }
                    .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                    .footer { text-align: center; padding: 20px; color: #777; font-size: 12px; }
                    .button { display: inline-block; padding: 12px 24px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Welcome to Basturms</h1>
                    </div>
                    <div class='content'>
                        <p>Dear <strong>{$staffName}</strong>,</p>
                        
                        <p>Welcome to Basturms School Management System! Your account has been created successfully.</p>
                        
                        <div class='credentials'>
                            <h3>Your Login Credentials</h3>
                            <div class='credential-item'>
                                <span class='credential-label'>Staff ID:</span>
                                <span class='credential-value'>{$staffId}</span>
                            </div>
                            <div class='credential-item'>
                                <span class='credential-label'>Username:</span>
                                <span class='credential-value'>{$username}</span>
                            </div>
                            <div class='credential-item'>
                                <span class='credential-label'>Temporary Password:</span>
                                <span class='credential-value'>{$password}</span>
                            </div>
                        </div>
                        
                        <div class='warning'>
                            <strong>⚠️ Important Security Notice:</strong><br>
                            Please login and change your password immediately for security purposes.
                        </div>
                        
                        <p style='text-align: center;'>
                            <a href='" . ($_ENV['APP_URL'] ?? 'https://basturms.com') . "/login' class='button'>Login Now</a>
                        </p>
                        
                        <p>If you have any questions or need assistance, please contact the administrator.</p>
                        
                        <p>Best regards,<br>
                        <strong>Basturms Administration Team</strong></p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                        <p>&copy; " . date('Y') . " Basturms School Management System. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            // Send email using EmailService
            $sent = $this->emailService->send($email, $message, $subject);

            if ($sent) {
                $this->logNotification($staffId, 'email', $email, 'credentials_shared', 'sent');
                $this->logger->logAudit(
                    'credentials_email_sent',
                    "Login credentials sent via email to {$email} for staff {$staffId}",
                    'system'
                );
            } else {
                $this->logNotification($staffId, 'email', $email, 'credentials_shared', 'failed');
                $this->logger->logAudit(
                    'credentials_email_failed',
                    "Failed to send login credentials via email to {$email} for staff {$staffId}",
                    'system'
                );
            }

            return $sent;

        } catch (\Exception $e) {
            $this->logNotification($staffId, 'email', $email, 'credentials_shared', 'failed');
            $this->logger->logAudit(
                'credentials_email_error',
                "Error sending credentials email: " . $e->getMessage(),
                'system'
            );
            return false;
        }
    }

    /**
     * Send staff login credentials via SMS
     */
    public function sendCredentialsBySMS(string $staffId, string $phone, string $username, string $password, array $staffInfo = []): bool
    {
        try {
            $staffName = ($staffInfo['first_name'] ?? '') . ' ' . ($staffInfo['last_name'] ?? '');
            
            // Format SMS message (keep it short for SMS)
            $message = "Dear {$staffName}, ";
            $message .= "Your Basturms login: ";
            $message .= "Username: {$username}, ";
            $message .= "Password: {$password}. ";
            $message .= "Please change password after first login.";

            // TODO: Integrate with SMS gateway (Twilio, Nexmo, etc.)
            // For now, we'll simulate SMS sending
            $sent = $this->sendSMS($phone, $message);

            if ($sent) {
                $this->logNotification($staffId, 'sms', $phone, 'credentials_shared', 'sent');
                $this->logger->logAudit(
                    'credentials_sms_sent',
                    "Login credentials sent via SMS to {$phone} for staff {$staffId}",
                    'system'
                );
            } else {
                $this->logNotification($staffId, 'sms', $phone, 'credentials_shared', 'failed');
                $this->logger->logAudit(
                    'credentials_sms_failed',
                    "Failed to send login credentials via SMS to {$phone} for staff {$staffId}",
                    'system'
                );
            }

            return $sent;

        } catch (\Exception $e) {
            $this->logger->logAudit(
                'credentials_sms_error',
                "Error sending credentials SMS: " . $e->getMessage(),
                'system'
            );
            return false;
        }
    }

    /**
     * Send SMS using configured gateway
     */
    private function sendSMS(string $phone, string $message): bool
    {
        // TODO: Implement actual SMS gateway integration
        // Example gateways: Twilio, Nexmo, Africa's Talking, etc.
        
        // For now, log the SMS that would be sent
        $this->logger->logAudit(
            'sms_simulation',
            "SMS to {$phone}: {$message}",
            'system'
        );

        // Return true to simulate successful sending
        // In production, this should return the actual result from SMS gateway
        return true;
    }

    /**
     * Log notification attempt
     */
    private function logNotification(string $staffId, string $type, string $recipient, string $purpose, string $status): void
    {
        try {
            $pdo = $this->database->getConnection();
            
            $sql = "INSERT INTO notification_logs (staff_id, notification_type, recipient, purpose, status, sent_at)
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$staffId, $type, $recipient, $purpose, $status]);
            
        } catch (\Exception $e) {
            // Silently fail - don't break the flow if logging fails
            $this->logger->logAudit(
                'notification_log_error',
                "Failed to log notification: " . $e->getMessage(),
                'system'
            );
        }
    }

    /**
     * Send credentials via both email and SMS
     */
    public function sendCredentialsBoth(string $staffId, string $email, string $phone, string $username, string $password, array $staffInfo = []): array
    {
        $results = [
            'email' => $this->sendCredentialsByEmail($staffId, $email, $username, $password, $staffInfo),
            'sms' => $this->sendCredentialsBySMS($staffId, $phone, $username, $password, $staffInfo)
        ];

        return $results;
    }
}
