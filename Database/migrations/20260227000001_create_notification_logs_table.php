<?php

use App\Core\Database;

return new class {
    public function up(): void
    {
        $db = Database::getInstance()->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS notification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id VARCHAR(50) NOT NULL,
            notification_type ENUM('email', 'sms') NOT NULL,
            recipient VARCHAR(255) NOT NULL,
            purpose VARCHAR(100) NOT NULL,
            status ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
            sent_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_staff_id (staff_id),
            INDEX idx_notification_type (notification_type),
            INDEX idx_status (status),
            INDEX idx_sent_at (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        
        echo "Created notification_logs table\n";
    }

    public function down(): void
    {
        $db = Database::getInstance()->getConnection();
        $db->exec("DROP TABLE IF EXISTS notification_logs");
        echo "Dropped notification_logs table\n";
    }
};
