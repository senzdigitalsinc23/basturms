<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS staff_status_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id VARCHAR(50) NOT NULL,
            old_status VARCHAR(20) NOT NULL,
            new_status VARCHAR(20) NOT NULL,
            reason TEXT NULL,
            changed_by VARCHAR(50) NOT NULL,
            changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_staff_id (staff_id),
            INDEX idx_changed_at (changed_at),
            FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        
        echo "Created staff_status_log table\n";
    }

    public function down(): void
    {
        $db = Database::getInstance()->getConnection();
        $db->exec("DROP TABLE IF EXISTS staff_status_log");
        echo "Dropped staff_status_log table\n";
    }
};
