<?php

use Database\Migration;

class CreateStudentPromotionsTable20250115000042 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS student_promotions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_no VARCHAR(20) NOT NULL,
                from_class_id INT NOT NULL,
                to_class_id INT NOT NULL,
                promotion_type ENUM('normal','special','graduation') NOT NULL DEFAULT 'normal',
                remarks TEXT DEFAULT NULL,
                promoted_by VARCHAR(20) NOT NULL,
                promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_no) REFERENCES students(student_no) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (from_class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (to_class_id) REFERENCES classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
                KEY idx_student_no (student_no),
                KEY idx_promoted_at (promoted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS student_promotions;");
    }
}
