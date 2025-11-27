# LOTD - OTP Verification System

A complete OTP (One-Time Password) verification system with PHP backend and MySQL database.

## 📁 Project Structure

```
LOTD/
├── index.html              # Entry form page
├── verify-otp.html         # OTP verification page
├── next-step.html          # Success/confirmation page
├── api/
│   ├── config/
│   │   ├── database.php    # Database configuration
│   │   ├── mail.php        # Email service configuration
│   │   └── sms.php         # SMS service configuration
│   ├── database/
│   │   └── schema.sql      # Database schema
│   ├── logs/               # SMS logs (test mode)
│   ├── setup.php           # Database setup script
│   ├── send-otp.php        # API: Generate & send OTP
│   ├── verify-otp.php      # API: Verify OTP
│   └── resend-otp.php      # API: Resend OTP
├── css/
├── images/
└── frontend/
```

## 🚀 Quick Setup

### 1. Start Laragon
Make sure MySQL and Apache are running in Laragon.

### 2. Setup Database
Open your browser and navigate to:
```
http://localhost/LOTD/api/setup.php
```
This will automatically create the database and all required tables.

### 3. Access the Application
```
http://localhost/LOTD/
```

## ⚙️ Configuration

### Database Configuration
Edit `api/config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lotd_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Laragon default is empty
```

### Email Configuration
Edit `api/config/mail.php`:
- For local testing: Uses PHP's `mail()` function
- For production: Configure SendGrid or Mailgun API keys

```php
// Change service type
define('MAIL_SERVICE', 'smtp'); // Options: 'smtp', 'sendgrid', 'mailgun'

// SendGrid (recommended for production)
define('SENDGRID_API_KEY', 'your-api-key-here');
```

### SMS Configuration
Edit `api/config/sms.php`:
- For local testing: SMS messages are logged to `api/logs/sms_log.txt`
- For production: Configure Twilio, Nexmo, or MSG91

```php
// Change service type
define('SMS_SERVICE', 'log'); // Options: 'log', 'twilio', 'nexmo', 'msg91'

// Twilio (popular choice)
define('TWILIO_ACCOUNT_SID', 'your-account-sid');
define('TWILIO_AUTH_TOKEN', 'your-auth-token');
define('TWILIO_FROM_NUMBER', '+1234567890');
```

## 📊 Database Schema

### entries
Stores user registration data.
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| entry_number | VARCHAR(20) | Unique entry identifier |
| name | VARCHAR(255) | User's full name |
| email | VARCHAR(255) | Email address |
| phone | VARCHAR(20) | Phone number |
| is_verified | TINYINT | Verification status |
| verified_at | DATETIME | Verification timestamp |

### otps
Stores OTP codes.
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| entry_id | INT | Foreign key to entries |
| otp_code | VARCHAR(6) | 6-digit OTP |
| is_used | TINYINT | Usage status |
| attempts | INT | Verification attempts |
| expires_at | DATETIME | Expiration time (10 min) |

### otp_attempts
Rate limiting and security tracking.

### notification_logs
Email and SMS delivery logs.

## 🔌 API Endpoints

### POST /api/send-otp.php
Generate and send OTP to email and phone.

**Request:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890"
}
```

**Response:**
```json
{
    "success": true,
    "message": "OTP sent successfully",
    "data": {
        "entry_id": 1,
        "entry_number": "LOTDABC123",
        "email_sent": true,
        "sms_sent": true,
        "expires_in": 600
    }
}
```

### POST /api/verify-otp.php
Verify the OTP code.

**Request:**
```json
{
    "entry_id": 1,
    "otp": "123456"
}
```

**Response:**
```json
{
    "success": true,
    "message": "OTP verified successfully",
    "data": {
        "entry_number": "LOTDABC123",
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "1234567890",
        "verified_at": "2025-11-27 10:30:00"
    }
}
```

### POST /api/resend-otp.php
Resend OTP to the user.

**Request:**
```json
{
    "entry_id": 1
}
```

## 🔒 Security Features

1. **Rate Limiting**
   - Max 10 OTP requests per hour per IP
   - Max 5 failed verification attempts per 15 minutes
   - Max 3 attempts per OTP code

2. **OTP Security**
   - 6-digit random OTP
   - 10-minute expiration
   - Single-use tokens
   - Secure random number generation

3. **Input Validation**
   - Email format validation
   - Phone number validation (min 10 digits)
   - SQL injection prevention (prepared statements)

## 🧪 Testing (Local Mode)

In test mode:
- **Email**: Uses PHP's mail() function (check Laragon's mail catcher)
- **SMS**: Logs to `api/logs/sms_log.txt`

Check the SMS log to see sent OTP codes:
```
cat api/logs/sms_log.txt
```

## 🚀 Production Checklist

- [ ] Configure real email service (SendGrid/Mailgun)
- [ ] Configure real SMS service (Twilio)
- [ ] Update database credentials
- [ ] Enable HTTPS
- [ ] Remove setup.php after installation
- [ ] Configure proper error logging
- [ ] Set up database backups

## 📧 Email Services Setup

### SendGrid
1. Create account at sendgrid.com
2. Create API key
3. Update `SENDGRID_API_KEY` in `api/config/mail.php`
4. Set `MAIL_SERVICE` to `'sendgrid'`

### Mailgun
1. Create account at mailgun.com
2. Get API key and domain
3. Update credentials in `api/config/mail.php`
4. Set `MAIL_SERVICE` to `'mailgun'`

## 📱 SMS Services Setup

### Twilio
1. Create account at twilio.com
2. Get Account SID, Auth Token, and Phone Number
3. Update credentials in `api/config/sms.php`
4. Set `SMS_SERVICE` to `'twilio'`

### Nexmo/Vonage
1. Create account at vonage.com
2. Get API Key and Secret
3. Update credentials in `api/config/sms.php`
4. Set `SMS_SERVICE` to `'nexmo'`

## 📝 License

This project is for educational purposes.
