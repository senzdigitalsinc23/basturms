# Admin Login Credentials

## Default Login Credentials

### Admin User
- **Email**: `admin@ghs.gov.gh`
- **Password**: `admin123`
- **Role**: Administrator (full access)

### HR Incharge
- **Email**: `incharge1@validation.com`
- **Password**: `incharge123`
- **Role**: HR Incharge (full HR access)

## Setup Instructions

### Option 1: Run SQL Script (Recommended)
```bash
# Navigate to the validation-api directory
cd validation-api

# Run the SQL script
mysql -u your_username -p agh_validations < Database/seeds/admin_credentials.sql
```

### Option 2: Manual SQL Execution
1. Open your MySQL client (phpMyAdmin, MySQL Workbench, etc.)
2. Select the `agh_validations` database
3. Copy and paste the contents of `Database/seeds/admin_credentials.sql`
4. Execute the SQL

### Option 3: Using PHP Script
Create a file `seed_admin.php` in the validation-api root:

```php
<?php
require_once 'vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance()->getConnection();

// Create HR unit
$db->exec("INSERT INTO units (name, description) VALUES ('Human Resources', 'HR Department') ON DUPLICATE KEY UPDATE name = name");

$hrUnit = $db->query("SELECT id FROM units WHERE name = 'Human Resources'")->fetch(PDO::FETCH_ASSOC);
$hrUnitId = $hrUnit['id'];

// Admin
$stmt = $db->prepare("INSERT INTO validation_staff (name, email, password, role, unit_id) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password)");
$stmt->execute(['System Administrator', 'admin@ghs.gov.gh', password_hash('admin123', PASSWORD_BCRYPT), 'admin', $hrUnitId]);

// HR Incharge
$stmt->execute(['HR Incharge', 'incharge1@validation.com', password_hash('incharge123', PASSWORD_BCRYPT), 'incharge', $hrUnitId]);

echo "Admin credentials created!\n";
```

Then run: `php seed_admin.php`

## Security Notes

⚠️ **IMPORTANT**: Change these default passwords immediately after first login in production!

## Troubleshooting

If you can't login:
1. Verify the users exist: `SELECT * FROM validation_staff WHERE role = 'admin';`
2. Check the unit exists: `SELECT * FROM units WHERE name = 'Human Resources';`
3. Verify password hash is correct
4. Check API logs for authentication errors
