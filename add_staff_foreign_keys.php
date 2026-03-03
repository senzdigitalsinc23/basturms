<?php
/**
 * Standalone script to add foreign key constraints to staff tables
 * Run: php add_staff_foreign_keys.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

echo "=== Adding Foreign Key Constraints to Staff Tables ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Disable foreign key checks temporarily
    echo "Disabling foreign key checks...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 1. staff_address
    echo "\n1. Adding foreign key to staff_address...\n";
    try {
        $db->exec("ALTER TABLE staff_address DROP FOREIGN KEY IF EXISTS fk_staff_address_staff_id");
        $db->exec("
            ALTER TABLE staff_address
            ADD CONSTRAINT fk_staff_address_staff_id
            FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ");
        echo "   ✓ Success\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // 2. staff_academic_history
    echo "\n2. Adding foreign key to staff_academic_history...\n";
    try {
        $db->exec("ALTER TABLE staff_academic_history DROP FOREIGN KEY IF EXISTS fk_staff_academic_history_staff_id");
        $db->exec("
            ALTER TABLE staff_academic_history
            ADD CONSTRAINT fk_staff_academic_history_staff_id
            FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ");
        echo "   ✓ Success\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // 3. staff_appointment_history
    echo "\n3. Adding foreign key to staff_appointment_history...\n";
    try {
        $db->exec("ALTER TABLE staff_appointment_history DROP FOREIGN KEY IF EXISTS fk_staff_appointment_history_staff_id");
        $db->exec("
            ALTER TABLE staff_appointment_history
            ADD CONSTRAINT fk_staff_appointment_history_staff_id
            FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ");
        echo "   ✓ Success\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // 4. staff_class
    echo "\n4. Adding foreign key to staff_class...\n";
    try {
        $db->exec("ALTER TABLE staff_class DROP FOREIGN KEY IF EXISTS fk_staff_class_staff_id");
        $db->exec("
            ALTER TABLE staff_class
            ADD CONSTRAINT fk_staff_class_staff_id
            FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ");
        echo "   ✓ Success\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // 5. staff_subjects
    echo "\n5. Adding foreign key to staff_subjects...\n";
    try {
        $db->exec("ALTER TABLE staff_subjects DROP FOREIGN KEY IF EXISTS fk_staff_subjects_staff_id");
        $db->exec("
            ALTER TABLE staff_subjects
            ADD CONSTRAINT fk_staff_subjects_staff_id
            FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ");
        echo "   ✓ Success\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // 6. staff_roles
    echo "\n6. Adding foreign key to staff_roles...\n";
    try {
        $db->exec("ALTER TABLE staff_roles DROP FOREIGN KEY IF EXISTS fk_staff_roles_staff_id");
        $db->exec("
            ALTER TABLE staff_roles
            ADD CONSTRAINT fk_staff_roles_staff_id
            FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ");
        echo "   ✓ Success\n";
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // 7. notification_logs (if exists)
    echo "\n7. Adding foreign key to notification_logs...\n";
    try {
        $result = $db->query("SHOW TABLES LIKE 'notification_logs'");
        if ($result->rowCount() > 0) {
            $db->exec("ALTER TABLE notification_logs DROP FOREIGN KEY IF EXISTS fk_notification_logs_staff_id");
            $db->exec("
                ALTER TABLE notification_logs
                ADD CONSTRAINT fk_notification_logs_staff_id
                FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
            ");
            echo "   ✓ Success\n";
        } else {
            echo "   ⊘ Table does not exist, skipping\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Re-enable foreign key checks
    echo "\nRe-enabling foreign key checks...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n=== Verification ===\n";
    echo "Checking foreign keys...\n\n";
    
    // Verify foreign keys were created
    $tables = [
        'staff_address',
        'staff_academic_history',
        'staff_appointment_history',
        'staff_class',
        'staff_subjects',
        'staff_roles'
    ];
    
    foreach ($tables as $table) {
        $stmt = $db->query("
            SELECT CONSTRAINT_NAME, DELETE_RULE, UPDATE_RULE
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE TABLE_NAME = '{$table}'
            AND CONSTRAINT_SCHEMA = DATABASE()
        ");
        $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($constraints)) {
            foreach ($constraints as $constraint) {
                echo "✓ {$table}: {$constraint['CONSTRAINT_NAME']} ";
                echo "(DELETE: {$constraint['DELETE_RULE']}, UPDATE: {$constraint['UPDATE_RULE']})\n";
            }
        } else {
            echo "✗ {$table}: No foreign keys found\n";
        }
    }
    
    echo "\n=== Complete ===\n";
    echo "Foreign key constraints have been added successfully!\n";
    echo "Deleting a staff record will now automatically delete all related records.\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Try to re-enable foreign key checks
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {
        // Ignore
    }
}
