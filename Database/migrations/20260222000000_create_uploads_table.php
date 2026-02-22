<?php

use Database\Migration;

class CreateUploadsTable20260222000000 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS uploads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doc_name VARCHAR(255) NOT NULL,
                doc_type VARCHAR(100) NOT NULL COMMENT 'e.g., profile_picture, staff_signature, student_document, staff_document',
                url VARCHAR(255) NOT NULL,
                file_type VARCHAR(100) NOT NULL COMMENT 'MIME type',
                file_size BIGINT NOT NULL,
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_doc_type (doc_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS uploads;");
    }
}
