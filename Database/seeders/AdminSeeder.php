<?php

namespace Database\Seeders;

use PDO;

class AdminSeeder
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function run(): void
    {
        echo "Seeding admin user...\n";

        // Create Human Resources unit if it doesn't exist
        $stmt = $this->db->prepare("
            INSERT INTO units (name, description)
            VALUES ('Human Resources', 'Human Resources Department')
            ON DUPLICATE KEY UPDATE name = name
        ");
        $stmt->execute();

        // Get HR unit ID
        $stmt = $this->db->prepare("SELECT id FROM units WHERE name = 'Human Resources'");
        $stmt->execute();
        $hrUnit = $stmt->fetch(PDO::FETCH_ASSOC);
        $hrUnitId = $hrUnit['id'];

        // Admin credentials
        $adminEmail = 'admin@ghs.gov.gh';
        $adminPassword = 'admin123'; // Change this in production!
        $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT);

        // Check if admin already exists
        $stmt = $this->db->prepare("SELECT id FROM validation_staff WHERE email = :email");
        $stmt->execute(['email' => $adminEmail]);
        $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingAdmin) {
            echo "Admin user already exists. Updating password...\n";
            $stmt = $this->db->prepare("
                UPDATE validation_staff 
                SET password = :password, role = 'admin', unit_id = :unit_id
                WHERE email = :email
            ");
            $stmt->execute([
                'password' => $hashedPassword,
                'unit_id' => $hrUnitId,
                'email' => $adminEmail
            ]);
            echo "Admin password updated successfully!\n";
        } else {
            echo "Creating new admin user...\n";
            $stmt = $this->db->prepare("
                INSERT INTO validation_staff (name, email, password, role, unit_id)
                VALUES (:name, :email, :password, :role, :unit_id)
            ");
            $stmt->execute([
                'name' => 'System Administrator',
                'email' => $adminEmail,
                'password' => $hashedPassword,
                'role' => 'admin',
                'unit_id' => $hrUnitId
            ]);
            echo "Admin user created successfully!\n";
        }

        // HR Incharge credentials
        $hrEmail = 'incharge1@validation.com';
        $hrPassword = 'incharge123'; // Change this in production!
        $hashedHrPassword = password_hash($hrPassword, PASSWORD_BCRYPT);

        // Check if HR incharge already exists
        $stmt = $this->db->prepare("SELECT id FROM validation_staff WHERE email = :email");
        $stmt->execute(['email' => $hrEmail]);
        $existingHr = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingHr) {
            echo "HR Incharge already exists. Updating password...\n";
            $stmt = $this->db->prepare("
                UPDATE validation_staff 
                SET password = :password, role = 'incharge', unit_id = :unit_id
                WHERE email = :email
            ");
            $stmt->execute([
                'password' => $hashedHrPassword,
                'unit_id' => $hrUnitId,
                'email' => $hrEmail
            ]);
            echo "HR Incharge password updated successfully!\n";
        } else {
            echo "Creating HR Incharge user...\n";
            $stmt = $this->db->prepare("
                INSERT INTO validation_staff (name, email, password, role, unit_id)
                VALUES (:name, :email, :password, :role, :unit_id)
            ");
            $stmt->execute([
                'name' => 'HR Incharge',
                'email' => $hrEmail,
                'password' => $hashedHrPassword,
                'role' => 'incharge',
                'unit_id' => $hrUnitId
            ]);
            echo "HR Incharge user created successfully!\n";
        }

        echo "\n=== Login Credentials ===\n";
        echo "Admin:\n";
        echo "  Email: {$adminEmail}\n";
        echo "  Password: {$adminPassword}\n\n";
        echo "HR Incharge:\n";
        echo "  Email: {$hrEmail}\n";
        echo "  Password: {$hrPassword}\n";
        echo "=================