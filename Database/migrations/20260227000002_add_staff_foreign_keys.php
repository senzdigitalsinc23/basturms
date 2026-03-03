<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        $db = Database::getInstance()->getConnection();
        
        // Drop existing foreign keys if they exist
        $this->dropForeignKeysIfExist($db);
        
        // Add foreign key constraints with CASCADE DELETE
        
        // staff_address -> staff
        $sql = "ALTER TABLE staff_address 
                ADD CONSTRAINT fk_staff_address_staff 
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id) 
                ON DELETE CASCADE ON UPDATE CASCADE";
        $db->exec($sql);
        echo "Added foreign key: staff_address -> staff\n";
        
        // staff_academic_history -> staff
        $sql = "ALTER TABLE staff_academic_history 
                ADD CONSTRAINT fk_staff_academic_history_staff 
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id) 
                ON DELETE CASCADE ON UPDATE CASCADE";
        $db->exec($sql);
        echo "Added foreign key: staff_academic_history -> staff\n";
        
        // staff_appointment_history -> staff
        $sql = "ALTER TABLE staff_appointment_history 
                ADD CONSTRAINT fk_staff_appointment_history_staff 
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id) 
                ON DELETE CASCADE ON UPDATE CASCADE";
        $db->exec($sql);
        echo "Added foreign key: staff_appointment_history -> staff\n";
        
        // staff_class -> staff
        $sql = "ALTER TABLE staff_class 
                ADD CONSTRAINT fk_staff_class_staff 
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id) 
                ON DELETE CASCADE ON UPDATE CASCADE";
        $db->exec($sql);
        echo "Added foreign key: staff_class -> staff\n";
        
        // staff_subjects -> staff
        $sql = "ALTER TABLE staff_subjects 
                ADD CONSTRAINT fk_staff_subjects_staff 
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id) 
                ON DELETE CASCADE ON UPDATE CASCADE";
        $db->exec($sql);
        echo "Added foreign key: staff_subjects -> staff\n";
        
        // staff_roles -> staff
        $sql = "ALTER TABLE staff_roles 
                ADD CONSTRAINT fk_staff_roles_staff 
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id) 
                ON DELETE CASCADE ON UPDATE CASCADE";
        $db->exec($sql);
        echo "Added foreign key: staff_roles -> staff\n";
        
        // users -> staff (optional, for staff user accounts)
        // Note: This assumes user_id in users table references staff_id
        try {
            $sql = "ALTER TABLE users 
                    ADD CONSTRAINT fk_users_staff 
                    FOREIGN KEY (user_id) REFERENCES staff(staff_id) 
                    ON DELETE CASCADE ON UPDATE CASCADE";
            $db->exec($sql);
            echo "Added foreign key: users -> staff\n";
        } catch (\Exception $e) {
            echo "Skipped users -> staff foreign key (may not be applicable): " . $e->getMessage() . "\n";
        }
        
        echo "All foreign key constraints added successfully\n";
    }

    public function down(): void
    {
        $db = Database::getInstance()->getConnection();
        $this->dropForeignKeysIfExist($db);
        echo "Dropped all staff foreign key constraints\n";
    }
    
    private function dropForeignKeysIfExist($db): void
    {
        $foreignKeys = [
            'staff_address' => 'fk_staff_address_staff',
            'staff_academic_history' => 'fk_staff_academic_history_staff',
            'staff_appointment_history' => 'fk_staff_appointment_history_staff',
            'staff_class' => 'fk_staff_class_staff',
            'staff_subjects' => 'fk_staff_subjects_staff',
            'staff_roles' => 'fk_staff_roles_staff',
            'users' => 'fk_users_staff'
        ];
        
        foreach ($foreignKeys as $table => $constraint) {
            try {
                $sql = "ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}";
                $db->exec($sql);
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }
        }
    }
};
