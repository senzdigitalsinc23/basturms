<?php

namespace App\Templates\Email;

/**
 * Email template for student registration
 */
class StudentRegistration
{
    /**
     * Generate HTML email for student registration
     *
     * @param array $data Email data
     * @return string HTML email content
     */
    public static function generate(array $data): string
    {
        $studentName = $data['student_name'] ?? 'Student';
        $studentNo = $data['student_no'] ?? 'N/A';
        $username = $data['username'] ?? 'N/A';
        $password = $data['password'] ?? 'N/A';
        $className = $data['class_name'] ?? 'N/A';
        $appName = $_ENV['APP_NAME'] ?? 'BASTURMS';
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        $supportEmail = $_ENV['SUPPORT_EMAIL'] ?? 'support@basturms.com';

        return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Welcome to {$appName}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .welcome-message {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #667eea;
            font-size: 16px;
        }
        .credentials {
            background-color: #fff;
            border: 2px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .credential-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .credential-row:last-child {
            border-bottom: none;
        }
        .credential-label {
            font-weight: 600;
            color: #495057;
        }
        .credential-value {
            font-family: 'Courier New', monospace;
            color: #667eea;
            font-weight: 600;
            font-size: 16px;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .warning-box strong {
            color: #856404;
        }
        .button {
            display: inline-block;
            padding: 14px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
            text-align: center;
        }
        .button:hover {
            opacity: 0.9;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 25px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        ul {
            padding-left: 20px;
        }
        ul li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🎓 Welcome to {$appName}</h1>
        </div>
        
        <div class='content'>
            <p class='welcome-message'>Hello {$studentName}!</p>
            
            <p>Congratulations! Your student account has been successfully created. We're excited to have you join our school community.</p>
            
            <div class='info-box'>
                <h3>📋 Student Information</h3>
                <p><strong>Student Number:</strong> {$studentNo}</p>
                <p><strong>Class:</strong> {$className}</p>
            </div>
            
            <div class='credentials'>
                <h3 style='margin-top: 0; color: #667eea;'>🔐 Your Login Credentials</h3>
                <p style='margin-bottom: 15px;'>Use these credentials to access the student portal:</p>
                
                <div class='credential-row'>
                    <span class='credential-label'>Username:</span>
                    <span class='credential-value'>{$username}</span>
                </div>
                
                <div class='credential-row'>
                    <span class='credential-label'>Password:</span>
                    <span class='credential-value'>{$password}</span>
                </div>
            </div>
            
            <div class='warning-box'>
                <strong>⚠️ Important Security Notice:</strong>
                <ul>
                    <li>Please change your password immediately after your first login</li>
                    <li>Never share your password with anyone</li>
                    <li>Keep this email secure or delete it after changing your password</li>
                </ul>
            </div>
            
            <div style='text-align: center;'>
                <a href='{$appUrl}/web/login' class='button'>Login to Student Portal</a>
            </div>
            
            <div class='info-box'>
                <h3>📚 Next Steps</h3>
                <ol>
                    <li>Click the button above to access the student portal</li>
                    <li>Login with your credentials</li>
                    <li>Change your password in the settings</li>
                    <li>Complete your profile information</li>
                    <li>Explore the portal features</li>
                </ol>
            </div>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
        </div>
        
        <div class='footer'>
            <p><strong>{$appName}</strong></p>
            <p>Need help? Contact us at <a href='mailto:{$supportEmail}'>{$supportEmail}</a></p>
            <p style='margin-top: 15px; font-size: 12px; color: #adb5bd;'>
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
        ";
    }

    /**
     * Generate plain text version
     *
     * @param array $data Email data
     * @return string Plain text email content
     */
    public static function generatePlainText(array $data): string
    {
        $studentName = $data['student_name'] ?? 'Student';
        $studentNo = $data['student_no'] ?? 'N/A';
        $username = $data['username'] ?? 'N/A';
        $password = $data['password'] ?? 'N/A';
        $className = $data['class_name'] ?? 'N/A';
        $appName = $_ENV['APP_NAME'] ?? 'BASTURMS';
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        $supportEmail = $_ENV['SUPPORT_EMAIL'] ?? 'support@basturms.com';

        return "
Welcome to {$appName}!

Hello {$studentName},

Congratulations! Your student account has been successfully created.

STUDENT INFORMATION
-------------------
Student Number: {$studentNo}
Class: {$className}

YOUR LOGIN CREDENTIALS
----------------------
Username: {$username}
Password: {$password}

IMPORTANT SECURITY NOTICE
-------------------------
- Please change your password immediately after your first login
- Never share your password with anyone
- Keep this email secure or delete it after changing your password

NEXT STEPS
----------
1. Visit: {$appUrl}/web/login
2. Login with your credentials
3. Change your password in the settings
4. Complete your profile information
5. Explore the portal features

If you have any questions or need assistance, please contact us at {$supportEmail}.

---
{$appName}
This is an automated message. Please do not reply to this email.
        ";
    }
}
