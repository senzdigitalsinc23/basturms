<?php

use Database\Migration;

class CreateQueueJobsTable20251231000002 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE queue_jobs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                job_class VARCHAR(255) NOT NULL,
                payload JSON NOT NULL,
                status ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
                attempts INT NOT NULL DEFAULT 0,
                error_message TEXT DEFAULT NULL,
                api_job_id VARCHAR(50) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS queue_jobs");
    }
}
