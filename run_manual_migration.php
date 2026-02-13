<?php

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/database/migrations/20251231000001_create_student_summary_report_table.php';

$db = App\Core\Database::getInstance()->getConnection();
try {
    $sql = "
            CREATE TABLE IF NOT EXISTS student_summary_report (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(20) NOT NULL,
                subject_id INT NOT NULL,
                class_id INT NOT NULL,
                academic_year VARCHAR(9) NOT NULL,
                term VARCHAR(20) NOT NULL,
                assignment_activity_id INT NOT NULL,
                total_score DECIMAL(5,2) NOT NULL DEFAULT 0,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Foreign keys
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (assignment_activity_id) REFERENCES assignment_activities(id) ON DELETE CASCADE ON UPDATE CASCADE,
                
                -- Unique constraint
                UNIQUE KEY unique_report_entry (student_no, subject_id, assignment_activity_id, academic_year, term)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
    $sql2 = "
            CREATE TABLE IF NOT EXISTS queue_jobs (
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
        ";
    $db->exec($sql2);
    echo "Queue jobs table migration run successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
