<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        $db = Database::getInstance()->getConnection();
        
        echo "Adding foreign key constraints to staff tables...\n";
        
        try {
            // Disable foreign key checks temporarily
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // 1. Add foreign key to staff_address table
            echo "Adding foreign key to staff_address...\n";
            $db->exec("
                ALTER TABLE staff_address
                DROP FOREIGN KEY IF EXISTS fk_staff_address_staff_id
            ");
            $db->exec("
                ALTER TABLE staff_address
                ADD CONSTRAINT fk_staff_address_staff_id
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ");
            
            // 2. Add foreign key to staff_academic_history table
            echo "Adding foreign key to staff_academic_history...\n";
            $db->exec("
                ALTER TABLE staff_academic_history
                DROP FOREIGN KEY IF EXISTS fk_staff_academic_history_staff_id
            ");
            $db->exec("
                ALTER TABLE staff_academic_history
                ADD CONSTRAINT fk_staff_academic_history_staff_id
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ");
            
            // 3. Add foreign key to staff_appointment_history table
            echo "Adding foreign key to staff_appointment_history...\n";
            $db->exec("
                ALTER TABLE staff_appointment_history
                DROP FOREIGN KEY IF EXISTS fk_staff_appointment_history_staff_id
            ");
            $db->exec("
                ALTER TABLE staff_appointment_history
                ADD CONSTRAINT fk_staff_appointment_history_staff_id
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ");
            
            // 4. Add foreign key to staff_class table
            echo "Adding foreign key to staff_class...\n";
            $db->exec("
                ALTER TABLE staff_class
                DROP FOREIGN KEY IF EXISTS fk_staff_class_staff_id
            ");
            $db->exec("
                ALTER TABLE staff_class
                ADD CONSTRAINT fk_staff_class_staff_id
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ");
            
            // 5. Add foreign key to staff_subjects table
            echo "Adding foreign key to staff_subjects...\n";
            $db->exec("
                ALTER TABLE staff_subjects
                DROP FOREIGN KEY IF EXISTS fk_staff_subjects_staff_id
            ");
            $db->exec("
                ALTER TABLE staff_subjects
                ADD CONSTRAINT fk_staff_subjects_staff_id
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ");
            
            // 6. Add foreign key to staff_roles table
            echo "Adding foreign key to staff_roles...\n";
            $db->exec("
                ALTER TABLE staff_roles
                DROP FOREIGN KEY IF EXISTS fk_staff_roles_staff_id
            ");
            $db->exec("
                ALTER TABLE staff_roles
                ADD CONSTRAINT fk_staff_roles_staff_id
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ");
            
            // 7. Add foreign key to notification_logs table (if exists)
            echo "Adding foreign key to notification_logs...\n";
            $result = $db->query("SHOW TABLES LIKE 'notification_logs'");
            if ($result->rowCount() > 0) {
                $db->exec("
                    ALTER TABLE notification_logs
                    DROP FOREIGN KEY IF EXISTS fk_notification_logs_staff_id
                ");
                $db->exec("
                    ALTER TABLE notification_logs
                    ADD CONSTRAINT fk_notification_logs_staff_id
                    FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");
            }
            
            // 8. Add foreign key to users table for staff user accounts
            echo "Adding foreign key to users table...\n";
            // First, check if the foreign key column exists and has matching data types
            $db->exec("
                ALTER TABLE users
                DROP FOREIGN KEY IF EXISTS fk_users_staff_id
            ");
            $db->exec("
                ALTER TABLE users
                ADD CONSTRAINT fk_users_staff_id
                FOREIGN KEY (user_id) REFERENCES staff(staff_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ");
            
            // Re-enable foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            echo "Successfully added all foreign key constraints!\n";
            echo "\nCascade delete is now enabled for:\n";
            echo "  - staff_address\n";
            echo "  - staff_academic_history\n";
            echo "  - staff_appointment_history\n";
            echo "  - staff_class\n";
            echo "  - staff_subjects\n";
            echo "  - staff_roles\n";
            echo "  - notification_logs\n";
            echo "  - users (staff accounts)\n";
            
        } catch (\Exception $e) {
            // Re-enable foreign key checks even on error
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down(): void
    {
        $db = Database::getInstance()->getConnection();
        
        echo "Removing foreign key constraints from staff tables...\n";
        
        try {
            // Disable foreign key checks temporarily
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Remove all foreign keys
            $db->exec("ALTER TABLE staff_address DROP FOREIGN KEY IF EXISTS fk_staff_address_staff_id");
            $db->exec("ALTER TABLE staff_academic_history DROP FOREIGN KEY IF EXISTS fk_staff_academic_history_staff_id");
            $db->exec("ALTER TABLE staff_appointment_history DROP FOREIGN KEY IF EXISTS fk_staff_appointment_history_staff_id");
            $db->exec("ALTER TABLE staff_class DROP FOREIGN KEY IF EXISTS fk_staff_class_staff_id");
            $db->exec("ALTER TABLE staff_subjects DROP FOREIGN KEY IF EXISTS fk_staff_subjects_staff_id");
            $db->exec("ALTER TABLE staff_roles DROP FOREIGN KEY IF EXISTS fk_staff_roles_staff_id");
            $db->exec("ALTER TABLE notification_logs DROP FOREIGN KEY IF EXISTS fk_notification_logs_staff_id");
            $db->exec("ALTER TABLE users DROP FOREIGN KEY IF EXISTS fk_users_staff_id");
            
            // Re-enable foreign key checks
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            echo "Successfully removed all foreign key constraints\n";
            
        } catch (\Exception $e) {
            // Re-enable foreign key checks even on error
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
};
