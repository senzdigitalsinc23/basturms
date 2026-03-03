<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        $db = Database::getInstance()->getConnection();
        
        echo "Adding unique constraints to staff and users tables...\n";
        
        try {
            // 1. Add unique constraint to email in staff table
            echo "1. Adding unique constraint to staff.email...\n";
            try {
                $db->exec("ALTER TABLE staff ADD UNIQUE KEY uk_staff_email (email)");
                echo "   ✓ Success\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "   ⊘ Already exists\n";
                } else {
                    throw $e;
                }
            }
            
            // 2. Add unique constraint to phone in staff table
            echo "2. Adding unique constraint to staff.phone...\n";
            try {
                $db->exec("ALTER TABLE staff ADD UNIQUE KEY uk_staff_phone (phone)");
                echo "   ✓ Success\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "   ⊘ Already exists\n";
                } else {
                    throw $e;
                }
            }
            
            // 3. Add unique constraint to id_no (Ghana Card) in staff table
            echo "3. Adding unique constraint to staff.id_no (Ghana Card)...\n";
            try {
                $db->exec("ALTER TABLE staff ADD UNIQUE KEY uk_staff_id_no (id_no)");
                echo "   ✓ Success\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "   ⊘ Already exists\n";
                } else {
                    throw $e;
                }
            }
            
            // 4. Add unique constraint to snnit_no in staff table (allow NULL)
            echo "4. Adding unique constraint to staff.snnit_no...\n";
            try {
                // First, check if there are any duplicate non-NULL values
                $stmt = $db->query("
                    SELECT snnit_no, COUNT(*) as count 
                    FROM staff 
                    WHERE snnit_no IS NOT NULL AND snnit_no != ''
                    GROUP BY snnit_no 
                    HAVING count > 1
                ");
                $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($duplicates)) {
                    echo "   ⚠ Warning: Found duplicate SSNIT numbers:\n";
                    foreach ($duplicates as $dup) {
                        echo "      - {$dup['snnit_no']} ({$dup['count']} times)\n";
                    }
                    echo "   Please fix duplicates before adding constraint\n";
                } else {
                    $db->exec("ALTER TABLE staff ADD UNIQUE KEY uk_staff_snnit_no (snnit_no)");
                    echo "   ✓ Success\n";
                }
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "   ⊘ Already exists\n";
                } else {
                    throw $e;
                }
            }
            
            // 5. Add unique constraint to username in users table
            echo "5. Adding unique constraint to users.username...\n";
            try {
                $db->exec("ALTER TABLE users ADD UNIQUE KEY uk_users_username (username)");
                echo "   ✓ Success\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "   ⊘ Already exists\n";
                } else {
                    throw $e;
                }
            }
            
            // 6. Add unique constraint to email in users table
            echo "6. Adding unique constraint to users.email...\n";
            try {
                $db->exec("ALTER TABLE users ADD UNIQUE KEY uk_users_email (email)");
                echo "   ✓ Success\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "   ⊘ Already exists\n";
                } else {
                    throw $e;
                }
            }
            
            echo "\n=== Summary ===\n";
            echo "Unique constraints added successfully!\n";
            echo "The following fields now prevent duplicates:\n";
            echo "  - staff.email\n";
            echo "  - staff.phone\n";
            echo "  - staff.id_no (Ghana Card)\n";
            echo "  - staff.snnit_no (SSNIT Number)\n";
            echo "  - users.username\n";
            echo "  - users.email\n";
            
        } catch (Exception $e) {
            echo "\n✗ Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function down(): void
    {
        $db = Database::getInstance()->getConnection();
        
        echo "Removing unique constraints from staff and users tables...\n";
        
        try {
            // Remove constraints from staff table
            $db->exec("ALTER TABLE staff DROP INDEX IF EXISTS uk_staff_email");
            $db->exec("ALTER TABLE staff DROP INDEX IF EXISTS uk_staff_phone");
            $db->exec("ALTER TABLE staff DROP INDEX IF EXISTS uk_staff_id_no");
            $db->exec("ALTER TABLE staff DROP INDEX IF EXISTS uk_staff_snnit_no");
            
            // Remove constraints from users table
            $db->exec("ALTER TABLE users DROP INDEX IF EXISTS uk_users_username");
            $db->exec("ALTER TABLE users DROP INDEX IF EXISTS uk_users_email");
            
            echo "Successfully removed all unique constraints\n";
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
};
