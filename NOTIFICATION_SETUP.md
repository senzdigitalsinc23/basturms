# Notification Service Setup

## Overview
The notification service uses the existing EmailService (PHPMailer) to send staff login credentials via email and SMS after registration.

## Features
- Professional HTML email templates
- Send credentials via email using PHPMailer
- Send credentials via SMS
- Send credentials via both email and SMS
- Automatic logging of all notification attempts
- Transaction-safe credential sharing
- SMTP with fallback to PHP mail()

## Email Configuration

The system uses the existing EmailService which is already configured. Email settings are in `config/mail.php`:

```php
return [
    'host' => env('MAIL_HOST', 'smtp.gmail.com'),
    'port' => env('MAIL_PORT', 587),
    'user' => env('MAIL_USERNAME'),
    'pass' => env('MAIL_PASSWORD'),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'from' => env('MAIL_FROM_ADDRESS', 'noreply@basturms.com'),
    'name' => env('MAIL_FROM_NAME', 'Basturms')
];
```

### Environment Variables (.env)
```env
# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@basturms.com
MAIL_FROM_NAME=Basturms School System
APP_URL=https://basturms.com
```

### Gmail Setup
1. Enable 2-Factor Authentication on your Gmail account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the App Password in `MAIL_PASSWORD`

## Email Template

The credential email includes:
- Professional HTML design
- Staff name personalization
- Formatted credentials (Staff ID, Username, Password)
- Security warning to change password
- Direct login button
- Responsive design

### SMS Configuration (Optional)
To enable SMS functionality, integrate with an SMS gateway provider:

#### Option 1: Twilio
```env
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=your_twilio_number
```

#### Option 2: Africa's Talking
```env
AFRICASTALKING_USERNAME=your_username
AFRICASTALKING_API_KEY=your_api_key
AFRICASTALKING_SENDER_ID=your_sender_id
```

#### Option 3: Nexmo/Vonage
```env
NEXMO_API_KEY=your_api_key
NEXMO_API_SECRET=your_api_secret
NEXMO_FROM_NUMBER=your_number
```

## Database Migration
Run the migration to create the notification_logs table:

```bash
php kiro migrate
```

## API Usage

### 1. Staff Registration (Auto-returns credentials)
```http
POST /api/v1/staff/register
Content-Type: application/json

{
  "personal_contact": {
    "first_name": "Joseph",
    "last_name": "Konnie",
    "email": "joseph.konnie@basturms.com",
    "phone": "0247760226",
    ...
  },
  ...
}
```

Response includes login credentials:
```json
{
  "success": true,
  "message": "Staff registered successfully",
  "data": {
    "staff_id": "LBAST26001",
    "email": "joseph.konnie@basturms.com",
    "login_credentials": {
      "username": "joseph.konnie@basturms.com",
      "temporary_password": "a3f7b2c9",
      "note": "Please change your password after first login"
    }
  }
}
```

### 2. Share Credentials (Send via Email/SMS)
```http
POST /api/v1/staff/share-credentials
Content-Type: application/json

{
  "staff_id": "LBAST26001",
  "username": "joseph.konnie@basturms.com",
  "password": "a3f7b2c9",
  "method": "email"
}
```

**Method options:**
- `email` - Send via email only
- `sms` - Send via SMS only
- `both` - Send via both email and SMS

Response:
```json
{
  "success": true,
  "message": "Credentials sent via email",
  "data": {
    "email_sent": true,
    "sms_sent": false
  }
}
```

## Implementation Notes

### Current Status
- ✅ Email sending is configured (uses PHP `mail()` function)
- ⚠️ SMS sending is simulated (logs to audit log)
- ✅ Notification logging is active
- ✅ Transaction-safe operations

### To Enable Real SMS
1. Choose an SMS gateway provider (Twilio, Africa's Talking, etc.)
2. Add credentials to `.env`
3. Update `NotificationService::sendSMS()` method with actual API integration
4. Test with a real phone number

### Email Template Customization
Edit the email message in `App/Services/NotificationService.php`:
- Method: `sendCredentialsByEmail()`
- Customize subject, body, and formatting

### SMS Template Customization
Edit the SMS message in `App/Services/NotificationService.php`:
- Method: `sendCredentialsBySMS()`
- Keep messages under 160 characters for single SMS

## Security Considerations

1. **Temporary Passwords**: Auto-generated passwords are 8 characters (secure random)
2. **Force Password Change**: Users should change password on first login
3. **Audit Logging**: All credential sharing attempts are logged
4. **Notification Logs**: Track delivery status for compliance
5. **HTTPS Required**: Always use HTTPS in production

## Troubleshooting

### Email Not Sending
- Check PHP `mail()` configuration
- Verify SMTP settings on server
- Check spam folder
- Review audit logs for errors

### SMS Not Sending
- Verify SMS gateway credentials
- Check account balance with provider
- Ensure phone number format is correct
- Review notification_logs table for status

## Future Enhancements
- [ ] Email templates with HTML formatting
- [ ] SMS gateway integration (Twilio/Africa's Talking)
- [ ] Retry mechanism for failed notifications
- [ ] Bulk credential sharing
- [ ] Custom message templates
- [ ] Notification preferences per staff
