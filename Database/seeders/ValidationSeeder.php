<?php

use Database\Seeder;
use App\Core\Database;

$db = Database::getInstance()->getConnection();

return new class($db) extends Seeder
{
    public function run(): void
    {
        // Create sample units
        $units = [];
        $unitNames = [
            ['name' => 'Human Resources', 'description' => 'HR Department'],
            ['name' => 'Finance', 'description' => 'Finance Department'],
            ['name' => 'IT Department', 'description' => 'Information Technology'],
            ['name' => 'Operations', 'description' => 'Operations Department']
        ];

        foreach ($unitNames as $unit) {
            $this->db->prepare("
                INSERT INTO units (name, description)
                VALUES (:name, :description)
            ")->execute($unit);
            $units[] = ['id' => (int)$this->db->lastInsertId(), 'name' => $unit['name']];
        }

        // Create admin user
        $this->db->prepare("
            INSERT INTO validation_staff (name, email, password, role, unit_id)
            VALUES (:name, :email, :password, :role, :unit_id)
        ")->execute([
            'name' => 'Admin User',
            'email' => 'admin@validation.com',
            'password' => password_hash('admin123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'unit_id' => $units[0]['id']
        ]);

        // Create accountant user
        $this->db->prepare("
            INSERT INTO validation_staff (name, email, password, role, unit_id)
            VALUES (:name, :email, :password, :role, :unit_id)
        ")->execute([
            'name' => 'Accountant User',
            'email' => 'accountant@validation.com',
            'password' => password_hash('accountant123', PASSWORD_BCRYPT),
            'role' => 'accountant',
            'unit_id' => $units[1]['id']
        ]);

        // Create incharge users for each unit
        foreach ($units as $index => $unit) {
            $this->db->prepare("
                INSERT INTO validation_staff (name, email, password, role, unit_id)
                VALUES (:name, :email, :password, :role, :unit_id)
            ")->execute([
                'name' => $unit['name'] . ' Incharge',
                'email' => 'incharge' . ($index + 1) . '@validation.com',
                'password' => password_hash('incharge123', PASSWORD_BCRYPT),
                'role' => 'incharge',
                'unit_id' => $unit['id']
            ]);

            // Create 3 staff members for each unit
            for ($i = 1; $i <= 3; $i++) {
                $this->db->prepare("
                    INSERT INTO validation_staff (name, email, password, role, unit_id)
                    VALUES (:name, :email, :password, :role, :unit_id)
                ")->execute([
                    'name' => $unit['name'] . ' Staff ' . $i,
                    'email' => strtolower(str_replace(' ', '', $unit['name'])) . '.staff' . $i . '@validation.com',
                    'password' => password_hash('staff123', PASSWORD_BCRYPT),
                    'role' => 'staff',
                    'unit_id' => $unit['id']
                ]);
            }
        }

        echo "Validation system seeded successfully!\n";
        echo "Admin: admin@validation.com / admin123\n";
        echo "Accountant: accountant@validation.com / accountant123\n";
        echo "Incharge: incharge1@validation.com / incharge123\n";
        echo "Staff: humanresources.staff1@validation.com / staff123\n";
    }
};
