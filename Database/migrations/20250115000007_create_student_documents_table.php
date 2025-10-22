<?php

use Database\Migration;

class CreateStudentDocumentsTable20250115000007 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS student_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(50) NOT NULL,
                document_name VARCHAR(255) NOT NULL,
                document_type VARCHAR(100) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_size INT NULL,
                mime_type VARCHAR(100) NULL,
                description TEXT NULL,
                uploaded_by VARCHAR(50) NULL,
                upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student (student_no),
                INDEX idx_document_type (document_type),
                INDEX idx_status (status),
                INDEX idx_upload_date (upload_date),
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS student_documents;");
    }
}
