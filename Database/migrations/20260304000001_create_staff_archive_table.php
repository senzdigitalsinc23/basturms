<?php

use App\Core\Database;

return new class {
    public function up(Database $db): void
    {
        $pdo = $db->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS staff_archive (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id VARCHAR(20) NOT NULL,
            archive_reason TEXT NULL,
            archived_by VARCHAR(20) NOT NULL,
            archived_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            staff_data JSON NOT NULL COMMENT 'Complete staff record at time of archiving',
            INDEX idx_staff_id (staff_id),
            INDEX idx_archived_at (archived_at),
            INDEX idx_archived_by (archived_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "Created staff_archive table\n";
    }

    public function down(Database $db): void
    {
        $pdo = $db->getConnection();
        $pdo->exec("DROP TABLE IF EXISTS staff_archive");
        echo "Dropped staff_archive table\n";
    }
};
