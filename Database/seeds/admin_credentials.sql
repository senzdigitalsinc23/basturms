-- Admin Login Credentials Setup
-- Run this SQL script to create admin and HR incharge users

-- Create Human Resources unit if it doesn't exist
INSERT INTO units (name, code, description)
VALUES ('Human Resources', 'HR', 'Human Resources Department')
ON DUPLICATE KEY UPDATE name = name;

-- Get the HR unit ID
SET @hr_unit_id = (SELECT id FROM units WHERE name = 'Human Resources' LIMIT 1);

-- Admin User
-- Email: admin@ghs.gov.gh
-- Password: admin123
INSERT INTO validation_staff (name, email, password, role, unit_id)
VALUES (
    'System Administrator',
    'admin@ghs.gov.gh',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    @hr_unit_id
)
ON DUPLICATE KEY UPDATE 
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    role = 'admin',
    unit_id = @hr_unit_id;

-- HR Incharge User
-- Email: incharge1@validation.com
-- Password: incharge123
INSERT INTO validation_staff (name, email, password, role, unit_id)
VALUES (
    'HR Incharge',
    'incharge1@validation.com',
    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
    'incharge',
    @hr_unit_id
)
ON DUPLICATE KEY UPDATE 
    password = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
    role = 'incharge',
    unit_id = @hr_unit_id;

-- Display credentials
SELECT '=== LOGIN CREDENTIALS ===' as 'Info';
SELECT 
    'Admin User' as 'User Type',
    'admin@ghs.gov.gh' as 'Email',
    'admin123' as 'Password'
UNION ALL
SELECT 
    'HR Incharge' as 'User Type',
    'incharge1@validation.com' as 'Email',
    'incharge123' as 'Password';
