<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    echo "Attempting to fix Foreign Key on class_activity_assignment...\n";

    // 1. Drop the incorrect Foreign Key
    // Note: We need to verify if the index needs to be dropped too, but usually DROP FOREIGN KEY is enough.
    // The constraint name is class_activity_assignment_ibfk_2 based on previous inspection.
    
    // Check if constraint exists first to avoid error?
    // Or just try-catch.
    
    try {
        $sql = "ALTER TABLE class_activity_assignment DROP FOREIGN KEY class_activity_assignment_ibfk_2";
        $db->exec($sql);
        echo "Dropped incorrect FK class_activity_assignment_ibfk_2.\n";
    } catch (PDOException $e) {
        echo "Warning: Could not drop FK (might not exist or different name): " . $e->getMessage() . "\n";
    }

    // 2. Add the correct Foreign Key
    // Link act_id to assignment_activities(activity_id)
    // First, ensure all current data satisfies the new constraint.
    // We saw earlier that existing data in class_activity_assignment might have been blocked by the old constraint,
    // so it might be empty or valid.
    
    // We also need to ensure the index exists for the FK column if not already. 
    // `act_id` already has a key from the previous FK.

    $sql = "ALTER TABLE class_activity_assignment 
            ADD CONSTRAINT class_activity_assignment_new_fk 
            FOREIGN KEY (act_id) REFERENCES assignment_activities(activity_id) 
            ON DELETE CASCADE ON UPDATE CASCADE";
            
    $db->exec($sql);
    echo "Added correct FK pointing to assignment_activities(activity_id).\n";
    
    echo "Schema fix completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
