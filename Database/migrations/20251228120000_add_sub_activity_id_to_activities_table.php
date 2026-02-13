<?php

use App\Core\Database;

class AddSubActivityIdToActivitiesTable20251228120000
{
    public function up()
    {
        $db = Database::getInstance()->getConnection();
        
        // Add sub_activity_id column if it doesn't exist
        $sql = "ALTER TABLE activities ADD COLUMN sub_activity_id VARCHAR(50) AFTER act_id";
        try {
            $db->exec($sql);
            echo "Added sub_activity_id column to activities table.\n";
            
            // Add index
            $db->exec("CREATE INDEX idx_sub_activity_id ON activities(sub_activity_id)");
            echo "Added index on sub_activity_id.\n";
            
        } catch (PDOException $e) {
            // fast fail if column likely exists, or print error
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "Column sub_activity_id already exists.\n";
            } else {
                throw $e;
            }
        }
    }

    public function down()
    {
        $db = Database::getInstance()->getConnection();
        $sql = "ALTER TABLE activities DROP COLUMN sub_activity_id";
        $db->exec($sql);
    }
}
